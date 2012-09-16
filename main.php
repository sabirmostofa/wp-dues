<?php

/*
  Plugin Name: WP-membership-dues
  Plugin URI: http://sabirul-mostofa.blogspot.com
  Description: Membership Dues calculator
  Version: 1.0
  Author: Sabirul Mostofa
  Author URI: mailto:sabirmostofa@gmail.com
 */

$wpMembershipDues = new wpMembershipDues();

class wpMembershipDues {

    public $table = '';
    public $image_dir = '';
    public $prefix = 'wprent';
    public $meta_box = array();

    function __construct() {
        global $wpdb;
        //$this->set_meta();
        $this->table = $wpdb->prefix . 'wb_country_list';
        $this->image_dir = plugins_url('/', __FILE__) . 'images/';
        $this->xml_file = plugins_url('/', __FILE__) . 'countries.xml';
        $this->data_url = 'http://api.worldbank.org/countries?per_page=400';
        //add_action('init', array($this, 'add_post_type'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'front_scripts'));
        add_action('wp_print_styles', array($this, 'front_css'));
        add_action('admin_menu', array($this, 'CreateMenu'), 50);
        add_action('wp_mem_dues_cron', array($this, 'start_cron'));
        add_filter('the_content', array($this, 'generate_content') );
		add_action('wp_ajax_membership_remove', array($this, 'ajax_remove_membership'));
		add_action('wp_ajax_get_dues', array($this, 'ajax_return_data'));
		add_action('wp_ajax_nopriv_get_dues', array($this, 'ajax_return_data'));
        register_activation_hook(__FILE__, array($this, 'create_table'));
        register_activation_hook(__FILE__, array($this, 'init_cron'));
        register_activation_hook(__FILE__, array($this, 'create_page'));
        register_activation_hook(__FILE__, array($this, 'set_memberships'));
        register_deactivation_hook(__FILE__, array($this, 'deactivation_tasks'));
    }

    function CreateMenu() {
        add_submenu_page('options-general.php', 'Dues Settings', 'Dues Settings', 'activate_plugins', 'wpMembershipDues', array($this, 'OptionsPage'));
    }
    
    function create_page(){		
		$page = array(
		'post_type' => 'page',
		'post_content' => '',
		'post_title' => 'Membership Dues',
		'post_author' => 1,
		'post_status' => 'publish'
		
		);
		
		if(!get_option('wb_mem_dues_page_number')){
			$page_no = wp_insert_post($page);
			update_option('wb_mem_dues_page_number', $page_no);
		}
	}
	
	function generate_content($content){
	global $post, $wpdb;
	$mem_page = get_option('wb_mem_dues_page_number');
	if($post->ID != $mem_page)
		return $content;
	
	$mems = get_option('wp_wb_memberships');
	$countries = $wpdb->get_results(
	" select * from $this->table order by country ASC"
	);

/*
	$all_countries = array();
	foreach($countries as $single){
		$key = $single->country_id;
		$all_countries[$key] = $single->country;
	}	
	
	sort($all_countries);
*/
	$extra="<br/>Membership type: <select name='membership_types' id='mem_type'> ";
	foreach($mems as $key=>$val){
		$extra .= "<option value='$key'> {$val[name]} </option>";
	}
	
	$extra .= '</select><br/>';
	$extra .="Select a Country: <select name='country_name' id='wb_country'> ";
	
	foreach($countries as $single){
		$extra .= "<option value='{$single->country_id}'> {$single->country} </option>";
	}
		
	$extra .= "</select><br/><input type='button' id='get-due' value='Submit'/> <br/> <div id='mem_output'></div><br/>";
	return $content.$extra;
		
	
	}
	
	function set_memberships(){
		if(get_option('wp_wb_memberships'))
			return;
	    $names = array('Full', 'Emeritus', 'Early-Career', 'Student');	    
	    $membership_array = array();
	    
	    foreach($names as $key => $value):
			$membership_array[sanitize_title_with_dashes($value)] = array(
					'name' => $value,
					'low_fee' => 0,
					'low_early' => 0,
					'medium_fee' => 0,
					'medium_early' => 0,
					'high_fee' => 0,
					'high_early' => 0
			);
	    endforeach; 
	    
		update_option('wp_wb_memberships', $membership_array);
		
		
		}
	function ajax_remove_membership(){
	$mems = get_option('wp_wb_memberships');
	$key = $_POST['id'];
	unset($mems[$key]);
	echo update_option('wp_wb_memberships', $mems);
	exit;
	}
	
	function ajax_return_data(){
		global $wpdb;
		$mems = get_option('wp_wb_memberships');
		$mem_type = trim($_POST['mem_type']);
		$country = trim($_POST['country']);
		$income_level = $wpdb->get_var( $wpdb->prepare(" select income_level from {$this->table} where country_id = '$country'  ") );
		$country_name = $wpdb->get_var( $wpdb->prepare(" select country from {$this->table} where country_id = '$country'  ") );
		
		$high_array= array( 'NOC', 'OEC', 'UMC');
		
		if(in_array($income_level, $high_array)){ 
			$fee='high_fee';
			$early='high_early';
		}
		elseif($income_level == 'LMC'){
			$fee ='medium_fee';
			$early='medium_early';
		}
		elseif($income_level == 'LIC'){
			$fee ='low_fee';
			$early='low_early';
		}
		
		$to_fee = $mems[$mem_type][$fee];
		
		$mem_name = $mems[$mem_type]['name'];
		
		$early_date = get_option('wp_wb_earlybird_date')?get_option('wp_wb_earlybird_date'): 'now';
		
		if( time() < strtotime($early_date)){
			$to_fee = $mems[$mem_type][$early];
		}	
		
		
		$data = "<b>Membership Type:</b>    $mem_name <br/>";
		$data .= "<b>Country:</b>         $country_name <br/>";
		$data .= "<b>Membership Due(1 year):</b> USD {$to_fee} <br/>";
		

		exit($data);
	}
	
    function OptionsPage() {
        include 'options-page.php';
    }



    function start_cron() {
        include 'cr-cron.php';
    }

    function init_cron() {
        if (!wp_get_schedule('wp_mem_dues_cron'))
            wp_schedule_event(time(), 'daily', 'wp_mem_dues_cron');
    }
    
    function update_list($file = 'countries.xml'){
		global $wpdb;
		//$wpdb->show_errors();
		$dom = new DOMDocument();
		$data = $this->get_content_direct($file);
		if($data[1] != 200 ) return;
		$dom->loadXML ($data[0]);
		$ns='http://www.worldbank.org';
		$num = 0;
		$countries = array();
		foreach($dom -> getElementsByTagNameNS($ns, 'country') as $sample):
			if($sample-> getElementsByTagNameNS($ns, 'incomeLevel')->item(0)->getAttribute('id') == 'NA')
				continue;
			$country_id = trim($sample->getAttribute('id'));
			$country = trim($sample-> getElementsByTagNameNS($ns, 'name')->item(0)-> nodeValue);
			$income = trim($sample-> getElementsByTagNameNS($ns, 'incomeLevel')->item(0) -> getAttribute('id'));
			$income_text = trim($sample-> getElementsByTagNameNS($ns, 'incomeLevel')->item(0) -> nodeValue);
			if(preg_match('/(.*?):/',$income_text, $matches )){
				$income_text = $matches[1];
			}
			
			
			if($this->not_in_table($country_id))
				$wpdb->query(
				$wpdb->prepare("
				insert into $this->table 
				(country_id, country, income_level, income_text) 
				values(%s, %s, %s, %s)", array( $country_id, $country, $income, $income_text )
				) );
			else
				$wpdb->update(
				$this->table,
				array( 
				 'country' => $country,  
				 'income_level' => $income,  
				 'income_text' => $income_text  
				),
				array('country_id' => $country_id),
				array( '%s' , '%s' , '%s' ),
				array('%s')
				
				);

		endforeach;	
	}

    function admin_scripts() {
		wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('datepicker', plugins_url('css/ui-lightness/jquery-ui-1.8.16.custom.css', __FILE__));
        wp_enqueue_script('wbdues_admin_script', plugins_url('/', __FILE__) . 'js/script_admin.js');
        wp_register_style('wbdues_admin_css', plugins_url('/', __FILE__) . 'css/style_admin.css', false, '1.0.0');
        wp_enqueue_style('wbdues_admin_css');

    }

    function front_scripts() {
        global $post;
        if (is_page() || is_single()) {
            wp_enqueue_script('jquery');
            if (!(is_admin())) {
                // wp_enqueue_script('wpvr_boxy_script', plugins_url('/' , __FILE__).'js/boxy/src/javascripts/jquery.boxy.js');
                wp_enqueue_script('wbdues_front_script', plugins_url('/', __FILE__) . 'js/script_front.js');
                wp_localize_script('wbdues_front_script', 'wpvrSettings', array(
                    'ajaxurl' => home_url('/').'wp-admin/admin-ajax.php',
                    'pluginurl' => plugins_url('/', __FILE__),
                 
                ));
            }
        }
    }

    function front_css() {
        if (!(is_admin())):
            wp_enqueue_style('wbdues_front_css', plugins_url('/', __FILE__) . 'css/style_front.css');
        endif;
    }



    function not_in_table($country_id) {
        global $wpdb;
        $var = $wpdb->get_var("select country_id from $this->table where country_id='$country_id'");
        if ($var == null)
            return true;
    }

    function create_table() {
        global $wpdb;
        $sql = "CREATE TABLE IF NOT EXISTS $this->table  (
		`id` int(4) unsigned NOT NULL AUTO_INCREMENT,
		`country_id` varchar(4) NOT NULL,		
		`country` varchar(60)  NOT NULL,	
		`income_level` varchar(6)  NOT NULL,	
		`income_text` varchar(60)  NOT NULL,	
		 PRIMARY KEY (`id`),				 	
		 key `country_id`(`country_id`)		 	
		)";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);



        // Adding primary ccountries to database
		$this -> update_list($this->xml_file);
    }

// end of create_table






    function get_content_direct($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'googlebot');
//    curl_setopt($ch, CURLOPT_PROXY, $ip);
//    curl_setopt($ch, CURLOPT_PROXYPORT, $port);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_ENCODING, '');
//curl_setopt($ch, CURLOPT_HEADER, 1);

        $res = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array($res, $http_status);
    }

 

    function deactivation_tasks() {

        wp_clear_scheduled_hook('wp_rental_cron');
    }

}
