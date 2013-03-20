<?php
/*
Plugin Name: MM Product Manager
Plugin URI: http://mediamanifesto.com
Description: Product Management for selling single items easily via paypal
Version: 0.9
Author: Media Manifesto Inc
Author URI: http://www.mediamanifesto.com
*/

include_once('inc/functions.php');

class MM_ProductManager
{
	var $_settings;
    var $_options_pagename = 'mm_pm_options';
    var $_versionnum = 0.9;
    var $location_folder;
	var $menu_page;
	//var $update_name = 'MM_ProductManager/mm_productmanager_plugin.php';
	
	function MM_ProductManager()
	{
		return $this->__construct();
	}
	
    function __construct()
    {
        $this->_settings = get_option('mm_pm_settings') ? get_option('mm_pm_settings') : array();
		$this->location_folder = trailingslashit(WP_PLUGIN_URL) . dirname( plugin_basename(__FILE__) );
        $this->_set_standart_values();       

        add_action( 'admin_menu', array(&$this, 'create_menu_link') );
		date_default_timezone_set(get_option('timezone_string'));
		//add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'check_plugin_updates' ) );
		//add_filter( 'site_transient_update_plugins', array( &$this, 'add_plugin_to_update_notification' ) );
		//add_action( 'admin_init', array( &$this, 'remove_plugin_update_info' ), 11 );
		
		//Scripts & Styles
		add_action( 'wp_print_scripts', array(&$this, 'plugin_js') );
		add_action( 'wp_print_styles', array(&$this, 'plugin_css') );
		
		//Shortcodes
		add_shortcode("MMProductGroup", array(&$this, "MMProductGroup") );
		add_shortcode("MMProduct", array(&$this, "MMProduct") );
		
		//Ajax Posts
		add_action('wp_ajax_nopriv_do_ajax', array(&$this, 'mmpm_save') );
		add_action('wp_ajax_do_ajax', array(&$this, 'mmpm_save') );
			
		//IPN Handling
		add_action( 'wp_loaded', array(&$this, 'mmpm_flush_rules') );
		add_filter( 'rewrite_rules_array', array(&$this, 'mmpm_rwrule') );
		add_filter('query_vars', array(&$this, 'mmpm_filter') );
		add_action('parse_request', array(&$this, 'mmpm_request') );
		add_filter('preprocess_comment', array(&$this, 'mm_comment_post'), 1);
		add_action('admin_bar_menu', array(&$this, 'add_specials_admin_bar_link'),25);
    }
    
	function add_specials_admin_bar_link() {
		global $wp_admin_bar;
		if ( !is_super_admin() || !is_admin_bar_showing() )
			return;
		$admin_page_url = 

		$wp_admin_bar->add_menu( array(
		'id' => 'mmpm_link',
		'title' => __( 'Update Classes'),
		'href' => admin_url(sprintf("options-general.php?page=%s", $this->_options_pagename)),
		) );
	}
    
    function mm_comment_post($comment)
    {
    	if (isset($_POST['bees']))
    	{
    		$bees = $_POST['bees'];
    		$url = $_POST['url'];
    		
    		if ($bees == 'honey' && $url == "")
    		{
    			return $comment;
    		}
    	}
    	
    	wp_die( __('An error has occurred please try again later.', 'MM'));
    }
    
    
    static function mmpm_install() {
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		
		$sql = sprintf("CREATE TABLE IF NOT EXISTS  %s (
			  `intID` int(11) NOT NULL AUTO_INCREMENT,
			  `vcrName` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
			  `vcrDescription` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
			  `intQuantity` int(11) NOT NULL,
			  `decPrice` decimal(10,2) NOT NULL,
			  `intNotifyQuantity` int(11) NOT NULL,
			  `dtmStartDate` datetime NOT NULL,
			  `dtmEndDate` datetime NOT NULL,
			  `tinDeleted` tinyint(1) NOT NULL DEFAULT '0',
			  `vcrUrl` varchar(100) NOT NULL DEFAULT '',
			  `intExternalID` int(11) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`intID`));",
		$wpdb->prefix . "mmpm_product");
		
		dbDelta($sql);
		
		$sql = sprintf("CREATE TABLE IF NOT EXISTS  %s (
				`intID` int(11) NOT NULL AUTO_INCREMENT,
				`intPurchaserID` int NOT NULL,
			  `vcrInvoiceNumber` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
			  `intValid` int NOT NULL DEFAULT '0',
			  `dtmDate` datetime NOT NULL,
			  `vcrJSON` varchar(9999) COLLATE utf8_unicode_ci DEFAULT '',
			  PRIMARY KEY (`intID`));",
		$wpdb->prefix . "mmpm_purchase");
		
		dbDelta($sql);
		
		$sql = sprintf("CREATE TABLE IF NOT EXISTS  %s (
				`intID` int(11) NOT NULL AUTO_INCREMENT,
			  `intPurchaseID` int NOT NULL,
			  `intProductID` int NOT NULL,
			  `intQuantity` int NOT NULL,
			  PRIMARY KEY (`intID`));",
		$wpdb->prefix . "mmpm_lineitem");
		
		dbDelta($sql);
		
		$sql = sprintf("CREATE TABLE IF NOT EXISTS  %s (
				`intID` int(11) NOT NULL AUTO_INCREMENT,
			  `vcrIP` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
			  `vcrAgent` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  `vcrJSON` varchar(9999) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			  PRIMARY KEY (`intID`));",
		$wpdb->prefix . "mmpm_purchaser");
		
		dbDelta($sql);
		
		add_option("mmpm_versionnum", $_versionnum);
	}
    
    function add_settings_link($links) {
		$settings = '<a href="' .
					admin_url(sprintf("options-general.php?page=%s", $this->_options_pagename)) .
					'">' . __('Settings') . '</a>';
		array_unshift( $links, $settings );
		return $links;
	}
	
	function create_menu_link()
    {
        $this->menu_page = add_options_page('MMPM Options', 'MMPM Plugin',
        'manage_options',$this->_options_pagename, array(&$this, 'build_settings_page'));
        add_action( "admin_print_scripts-{$this->menu_page}", array(&$this, 'plugin_page_js') );
        add_action("admin_head-{$this->menu_page}", array(&$this, 'plugin_page_css'));
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'), 10, 2);
    }

    function build_settings_page()
    {
	//add_action( 'wp_print_scripts', array(&$this, 'plugin_js') );
        if (!$this->check_user_capability()) {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }

        if (isset($_REQUEST['saved'])) {if ( $_REQUEST['saved'] ) echo '<div id="message" class="updated fade"><p><strong>'.'MM Product Manager'.' settings saved.</strong></p></div>';}
		if ( isset($_POST['mm_pm_settings_saved']) )
            $this->_save_settings_todb($_POST);
        
		include_once('mm_pm_options.php');
    }

    function plugin_js()
	{
		//wp_deregister_script( 'jquery' );
		//wp_register_script( 'jquery171', $this->location_folder . '/js/jquery-1.7.1.min.js');

		wp_enqueue_script('formtools', $this->location_folder . '/js/formtools.js');
	}
	
	function plugin_css()
	{
?>
         <link rel="stylesheet" href="<?php echo $this->location_folder; ?>/css/formstyles.css" type="text/css" />
<?php
	}

    function plugin_page_js()
    {
    	wp_enqueue_script('bootstrap', $this->location_folder . '/js/bootstrap.min.js');
    	wp_enqueue_script('jquery-ui', $this->location_folder . '/js/jquery-ui.min.js');
    	wp_enqueue_script('plugin-datetime-addon', $this->location_folder . '/js/jquery-ui-timepicker-addon.js');
    	wp_enqueue_script('plugin', $this->location_folder . '/js/plugin.js');
    }

    function plugin_page_css()
    {
?>
         <link rel="stylesheet" href="<?php echo $this->location_folder; ?>/css/bootstrap.min.css" type="text/css" />
<?php
    }

    function check_user_capability()
    {
        if ( is_super_admin() || current_user_can('manage_options') ) return true;

        return false;
    }

    function get_option($setting)
    {
        return $this->_settings[$setting];
    } 
    
    function MMProductGroup($atts, $content = null)
	{
		//add_action( 'wp_print_scripts', array(&$this, 'plugin_js') );
		extract( shortcode_atts( array(
			'description' => 'null'
		), $atts ) );
		
		$Products = GetProductsByDescription($description);
		
		$output = "<div class=\"mm_wrapper\"><div class=\"group-header\">Register for Classes</div>";
		
		if ($Products)
		{
			$output .= "<div class=\"mm-faux-table\">";
			$output .= "<div class=\"mm-hrow\"><div class=\"mm-inline mm-small\">Date</div><div class=\"mm-inline mm-large\">Cost</div><div class=\"mm-inline\">Quantity</div><div class=\"mm-inline\">Subtotal</div><div class=\"mm-inline mm-large\">Register via Paypal</div></div>";

			foreach ($Products as $Product)
			{
				$output .= $this->GenerateProductRow($Product);
			}
			
			$output .= "</div>";
			$output .= '<p class="note">Before making a purchase please read the cancellation policy <a href="http://www.simonsfinefoods.com/cooking-classes/">here</a>.</p>';
		}
		else
		{
			$output .= "<p>There are no upcoming classes scheduled in the new system</p>";
		}
		
		$output .= "</div>";
		
		return $output;
	}
	
	function MMProduct($atts)
	{
		//add_action( 'wp_print_scripts', array(&$this, 'plugin_js') );
		extract( shortcode_atts( array(
			'code' => 'null'
		), $atts ) );
		
		$product = GetProductByName($code);
		
		if (CanSellProduct($product, 1))
		{		
			$url = absolute_to_relative_url(admin_url() . "admin-ajax.php");
			$eDate = strtotime($product->dtmEndDate);
			$EndDate = date("M d", $eDate);
			$Price = $product->decPrice;
			$HiddenPrice = sprintf("<input type=\"hidden\" id=\"mmprice-%s\" value=\"%d\" />", $product->vcrName, $Price);
			$QuantityInput = sprintf("<div class=\"mm-input\"><input onkeyup=\"javascript: updateSubtotal('%s');\" maxlength=\"1\" id=\"mmquant-%s\" type=\"text\" class=\"small nonzero req num\" value=\"0\" /></div>",
								$product->vcrName, $product->vcrName);
			$Subtotal = sprintf("<input id=\"mmsubtotal-%s\" type\"text\" class=\"small\" disabled=\"disabled\" />", $product->vcrName);
			$Action = sprintf("<a href=\"javascript: void(0);\" class=\"buy btn\" onclick=\"javascript: CheckScripts(); doBuy('%s', '%s');\">Register</a></div><div id=\"mmattr-%s\">",
			$product->vcrName, str_replace ("\/", "\/\/", $url),  $product->vcrName);
			
			$output .= sprintf("<form method=\"post\" id=\"mmform-%s\">", $product->vcrName);
			$output .= sprintf("<div class=\"mm-row\">%s<div class=\"mm-inline mm-small\">%s</div><div class=\"mm-inline mm-large\">$%s</div><div class=\"mm-inline\">%s</div><div class=\"mm-inline\">%s</div><div class=\"mm-inline mm-large\">%s</div></div>",
								$HiddenPrice, $EndDate, $Price, $QuantityInput, $Subtotal, $Action);
			$output .= "</form>";
		}
		
		return $this->GenerateProductRow($product);
	}
	
	function GenerateProductRow($product)
	{
		$output = "";
	
		if (CanSellProduct($product, 1))
		{		
			$url = absolute_to_relative_url(admin_url() . "admin-ajax.php");
			$eDate = strtotime($product->dtmEndDate);
			$EndDate = date("M d", $eDate);
			$Price = $product->decPrice;
			$HiddenPrice = sprintf("<input type=\"hidden\" id=\"mmprice-%s\" value=\"%d\" />", $product->vcrName, $Price);
			$QuantityInput = sprintf("<div class=\"mm-input\"><input onkeyup=\"javascript: updateSubtotal('%s');\" maxlength=\"1\" id=\"mmquant-%s\" type=\"text\" class=\"small nonzero req num\" value=\"0\" /></div>",
								$product->vcrName, $product->vcrName);
			$Subtotal = sprintf("<input id=\"mmsubtotal-%s\" type\"text\" class=\"small\" disabled=\"disabled\" />", $product->vcrName);
			$Action = sprintf("<a href=\"javascript: void(0);\" class=\"buy btn\" onclick=\"javascript: CheckScripts(); doBuy('%s', '%s');\">Register</a></div><div id=\"mmattr-%s\">",
			$product->vcrName, str_replace ("\/", "\/\/", $url),  $product->vcrName);
			
			$output .= sprintf("<form method=\"post\" id=\"mmform-%s\">", $product->vcrName);
			$output .= sprintf("<div class=\"mm-row\">%s<div class=\"mm-inline mm-small\">%s</div><div class=\"mm-inline mm-large\">$%s</div><div class=\"mm-inline\">%s</div><div class=\"mm-inline\">%s</div><div class=\"mm-inline mm-large\">%s</div></div>",
								$HiddenPrice, $EndDate, $Price, $QuantityInput, $Subtotal, $Action);
			$output .= "</form>";
		}
		
		return $output;
	}
	
	function mmpm_filter($vars)
	{
		$new_vars = array('mm_ipn');
		$vars = $new_vars + $vars;
		
		return $vars;
	}
	
	function mmpm_request($wp) {
		// only process requests with "my_plugin=paypal"
		if (array_key_exists('mm_ipn', $wp->query_vars)) {
				
				if ( $wp->query_vars['mm_ipn'] == 'paypal') {
				!$this->mmpm_ipn($wp);
			}
		}
		
		//echo  '<div class="debug" style="position: absolute; height: 50px; width: 100px; bottom: 10px; border: 1px solid black;">' . count($wp->query_vars) . '</div>';
		
		//echo "derp";
	}
	
	function mmpm_ipn($wp)
	{
		//echo  '<div class="debug" style="position: absolute; height: 50px; width: 100px; bottom: 10px; border: 1px solid black;">abc123</div>';
		include_once('ipn/ipn.php');
		die;
	}
	
	function mmpm_rwrule( $rules )
	{
		$newrules = array('mm_ipn/paypal' => 'index.php?mm_ipn=paypal');
		//echo $newrules['mmpm_ipn/paypal'];
		return $newrules + $rules;
	}
	
	function mmpm_flush_rules(){
		$rules = get_option( 'rewrite_rules' );
		$output = "true";	
		if ( ! isset( $rules['mm_ipn/paypal	'] ) ) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
		else
		{
			$output = "false";
			//echo $rules['(mm_ipn)/(paypal)'];
		}
		//echo  '<div class="debug" style="position: absolute; height: 50px; width: 100px; bottom: 10px; border: 1px solid black;">' . $output . "</div>";
		//echo "abc123"
	}
	
	function mmpm_save()
	{
		if ($this->check_user_capability())
		{
			switch($_REQUEST['fn']){
				case 'buy':
					$data_back = json_decode (stripslashes($_REQUEST['buy']), true);
					
					$name = $data_back['info'][0]['code'];
					$quantity = $data_back['info'][0]['quant'];
					
					Buy($name, $quantity); //Outputs JSON
				break;
				case 'product':
					
					$data_back = json_decode (stripslashes($_REQUEST['product']), true);
					$pid = 	$data_back['info'][0]['pid'];
					$name = $data_back['info'][0]['name'];
					$desc = $data_back['info'][0]['desc'];
					$price = $data_back['info'][0]['price'];
					$max = $data_back['info'][0]['max'];
					$notify = $data_back['info'][0]['notify'];
					$start = $data_back['info'][0]['start'];
					$end = $data_back['info'][0]['end'];
					$url = $data_back['info'][0]['url'];
					
					if ($pid != -1)
					{
						//do update
						UpdateProduct($pid, $name, $desc, $price, $max, $notify, $start, $end, $url);
					}
					else
					{
						$pid = InsertProduct($name, $desc, $price, $max, $notify, $start, $end, $url);
					}
					
					OutputProductJSON($pid);
				break;
				case 'delete':
					$data_back = json_decode (stripslashes($_REQUEST['delete']), true);
					$id = $data_back['info'][0]['Pid'];
					DeleteProduct($id);
				break;
				case 'fill':
					$data_back = json_decode (stripslashes($_REQUEST['fill']), true);
					$id = $data_back['info'][0]['Pid'];
					FinishProduct($id);
				break;
				case 'get':
					$data_back = json_decode (stripslashes($_REQUEST['get']), true);
					$pid = $data_back['info'][0]['Pid'];
					OutputProductJSON($pid);
				break;
				case 'settings':
					$data_back = json_decode (stripslashes($_REQUEST['settings']), true);
					
					$values = array(
						'mm_pm_paypalaccount' => $data_back['info'][0]['paypal'],
						'mm_pm_notifyemail' => $data_back['info'][0]['nemail'],
						'mm_pm_notifyquantity' => $data_back['info'][0]['nquant'],
						'mm_pm_invoice' => $data_back['info'][0]['invoice'],
						'mm_pm_tax' => $data_back['info'][0]['tax'],
						'mm_pm_currency' => $data_back['info'][0]['currency'],
					);
					
					$this->_save_settings_todb($values);
				break;
				case 'calid':
					$data_back = json_decode (stripslashes($_REQUEST['calid']), true);
					
					echo GetCalendarUrl($_REQUEST['calid']['id']); //Outputs string url
				break;
				default:
					//Derp
				break;
			}
		}
		else
		{
			//If you're not an authorized user you can only buy products
			switch($_REQUEST['fn']){
				case 'buy':
					$data_back = json_decode (stripslashes($_REQUEST['buy']), true);
					
					$name = $data_back['info'][0]['code'];
					$quantity = $data_back['info'][0]['quant'];
					
					Buy($name, $quantity); //Outputs JSON
				break;
				case 'calid':
					$data_back = json_decode (stripslashes($_REQUEST['calid']), true);
					
					echo GetCalendarUrl($_REQUEST['calid']['id']); //Outputs string url
				break;
			}	
		}

		die;
	}
	
	function _save_settings_todb($form_settings = '')
	{
		if ( $form_settings <> '' ) {
			unset($form_settings['mm_pm_settings_saved']);

			$this->_settings = $form_settings;

			#set standart values in case we have empty fields
			$this->_set_standart_values();
		}
		
		update_option('mm_pm_settings', $this->_settings);
	}

	function _set_standart_values()
	{
		global $shortname; 

		$standart_values = array(
			'mm_pm_paypalaccount' => '',
			'mm_pm_notifyemail' => '',
			'mm_pm_notifyquantity' => '',
			'mm_pm_invoice' => '',
			'mm_pm_tax' => '',
			'mm_pm_currency' => '',
		);

		foreach ($standart_values as $key => $value){
			if ( !array_key_exists( $key, $this->_settings ) )
				$this->_settings[$key] = '';
		}

		foreach ($this->_settings as $key => $value) {
			if ( $value == '' ) $this->_settings[$key] = $standart_values[$key];
		}
	}
} // end MM_ProductManager class

register_activation_hook(__FILE__,array('MM_ProductManager', 'mmpm_install'));

add_action( 'init', 'MM_ProductManager_Init', 5 );
function MM_ProductManager_Init()
{
    global $MM_ProductManager;
    $MM_ProductManager = new MM_ProductManager();
}
?>