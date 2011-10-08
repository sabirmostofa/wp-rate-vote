<?php
/*
Plugin Name: WP-Vote-Rate
Plugin URI: http://sabirul-mostofa.blogspot.com
Description: Vote and Rate the contents
Version: 1.0
Author: Sabirul Mostofa
Author URI: http://sabirul-mostofa.blogspot.com
*/


$wpVoteRate= new wpVoteRate();


class wpVoteRate{
    public $table ='';
    public $table_av ='';
    public $image_dir='';
    public $grades = array( 'A',' A-',' B+', 'B', 'B-', 'C+', 'C','C-', 'D+','D', 'D-', 'F');
    public $grades_to_show = array( 'A', 'B',  'C', 'D', 'F');
	
	
        function __construct(){
               global $wpdb;
               $this -> table =  $wpdb -> prefix.'vote_rate_list';
               $this -> table_av =  $wpdb -> prefix.'vote_rate_average';
               $this -> image_dir =plugins_url('/' , __FILE__).'images/';
             add_action('wp_enqueue_scripts' , array($this,'front_scripts'));
             add_filter('the_content',array($this, 'filter_content' ),1000 );
             add_action('wp_print_styles' , array($this,'front_css'));       
             register_activation_hook(__FILE__, array($this, 'create_table'));
             add_action( 'wp_ajax_submit-wpvote', array($this,'ajax_insert_vote'));
             
             
             
             			
        }

        	function front_scripts(){
                    global $post;
                    if( is_page()|| is_single()){
                        wp_enqueue_script('jquery');
                                if(!(is_admin())){
                                        wp_enqueue_script('wpvr_front_script', plugins_url('/' , __FILE__).'js/script_front.js');
                                        wp_localize_script('wpvr_front_script', 'wpvrSettings',
                                                        array(
                                                        'ajaxurl'=>admin_url('admin-ajax.php'),
                                                        'pluginurl' => plugins_url('/' , __FILE__),                                                       
                                                        'site_url' => site_url(),
                                                         'post_id' => $post ->ID
                                                        ));

                                }
                    }
	}
        
        	function front_css(){
		if(!(is_admin())):
		wp_enqueue_style('wpvr_front_css', plugins_url('/' , __FILE__).'css/style_front.css');
		endif;
	}


        function filter_content($content){
            global $wpdb, $post;
            $post_id = $post ->ID;
             $current_user = wp_get_current_user();
             $user_id = $current_user -> ID;
            $cd = $this->get_vote($post_id, $user_id);
            if(is_single()||is_page()){
                $vote_val = $this->get_vote($post_id, $user_id) ;
                $grade_user_count=$this ->get_vote_count($post_id);
                $grade_av_image = $this -> image_dir . $this ->get_av_image($post_id);

            
               
                    $divs = '';
                    foreach($this ->grades_to_show as $key=>$grade){
                        $src=($cd !== null && $cd == $key)? $this -> image_dir. strtolower($grade). '_color.png': $this -> image_dir. strtolower($grade). '_gray.png';
                        $id = ($cd !== null && $cd == $key)? 'id="user-grade"':" ";
                        $divs .= "<div class='rate-grade-image'><a href='#'><img $id class='val$key' src='$src'/></a></div>";
                    }
                    
                       
                    
                                                        $extra_content=<<<HDS
   <div class='grade-details'><span id='grade-users-count'>$grade_user_count</span> User(s) graded this </div>
   <div class='dotted-margin'>       
    <div class='grade-this-text'>Grade this product ... </div> $divs
        <div class="grade-text-value"></div>
    <div stye="clear:both;"></div>
    
    </div   
                  
                               
HDS;
                    
             
              $content = $extra_content. $content;
                    $redirect = get_permalink($post_id);
                    $login_link = wp_login_url( $redirect );
//                    $content.=<<<HDS
//                        <div style="display:block;background-color:red;margin:5px 0 15px">You need to be logged in to rate the post. 
//                         <a href="$login_link">Login Here</a></div>        
//HDS;
               
                return $content;

            }
            return $content;
        }


		

	
	
function ajax_insert_vote(){
    $rating = $_POST['grade-value'];
    $post_id = $_POST['post_id'];
    $current_user = wp_get_current_user();
    $user_id = $current_user -> ID;
    if($this -> get_vote($post_id,$user_id) !== null){
        $this ->update_vote($post_id, $user_id, $rating);
        $this ->update_av_table($post_id);
        echo json_encode(array( 'action'=> 'updated', 'grade' => $rating ));
    }
    else{
       $this ->add_vote($post_id, $user_id,$rating);
       $this ->update_av_table($post_id);
        echo json_encode(array( 'action' => 'added', 'grade' => $rating ));
    }

    exit;
    
}


//function user_voted($post_id,$user_id){
//    global $wpdb;
//return  $wpdb -> get_var("select grade from $this->table where post_id='$post_id' and user_id='$user_id'");
//    
//}

function get_vote($post_id, $user_id){
    global $wpdb;
  return  $wpdb -> get_var("select grade from $this->table where post_id='$post_id' and user_id='$user_id'");
}


function add_vote($post_id, $user_id, $rating){
        global $wpdb;
        $wpdb -> insert($this ->table, array( 
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

function update_vote($post_id, $user_id, $rating){
    global $wpdb;
    $wpdb -> update( $this->table, array('grade' => $rating), array('post_id' => $post_id, 'user_id' => $user_id) );
//      $meta =get_post_meta($post_id,'wp_vote_meta',true);
//      $this ->delete_vote($post_id, $user_id);
//      $this ->add_vote($post_id, $user_id, $rating);
      
}

function update_av_table($post_id){
    global $wpdb;
    
    $vote_count = $wpdb-> get_var("select count(*) from $this->table where post_id='$post_id'");
    $av_rating = $wpdb -> get_var("select avg(grade) from $this->table where post_id='$post_id'");
    $date = date("Y:m:d H:i:s");
    
    if($this-> get_vote_count($post_id))
        $wpdb-> update( $this->table_av, array('average_grade' => $av_rating, 'grade_count' => $vote_count, 'last_added' => $date), array('post_id' => $post_id) );    
    else
        $wpdb-> insert( $this->table_av, array('post_id'=>$post_id ,'average_grade' => $av_rating, 'grade_count' => $vote_count), array('%d', '%f', '%d') );
        
    
}

function get_av_grade($post_id){
    global $wpdb;
    return $wpdb -> get_var("select average_grade from $this->table_av where post_id='$post_id'");
    
}

function get_vote_count($post_id){
    global $wpdb;
    return $wpdb -> get_var("select grade_count from $this->table_av where post_id='$post_id'");
}

function delete_vote( $post_id, $user_id){
    global $wpdb;
    $wpdb -> query("delete from $this->table where post_id='$post_id' and user_id='$user_id' ");
    
}

function determine_grade($grade){
    if($grade == 0)$val = 'A';
    if($grade > 0 && $grade <= 0.5)$val='A-';
    if($grade > 0.5 && $grade < 1)$val='B+';
    if($grade == 1)$val = 'B';
    if($grade > 1 && $grade <= 1.5)$val='B-';
    if($grade > 1.5 && $grade < 2)$val='C+';
    if($grade == 2)$val = 'C';
    if($grade > 2 && $grade <= 2.5)$val='C-';
    if($grade > 2.5 && $grade < 3)$val='D+';
    if($grade == 3)$val = 'D';
    if($grade > 3 && $grade <= 3.75)$val='D-';
    if($grade == 4)$val = 'F';
    return $val;
    
    
}

function get_av_image($post_id){
    $grade = $this ->determine_grade( $this->get_av_grade($post_id));
    if(strlen($grade) == 1)
        $image = strtolower ($grade).'_all.png';
    else
        $image = ($grade[1] == '+')? strtolower ($grade[0]).'_plus_all.png': strtolower ($grade[0]).'_minus_all.png';
    return $image;
}
    
    function create_table(){
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

        
	function exists_in_table($id){
	global $wpdb;
	//$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
	$result = $wpdb->get_results( "SELECT id FROM $this->table  where series_id='$id'" );
	if(empty($result))
		return false;

	return true;
	}



}