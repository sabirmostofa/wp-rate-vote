<?php

if(isset($_POST['main-submit'])):
	$_POST = array_map( create_function('$a', 'return trim($a);'), $_POST);
	extract($_POST);	
	update_option( 'grading-settings-var', array( 
	'cache_time' => $cache_time
            ));

        endif;
        if( get_option('grading-settings-var') )extract( get_option('grading-settings-var'));
        $cache_time = (isset($cache_time))? $cache_time: 1800;
?>

<div class="wrap">
    <form action ='' method='post'>
         <h4>General Settings</h4>
 Image Cache Time(In Seconds, Default is 30 mins, 30*60 = 1800 )
  <br/>
 <input style="width:40%" type='text' name='cache_time' value="<?php echo $cache_time ?>"/>
 <br/>
 <br/>
  <input class='button-primary' type='submit' name="main-submit" value='Submit'/> 
    </form>
</div>
