<?php

if(isset($_POST['main-submit'])):
	$_POST = array_map( create_function('$a', 'return trim($a);'), $_POST);
	extract($_POST);	
	update_option( 'grading-settings-var', array( 
	'cache_time' => $cache_time,
        'week_count' => $week_count,
        'month_count' => $month_count
            ));

        endif;
        if( get_option('grading-settings-var') )extract( get_option('grading-settings-var'));
        $cache_time = (isset($cache_time))? $cache_time: 1800;
        $week_count = (isset($week_count))? $week_count: 10;
        $month_count = (isset($month_count))? $month_count: 10;
?>

<div class="wrap">
    <form action ='' method='post'>
         <h4>General Settings</h4>
 Image Cache Time(In Seconds, Default is 30 mins, 30*60 = 1800 )
  <br/>
 <input style="width:40%" type='text' name='cache_time' value="<?php echo $cache_time ?>"/>
 <br/>
 <br/>
 Posts to show in the "WSO'S Of the week" page(Default 10)
  <br/>
 <input style="width:40%" type='text' name='week_count' value="<?php echo $week_count ?>"/>
 <br/>
 <br/>
 Posts to show in the "WSO'S Of the month" page(Default 10)
  <br/>
 <input style="width:40%" type='text' name='month_count' value="<?php echo $month_count ?>"/>
 <br/>
 <br/>
  <input class='button-primary' type='submit' name="main-submit" value='Submit'/> 
    </form>
</div>
