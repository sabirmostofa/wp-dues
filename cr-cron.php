<?php

set_time_limit(600);



//loading files
if(!function_exists('add_action'))
require_once '../../../wp-load.php';


if(!function_exists('wp_create_category'))    
require_once '../../../wp-admin/includes/taxonomy.php';

/*
var_dump(time());
var_dump(wp_next_scheduled('wp_rental_cron'));
exit;
*/

//echo time();
//var_dump(wp_get_schedule('wp_rental_cron'));

//

global $wpMembershipDues, $wpdb;

$wpMembershipDues->update_list($wpMembershipDues->data_url);

//var_dump($all_cities);






