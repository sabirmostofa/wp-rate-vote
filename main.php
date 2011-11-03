<?php

/*
  Plugin Name: WP-Vote-Rate
  Plugin URI: http://sabirul-mostofa.blogspot.com
  Description: Vote and Rate the contents
  Version: 1.0
  Author: Sabirul Mostofa
  Author URI: http://sabirul-mostofa.blogspot.com
 */

include 'featured-post-widget.php';
$wpVoteRate = new wpVoteRate();

class wpVoteRate {

    public $table = '';
    public $table_av = '';
    public $image_dir = '';
    public $grades = array('A', ' A-', ' B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'D-', 'F');
    public $grades_to_show = array('A', 'B', 'C', 'D', 'F');
    public $prefix = 'wpvote';
    public $meta_box = array();

    function __construct() {
        global $wpdb;
        $this->set_meta();
        $this->table = $wpdb->prefix . 'vote_rate_list';
        $this->table_av = $wpdb->prefix . 'vote_rate_average';
        $this->image_dir = plugins_url('/', __FILE__) . 'images/';
        add_action('wp_enqueue_scripts', array($this, 'front_scripts'));
        add_filter('the_content', array($this, 'filter_content'), 1000);
        add_action('wp_print_styles', array($this, 'front_css'));
        register_activation_hook(__FILE__, array($this, 'create_table'));
        add_action('wp_ajax_submit-wpvote', array($this, 'ajax_insert_vote'));
        add_action('wp_ajax_nopriv_submit-wpvote', array($this, 'ajax_insert_vote'));
        add_action('wp_ajax_get_image_src', array($this, 'ajax_get_image'));
        add_action('wp_ajax_nopriv_get_image_src', array($this, 'ajax_get_image'));
        add_action('wp_ajax_nopriv_set_user', array($this, 'ajax_set_user'));
        add_action('plugins_loaded', array($this, 'return_image'));
        add_action('add_meta_boxes', array($this, 'add_custom_box'));
        add_action('widgets_init', create_function('', 'register_widget("Grading_Featured_Post");'));

        // backwards compatible
        add_action('admin_init', array($this, 'add_custom_box'));

        add_action('save_post', array($this, 'vote_save_postdata'));
        add_action('admin_menu', array($this, 'CreateMenu'), 50);
    }

    function CreateMenu() {
        add_submenu_page('options-general.php', 'Grading Settings', 'Grading Settings', 'activate_plugins', 'wpGradingSystem', array($this, 'OptionsPage'));
    }

    function OptionsPage() {
        include 'options-page.php';
    }

    function front_scripts() {
        global $post;
        if (is_page() || is_single()) {
            wp_enqueue_script('jquery');
            if (!(is_admin())) {
                // wp_enqueue_script('wpvr_boxy_script', plugins_url('/' , __FILE__).'js/boxy/src/javascripts/jquery.boxy.js');
                wp_enqueue_script('wpvr_front_script', plugins_url('/', __FILE__) . 'js/script_front.js');
                wp_localize_script('wpvr_front_script', 'wpvrSettings', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'pluginurl' => plugins_url('/', __FILE__),
                    'site_url' => site_url(),
                    'post_id' => $post->ID
                ));
            }
        }
    }

    function front_css() {
        if (!(is_admin())):
            wp_enqueue_style('wpvr_front_css', plugins_url('/', __FILE__) . 'css/style_front.css');
        endif;
    }

    function filter_content($content) {

        global $wpdb, $post;
        $post_id = $post->ID;

        if (!in_category(1, $post_id) && !get_post_meta($post_id, 'vote_checkbox', true))
            return $content;
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $cd = $this->get_vote($post_id, $user_id);
        if (is_single() || is_page()) {
            $vote_val = $this->get_vote($post_id, $user_id);
            $grade_user_count = $this->get_vote_count($post_id);
            $grade_user_count = ($grade_user_count === null) ? 0 : $grade_user_count;
            $grade_av_image = $this->get_av_image($post_id);



            $divs = '';
            foreach ($this->grades_to_show as $key => $grade) {
                $src = ($cd !== null && $cd == $key) ? $this->image_dir . strtolower($grade) . '_color.png' : $this->image_dir . strtolower($grade) . '_gray.png';
                $id = ($cd !== null && $cd == $key) ? 'id="user-grade"' : " ";
                $divs .= "<div class='rate-grade-image'><a href='#'><img $id class='val$key' src='$src'/></a></div>";
            }



            $extra_content = <<<HDS
   <div class='grade-details'><span id='grade-users-count'>$grade_user_count</span> User(s) graded this
    <img id="av-grade-image" src="$grade_av_image"/>                
    </div>
   <div class='dotted-margin'>       
    <div class='grade-this-text'>Grade this product ... </div> $divs
        <div class="grade-text-value"></div>
    <div stye="clear:both;"></div>
    
    </div   
                  
                               
HDS;
            $img_src = site_url() . '/?show-grade=1&post=' . $post_id;
            $pre_text = htmlentities("<img src=\"$img_src\"/>");
            $report_image = $this->reurn_report_link($post_id);
            $nonce = wp_create_nonce(time());


            $image_div = <<<IM
   <div class='copy-image-src' >
       <img src="$report_image"/>
         <h2> Show the Grade in your website: </h2>
         <input type="button" value="Show Image Source" id="show-image-button"/>
       <div id="show-var-image"></div>
       </div>
       
IM;

            $content = $extra_content . $content . $image_div;
            $redirect = get_permalink($post_id);
            $login_link = wp_login_url($redirect);
//                    $content.=<<<HDS
//                        <div style="display:block;background-color:red;margin:5px 0 15px">You need to be logged in to rate the post. 
//                         <a href="$login_link">Login Here</a></div>        
//HDS;

            return $content;
        }
        return $content;
    }

    //meta boxes


    function set_meta() {
        $this->meta_box = array(
            'id' => 'vote-meta-box',
            'title' => "Show Grading System",
            'page' => 'page',
            'context' => 'normal',
            'priority' => 'high',
            'fields' => array(
                array(
                    'name' => 'Show Grading System',
                    'desc' => ' Check To enable Grading System',
                    'id' => $this->prefix . 'checkbox',
                    'type' => 'checkbox',
                    'std' => ''
                )
            )
        );
    }

    function add_custom_box() {
        $post_id = -3;
        if (isset($_REQUEST['post']))
            $post_id = $_REQUEST['post'];
        if (in_category(1, $post_id))
            return;
        $meta_box = $this->meta_box;

        add_meta_box($meta_box['id'], $meta_box['title'], array($this, 'show_box'), $meta_box['page'], $meta_box['context'], $meta_box['priority']);
        add_meta_box($meta_box['id'], $meta_box['title'], array($this, 'show_box'), 'post', $meta_box['context'], $meta_box['priority']);
    }

    function show_box() {
        $meta_box = $this->meta_box;
        global $post;
        if (get_post_meta($post->ID, 'vote_checkbox', true))
            echo '<input type="checkbox" name="vote_checkbox" value="checked" checked="true"/>';
        else
            echo '<input type="checkbox" name="vote_checkbox" value="checked"/>';
    }

    function vote_save_postdata($post_id) {
        global $wpdb;
//        var_dump($_POST);
//        exit;

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }
        if (isset($_POST['post_ID']))
            $post_id = $_POST['post_ID'];
        $val = (isset($_POST['vote_checkbox'])) ? 1 : 0;
        update_post_meta($post_id, 'vote_checkbox', $val);
    }

    function ajax_insert_vote() {
        $rating = $_POST['grade-value'];
        $post_id = $_POST['post_id'];
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $login_url = wp_login_url(get_permalink($post_id));

        if ($user_id == 0) {
            echo json_encode(array('action' => 'none', 'user' => $user_id, 'login' => $login_url));
            exit;
        }
        if ($this->get_vote($post_id, $user_id) !== null) {
            $this->update_vote($post_id, $user_id, $rating);
            $this->update_av_table($post_id);
            $vote_count = $this->get_vote_count($post_id);
            $new_av_image = $this->get_av_image($post_id);
            echo json_encode(array('action' => 'updated', 'grade' => $rating, 'count' => $vote_count, 'image' => $new_av_image, 'user' => $user_id));
        } else {
            $this->add_vote($post_id, $user_id, $rating);
            $this->update_av_table($post_id);
            $vote_count = $this->get_vote_count($post_id);
            $new_av_image = $this->get_av_image($post_id);
            echo json_encode(array('action' => 'added', 'grade' => $rating, 'count' => $vote_count, 'image' => $new_av_image, 'user' => $user_id));
        }

        exit;
    }

    function ajax_set_user() {
        echo wp_set_current_user(1);
        exit('success');
    }

//function user_voted($post_id,$user_id){
//    global $wpdb;
//return  $wpdb -> get_var("select grade from $this->table where post_id='$post_id' and user_id='$user_id'");
//    
//}

    function get_vote($post_id, $user_id) {
        global $wpdb;
        return $wpdb->get_var("select grade from $this->table where post_id='$post_id' and user_id='$user_id'");
    }

    function add_vote($post_id, $user_id, $rating) {
        global $wpdb;
        $wpdb->insert($this->table, array(
            'post_id' => $post_id,
            'user_id' => $user_id,
            'grade' => $rating
                ), array(
            '%d',
            '%d',
            '%d'
        ));
//        $prev_value= get_post_meta($post_id,'wp_vote_meta',true);
//        update_post_meta($post_id, 'wp_vote_meta', $prev_value.";$user_id,$rating" );       
    }

    function update_vote($post_id, $user_id, $rating) {
        global $wpdb;
        $wpdb->update($this->table, array('grade' => $rating), array('post_id' => $post_id, 'user_id' => $user_id));
//      $meta =get_post_meta($post_id,'wp_vote_meta',true);
//      $this ->delete_vote($post_id, $user_id);
//      $this ->add_vote($post_id, $user_id, $rating);
    }

    function update_av_table($post_id) {
        global $wpdb;

        $vote_count = $wpdb->get_var("select count(*) from $this->table where post_id='$post_id'");
        $av_rating = $wpdb->get_var("select avg(grade) from $this->table where post_id='$post_id'");
        $date = date("Y:m:d H:i:s");

        if ($this->get_vote_count($post_id))
            $wpdb->update($this->table_av, array('average_grade' => $av_rating, 'grade_count' => $vote_count, 'last_added' => $date), array('post_id' => $post_id));
        else
            $wpdb->insert($this->table_av, array('post_id' => $post_id, 'average_grade' => $av_rating, 'grade_count' => $vote_count), array('%d', '%f', '%d'));
    
        
        update_post_meta($post_id,'post_av_rating',$av_rating);
        
    }

    function get_av_grade($post_id) {
        global $wpdb;
        return $wpdb->get_var("select average_grade from $this->table_av where post_id='$post_id'");
    }

    function get_vote_count($post_id) {
        global $wpdb;
        return $wpdb->get_var("select grade_count from $this->table_av where post_id='$post_id'");
    }

    function delete_vote($post_id, $user_id) {
        global $wpdb;
        $wpdb->query("delete from $this->table where post_id='$post_id' and user_id='$user_id' ");
    }

    function determine_grade($grade) {
        if ($grade == 0)
            $val = 'A';
        if ($grade > 0 && $grade <= 0.5)
            $val = 'A-';
        if ($grade > 0.5 && $grade < 1)
            $val = 'B+';
        if ($grade == 1)
            $val = 'B';
        if ($grade > 1 && $grade <= 1.5)
            $val = 'B-';
        if ($grade > 1.5 && $grade < 2)
            $val = 'C+';
        if ($grade == 2)
            $val = 'C';
        if ($grade > 2 && $grade <= 2.5)
            $val = 'C-';
        if ($grade > 2.5 && $grade < 3)
            $val = 'D+';
        if ($grade == 3)
            $val = 'D';
        if ($grade > 3 && $grade <= 3.75)
            $val = 'D-';
        if ($grade == 4)
            $val = 'F';
        return $val;
    }

    function get_av_image($post_id=-1) {
        if ($post_id == -1) {
            global $post;
            $post_id = $post->ID;
        }
        $av_grade = $this->get_av_grade($post_id);
        if ($av_grade === null)
            return $this->image_dir . 'none_all_alt.png';
        $grade = $this->determine_grade($av_grade);
        if (strlen($grade) == 1)
            $image = strtolower($grade) . '_all_alt.png';
        else
            $image = ($grade[1] == '+') ? strtolower($grade[0]) . '_plus_all_alt.png' : strtolower($grade[0]) . '_minus_all_alt.png';
        return $this->image_dir . $image;
    }

    function create_table() {
        global $wpdb;
        $sql = "CREATE TABLE IF NOT EXISTS $this->table  (
		`id` int unsigned NOT NULL AUTO_INCREMENT, 
		`post_id` int unsigned  NOT NULL,
		`user_id` int unsigned  NOT NULL,
		`grade` tinyint(1)  NOT NULL,
                                   `added` timestamp not null default current_timestamp,
		 PRIMARY KEY (`id`),
		 key `post`(`post_id`),	
		 key `user`(`user_id`)		 	
		)";

        $sql1 = "CREATE TABLE IF NOT EXISTS $this->table_av  (
		`id` int unsigned NOT NULL AUTO_INCREMENT, 
		`post_id` int unsigned  NOT NULL,		
		`average_grade` float(5,2) NOT NULL,
                                    `grade_count` int unsigned NOT NULL,
                                   `last_added` timestamp not null default current_timestamp,
		 PRIMARY KEY (`id`),
		 key `post`(`post_id`)		 		 	
		)";


        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);
        dbDelta($sql1);
    }

    // return cached images or generate a new one

    function return_image() {
        if (isset($_REQUEST['show-grade'])) {
            $post_id = $_REQUEST['post'];
            $ts = $_REQUEST['ts'];

            if (!$this->can_return_image($post_id, $ts))
                exit;
            $cache_dir = dirname(__FILE__) . '/images';
            $cache_file = dirname(__FILE__) . '/images/cached/' . $post_id . '.png';
            $http_cache_file = plugins_url('/', __FILE__) . '/images/cached/' . $post_id . '.png';

            if (!is_dir($cache_dir)) {
                mkdir($cache_dir);
                exit($cache_dir);
            }
            if (get_option('grading-settings-var'))
                extract(get_option('grading-settings-var'));
            $cachetime = (isset($cache_time)) ? $cache_time : 1800;

            if (file_exists($cache_file) && (time() - $cachetime) < filemtime($cache_file)) {
                wp_redirect($http_cache_file);
                exit;
            }

            $this->output_image($post_id);
        }
    }

    function output_image($post_id, $output_direct=true) {
        $cache_file = dirname(__FILE__) . '/images/cached/' . $post_id . '.png';
        $a = timezone_name_from_abbr('cst');
        date_default_timezone_set($a);
        $date = date("H:i:s");
        $date_days = date('Y-m-d');
        $date = $date . ' CST';

        $post_title = wp_kses_decode_entities(get_the_title($post_id));

        $first_part = substr($post_title, 0, 30);
        if (strlen($post_title) > 30) {
            $second_part = substr($post_title, 30, strlen($post_title));
        }
        //calculating image length 50 image width 95 timestamp width
        $title_len = strlen($post_title) * 10 + 50 + 220;
        $title_len = ($title_len > 800) ? 800 : $title_len;
        $image_src = $this->get_av_image($post_id);
        $im_src_base = plugins_url('/', __FILE__) . 'images/test-card3.png';
        $im_base = @imagecreatefrompng($im_src_base);
        $im = @imagecreatefrompng($image_src);
        //$im_base = @imagecreate($title_len,50);
        $textcolor = imagecolorallocate($im, 255, 0, 0);
        imagestring($im_base, 3, 300, 28, $date, $textcolor);
        imagestring($im_base, 3, 300, 40, $date_days, $textcolor);
        imagestring($im_base, 5, 105, 70, $first_part, $textcolor);
        if (isset($second_part)) {
            $second_part = (strlen($second_part) > 38) ? substr($second_part, 0, 38) . '..' : $second_part;
            imagestring($im_base, 5, 10, 85, $second_part, $textcolor);
        }
        imagecopymerge($im_base, $im, 110, 20, 0, 0, 35, 34, 100);

        // Write the string at the top left
        // imagestring($im, 5, 0, 0, $post_title, $textcolor);
        // imagepng($im);
        //echo file_get_contents($image_src);
        imagepng($im_base, $cache_file);
        if ($output_direct) {
            header("Content-Type: image/png");
            echo file_get_contents($cache_file);

            exit;
        }
    }

    function reurn_report_link($post_id) {
        $cache_file = dirname(__FILE__) . '/images/cached/' . $post_id . '.png';
        if (get_option('grading-settings-var'))
            extract(get_option('grading-settings-var'));
        
        $cachetime = (isset($cache_time)) ? $cache_time : 1800;

        if (!is_file($cache_file) || (time() - $cachetime) < filemtime($cache_file))
            $this->output_image($post_id, false);

        return plugins_url('/', __FILE__) . 'images/cached/' . $post_id . '.png';
    }

    function can_return_image($post_id, $ts) {
        $meta_array = get_post_meta($post_id, 'vote_image_timestamps', true);
        if (in_array($ts, $meta_array))
            return true;
        return;
    }

    function ajax_get_image() {
        $post_id = $_POST['post_id'];
        $time = time();
        $meta_array = get_post_meta($post_id, 'vote_image_timestamps', true);
        $to_add = $time;
        if (!is_array($meta_array)) {
            update_post_meta($post_id, 'vote_image_timestamps', array($to_add));
        } else {
            $meta_array[] = $to_add;
            update_post_meta($post_id, 'vote_image_timestamps', $meta_array);
        }
        $img_src = site_url() . '/?show-grade=1&post=' . $post_id . '&ts=' . $time;
        // $pre_text = htmlentities( "<img src=\"$img_src\"/>");
        $pre_text = "<img src=\"$img_src\"/>";

        $a = array('data' => $pre_text);
        echo json_encode($a);
        exit;
    }

    function exists_in_table($id) {
        global $wpdb;
        //$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
        $result = $wpdb->get_results("SELECT id FROM $this->table  where post_id='$id'");
        if (empty($result))
            return false;

        return true;
    }

}
