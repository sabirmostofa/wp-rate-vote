<?php
set_time_limit(1700);

require_once '../../../wp-load.php';
$url ='http://www.warriorforum.com/warrior-special-offers-forum/';
 $content = file_get_contents($url);
 $doc = new DOMDocument();
 @$doc->loadHTML($content);
 
 foreach($doc ->getElementsByTagName('a') as $single):
     $id= $single -> getAttribute('id');
     if(stripos( $id,  'thread_title' ) !== false ):
         if(preg_match('/\d+/', $id, $matches))
                 $id_int = $matches[0];
        $list = get_option('wso-imported-ids');
         if(!is_array($list))$list = array();
     if( !in_array($id_int, array(66,313426, 142687, 122868,25379,4740)) && !in_array($id_int, $list) ):
         $title = html_entity_decode($single -> nodeValue);
         $post_content = '<a href="'. $single -> getAttribute('href'). '">'.$single -> getAttribute('href').'</a>';
         $post_image_target = $single -> getAttribute('href');
         $post_image_src = "http://images.shrinktheweb.com/xino.php?stwembed=1&stwaccesskeyid=9690a743e365c89&stwsize=xlg&stwinside=1&stwurl=$post_image_target";
         
         $image_div=<<<TY
         <div class="wsos-post-scr" style="margin:10px auto">
         <a href="$post_image_target"><img src="$post_image_src"/></a>
         </div>
TY;
         $post_content .= $image_div;
            $new_post = array();
            $new_post['post_title'] = $title;
            $new_post['post_author'] = 69;
            $new_post['post_content'] = $post_content;
            $new_post['post_status'] = 'publish';
            $new_post['post_category'] = array(1);
            $new_post_id = wp_insert_post($new_post);
            if($new_post_id){
                $list[]=$id_int;
                update_option('wso-imported-ids',$list);
            }
         
     endif;
         
     endif;
 endforeach;
