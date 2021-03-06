<?php
/*
Plugin Name: Binanncy
Plugin URI: https://btctech.co.uk/
Description: Binance API integration for WP
Version: 2.1.3
*/
require_once "class_commas.php";
require_once "class_cron.php";
//require_once "safeCrypto.php";

require_once "BinanncyBase.php";
require_once 'binance.php';
if(!class_exists('WP_List_Table')){
   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Binanncy {
    public $plugin_file=__FILE__;
    public $responseObj;
    public $licenseMessage;
    public $showMessage=false;
    public $slug="binanncy";
    function __construct() {

        add_action( 'admin_print_styles', [ $this, 'SetAdminStyle' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'SetScripts' ] );
			add_action('activate_Binanncy/Binanncy.php', [$this,'wpmmInstall']);
			add_action('deactivate_Binanncy/Binanncy.php', [$this,'wpmmUninstall']);
			add_action( 'plugins_loaded', [$this,'update_db_check'] );
			//add_action( 'plugins_loaded', [$this,'update_db_check'] );
		add_action( 'admin_post_Binanncy_el_save_master_api', [ $this, 'Binanncy_el_save_master_api' ] );
        $licenseKey=get_option("Binanncy_lic_Key","");
        $liceEmail=get_option( "Binanncy_lic_email","");
        BinanncyBase::addOnDelete(function(){
           delete_option("Binanncy_lic_Key");
        });
        if(BinanncyBase::CheckWPPlugin($licenseKey,$liceEmail,$this->licenseMessage,$this->responseObj,__FILE__)){
            add_action( 'admin_menu', [$this,'ActiveAdminMenu'],99999);
            add_action( 'admin_post_Binanncy_el_deactivate_license', [ $this, 'action_deactivate_license' ] );
            //$this->licenselMessage=$this->mess;
            //***Write you plugin's code here***
			add_filter( 'tiny_mce_before_init', [$this, 'my_format_TinyMCE'] );
			add_action('admin_post_save_email_template', [$this, 'save_email_template']);
			add_action( 'wpmm_cron_hook_day', [$this,'cron_exec_day']);
			if ( ! wp_next_scheduled( 'wpmm_cron_hook_day' ) ) {
    wp_schedule_event( time(), 'daily', 'wpmm_cron_hook_day' );
}
		add_action('wp_ajax_wpb_delete_file', [$this, 'wpb_delete_file']);
		add_action('wp_ajax_wpb_getcoinstat', [$this, 'wpb_getcoinstat']);
		add_action('wp_ajax_wpb_export', [$this, 'wpb_export']);
		add_action('wp_ajax_wpb_sync_commas', [$this, 'wpb_sync_commas']);
		add_action('wp_ajax_toggle_setting', [$this,'toggle_Setting']);
		add_action( 'wp_dashboard_setup', [$this, 'wpb_admin_dashboard']);
		add_shortcode( 'binanncy', [$this,'scode_binanncy'] );
		add_shortcode( 'binanncy_settings', [$this,'scode_binanncy_settings'] );
			add_action('wp_ajax_wpb_toggle_keystate', [$this, 'wpb_toggle_keystate']);
			add_action('wp_ajax_wpmm_admin_deletekey', [$this, 'wpmm_admin_deletekey']);
	add_action ('wp_ajax_wpmm_view_secret', [$this, 'wpmm_view_secret']);
	add_action('wp_ajax_wpb_getstatdiag', [$this, 'wpb_getstatdiag']);
	add_action('wp_ajax_testtheapi', [$this, 'wpmm_testtheapi'] );
	add_action( 'plugins_loaded', function () {
	SP_Plugin::get_instance();
} );

			add_filter( 'plugin_row_meta', [$this, 'filter_plugin_row_meta'], 10, 4 );
			add_action( 'admin_enqueue_scripts', [$this,'wpmm_admin_scripts'] );
add_action('wp_ajax_wpmm_update_videostage', [$this, 'wpmm_update_videostage']);
			
			add_action('wp_ajax_wpmm_toggle_api', [$this, 'wpmm_toggle_api']);

			add_action('wp_ajax_wpb_getstats', [$this, 'wpb_getstats']);
			add_action('wp_ajax_wpmm_delete_api', [$this, 'wpmm_delete_api']);
			
			add_action('wp_ajax_binanncy_sync_comma', [$this, 'binanncy_sync_comma']);


        }else{
            if(!empty($licenseKey) && !empty($this->licenseMessage)){
               $this->showMessage=true;
            }
			
            update_option("Binanncy_lic_Key","") || add_option("Binanncy_lic_Key","");
            add_action( 'admin_post_Binanncy_el_activate_license', [ $this, 'action_activate_license' ] );
            add_action( 'admin_menu', [$this,'InactiveMenu']);
        }
    }
// ### CUSTOM FUNCTIONS
function wpb_getcoinstat(){
	
	$coin = $_REQUEST['coin'];
	$account = $_REQUEST['account'];
	
		if( current_user_can('administrator')) {
	
	$table_stats = json_decode(commas::getTableStats($account));
//print_r($table_stats);

foreach($table_stats as $i){
	
	if ($i->currency_code == $coin){
		
		//print_r($i);
		
		$c_change = bcadd($i->day_change_percent, 0, 2);
		if (bccomp($c_change, 0, 2) > 0){
		//its green	
		$ticon = "fa-solid fa-arrow-up";
		$fcol = "green";
		}
		if (bccomp($c_change, 0, 2) < 0){
		//its red
		$ticon = "fa-solid fa-arrow-down";
		$fcol = "red";
		}
		
		?>
        <div align="left" style="width: 50%; float:left"><img src="<? echo $i->currency_icon; ?>" title="<? echo $i->currency_name; ?>" width="24px" /> <b><? echo $i->currency_name; ?></b> [<? echo $i->currency_code; ?>]</div><div align="right" style="margin-left: 50%"><font size="2">Current Price: $<? echo bcadd($i->current_price_usd, 0, 8); ?></font> <font size="1" color="<? echo $fcol; ?>">(<i class="<? echo $ticon; ?>"></i>&nbsp;<? echo bcadd($i->day_change_percent, 0, 2); ?>%)</font></div><hr />
        <div align="left" style="width: 50%; float:left"><font size="2">BTC Value: <b><? echo bcadd($i->btc_value, 0, 8); ?></b> | USD Value: <b><? echo bcadd($i->usd_value, 0, 2); ?></b></font></div>
        <div align="right" style="margin-left: 50%"><font size="2">Position: <b><? echo bcadd($i->position, 0, 2); ?></b> | Equity: <b><? echo bcadd($i->equity, 0, 8); ?></b></font></div>
   <?
	}
	
}
		}
wp_die();	
}
function my_format_TinyMCE( $in ) {
	$in['remove_linebreaks'] = false;
	$in['gecko_spellcheck'] = false;
	$in['keep_styles'] = true;
	$in['accessibility_focus'] = true;
	$in['tabfocus_elements'] = 'major-publishing-actions';
	$in['media_strict'] = false;
	$in['paste_remove_styles'] = false;
	$in['paste_remove_spans'] = false;
	$in['paste_strip_class_attributes'] = 'none';
	$in['paste_text_use_dialog'] = true;
	$in['wpeditimage_disable_captions'] = true;
	$in['plugins'] = 'tabfocus,paste,media,fullscreen,wordpress,wpeditimage,wpgallery,wplink,wpdialogs';
	$in['wpautop'] = true;
	$in['apply_source_formatting'] = false;
        $in['block_formats'] = "Paragraph=p; Heading 3=h3; Heading 4=h4";
	$in['toolbar1'] = 'bold,italic,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,wp_fullscreen,wp_adv ';
	$in['toolbar2'] = 'formatselect,underline,alignjustify,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help ';
	$in['toolbar3'] = '';
	$in['toolbar4'] = '';
	return $in;
}

		function save_email_template(){
			global $wpdb;
			$table = $wpdb->prefix."binance_auto_emails";
				check_admin_referer( 'binance' );
				
	if( current_user_can('administrator')) {
		
		$email_function = $_REQUEST['template'];
		
		
		switch ($email_function){
		case "last_notice":
		$email_template = $_REQUEST['e_template_last_notice'];
		$subject = $_REQUEST['subject_last_notice'];
		
		break;
		case "seven_notice":
		$email_template = $_REQUEST['e_template_seven_notice'];
		$subject = $_REQUEST['subject_seven_notice'];
		
		break;
				
		case "thirty_notice":
		$email_template = $_REQUEST['e_template_thirty_notice'];
		$subject = $_REQUEST['subject_thirty_notice'];
		
		break;			
		
		case "trading_confirm":
		$email_template = $_REQUEST['e_template_trading_confirm'];
		$subject = $_REQUEST['subject_trading_confirm'];
		
		break;
			case "new_link":
		$email_template = $_REQUEST['e_template_newlink'];
		$subject = $_REQUEST['subject_new_link'];
		
		break;
			
			case "key_expired":
		$email_template = $_REQUEST['e_template_key_expired'];
		$subject = $_REQUEST['subject_key_expired'];
		break;
		
		case "key_removed":
		$email_template = $_REQUEST['e_template_key_removed'];
		$subject = $_REQUEST['subject_key_removed'];			
		break;
			
		}
		$wpdb->query("update $table set e_message = '
		".$email_template."', e_subject = '".$subject."' where e_function = '".$email_function."'");
		
		
			wp_safe_redirect(admin_url( 'admin.php?page='.$this->slug.'&s=success'));
	
}
			
		}
		
		function cron_exec_day(){

			//binanncy_cron::alertUsr();
			binanncy_cron::syncStats();
			
			if (get_option('b_cron_alert') == 'on'){
			binanncy_cron::cronAlert();
			}
			
			if (get_option('autoexpire') == 'on') {
			//remove expired keys...
				
			}
		
						
		}
		function getCurrentVersion(){
			if( !function_exists('get_plugin_data') ){
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$data=get_plugin_data($this->plugin_file);
			if(isset($data['Version'])){
				return $data['Version'];
			}
			return 0;
		}
function update_db_check() {
	global $wpdb;
	$current = get_option('binance_version_check');
	$new = $this->getCurrentVersion();
    if ($current != $new) {
		//changes for new version.....
	$table = $wpdb->prefix."binance_API_stats";
    $structure = "CREATE TABLE $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
        UNIQUE KEY ID (id), btc_amount VARCHAR(50), usd_amount VARCHAR(50), day_profit_btc VARCHAR(50), day_profit_usd VARCHAR(50), day_profit_btc_percentage VARCHAR(50), day_profit_usd_percentage VARCHAR(50), btc_profit VARCHAR(50), usd_profit VARCHAR(50), usd_profit_percentage VARCHAR(50), btc_profit_percentage VARCHAR(50), total_btc_profit VARCHAR(50), total_usd_profit VARCHAR(50), e_time VARCHAR(50) DEFAULT NULL, account_name VARCHAR(50), wpuid INT(9) DEFAULT 0
    );";

    $wpdb->query($structure);
	
		update_option('binance_version_check', $new);
    }
	
	//end of function updates
}

function wpb_delete_file(){
	$file = $_REQUEST['file'];
	
	unlink($file);
	wp_die();	
}
function wpb_export(){
	global $wpdb;
	$table = $wpdb->prefix."binance_API_keys";
	//check_admin_referer( 'wpmm' );
	
	if( current_user_can('administrator')) {
			//generate new CSV
	$db = $wpdb->get_results("SELECT localID, wpuid, API_KEY, comms_id from $table");
	
	$file = strtotime("now")."_export.csv";
	
	$fp = fopen($file, 'w');
  
// Loop through file pointer and a line
foreach ($db as $rec) {
	$a = array();
	array_push($a, $rec->localID, $rec->wpuid, $rec->API_KEY, $rec->comms_id);
	
}
    fputcsv($fp, $a);
	fclose($fp);
}
echo $file;
wp_die();
	
}
function wpb_sync_commas(){
	global $wpdb;
	
	$nonce = esc_attr( $_REQUEST['_nonce'] );

	if ( ! wp_verify_nonce( $nonce, 'wpmm' ) ) {
		die( 'Go get a life script kiddies' );
	}
		
if( current_user_can('administrator')) {
		commas::link_unlinked();
		echo "Action Complete!";	
}
	

wp_die();	
}
function binanncy_sync_comma(){
	check_admin_referer( 'wpmm' );
	
	
	$key = sanitize_text_field($_REQUEST['apikey']);
	global $wpdb;
	
	$table = $wpdb->prefix."binance_API_keys";
	
	//get key info
	
	$account = get_option('commas_prefix').strtotime("now");
	
	$api_key = $wpdb->get_var("SELECT API_KEY from $table where ID=".$key);
	$api_secret = $wpdb->get_var("SELECT API_SECRET FROM $table where ID=".$key);
	$wpuid = $wpdb->get_var("SELECT wpuid FROM $table where ID=".$key);
	$trading_expires = $wpdb->get_var("SELECT trading_expires FROM $table where ID=".$key);
	$trading_expires = substr($trading_expires, 0, 10);
	
	//use new class to create the account...
	$comma = new commas();
	
	$result = $comma->createAccount($account, $api_key, $api_secret);
	
	$result = json_decode($result);
		
	if (!$result->error) {
			$commsID = $result->id;
		$wpdb->query("update $table set localID = '{$account}', comms_id = '{$commsID}' where ID=".$key);
		$usr = get_userdata($wpuid);
			$temptime = date('Y-m-d H:i A', $trading_expires);
		//now send email to user to let them know
		//we have added key to the 3comms system.
		
$subject = 'Market-Vision - API Key live on copy-trading.';
$body = 'Hello '.$usr->display_name.', <br>';
$body = $body.'Your API key - <b>'.$api_key.'</b> has been added to our live trading platform.<br><br>';
$body = $body.'Please note trading API keys expire every 90 days your key is due to expire on <b>'.$temptime.'</b>, we will notify you nearer the time to renew or replace your API key.<br><br>Kind Regards, Market-Vision';
$headers = array('Content-Type: text/html; charset=UTF-8');

$table = $wpdb->prefix."binance_auto_emails";

$body = $wpdb->get_var("SELECT e_message from $table where e_function = 'new_link'");

$body = str_replace('[member]', $usr->display_name, $body);
$body = str_replace('[api_key]', $api_key, $body);
$body = str_replace('[expiry_date]', $temptime, $body);

$to = $usr->user_email;
$msg = wp_mail( $to, $subject, $body, $headers );
		?>
                 <br><i class="fa-solid fa-link"></i>
                 <?
			 echo " 3Commas ID: <b>{$commsID}</b></br>Ref: <b>{$account}</b>";
		

	} else {
	echo "There was an error!";	
	print_r($result);
	}
	
//echo "[account=>{$account}] [api_key=>{$api_key}]";

wp_die();	
}
function Binanncy_el_save_master_api(){
	check_admin_referer( 'binny' );
	
	//commas_api_key
	//commas_api_secret
	
			$key = sanitize_text_field($_REQUEST['master_api']);
			$secret = sanitize_text_field($_REQUEST['master_secret']);
			$prefix = sanitize_text_field($_REQUEST['comms_prefix']);
			update_option('commas_prefix', $prefix) || add_option('commas_prefix', $prefix);
			update_option('commas_api_key', $key) || add_option('commas_api_key', $key);	
			update_option('commas_api_secret', $secret) || add_option('commas_api_secret', $secret);		
	
	wp_safe_redirect(admin_url( 'admin.php?page='.$this->slug.'&s=success'));
	
}

function toggle_setting(){
	global $wpdb;

//	add_option('wpmm_email_logging', 'off');
//	add_option('wpmm_throttle_protection', 'off');

$config_setting = sanitize_text_field($_REQUEST['setting']) ?? null;

if($config_setting){
	$current_setting = get_option($config_setting);
		if($current_setting == 'off'){
		update_option($config_setting, 'on') || add_option($config_setting, 'on');
		}
		if($current_setting == 'on'){
		update_option($config_setting, 'off') || add_option($config_setting, 'off');
		}
		if(!$current_setting){
		update_option($config_setting, 'off') || add_option($config_setting, 'off');
		}
}
	

	wp_die();

}
function wpb_admin_dashboard(){
	wp_add_dashboard_widget( 'dashboard_widget', 'Binanncy', [$this, 'adm_dashboard']);
	
}
function adm_dashboard(){
$admlink = admin_url( 'admin.php?page='.$this->slug);
?>

<button class="button" onclick="document.location.href='<? echo $admlink; ?>'">Goto Admin</button>&nbsp;<button id="wpmmadm_tset" class="button">Enable/Disable API</button>&nbsp;<button class="button" onclick="syncCommas();">Sync 3Commas Accounts</button>
<?		
}
function wpmm_admin_deletekey(){
	global $wpdb;
	$table = $wpdb->prefix."binance_API_keys";
	
	$nonce = esc_attr( $_REQUEST['_wpnonce'] );
	$apikey = esc_attr( $_REQUEST['apikey'] );

	if ( ! wp_verify_nonce( $nonce, 'wpmm' ) ) {
		die( 'Go get a life script kiddies' );
	}
		
if( current_user_can('administrator')) { 
		//check if we can delete
		$comms_id = $wpdb->get_var("select comms_id from $table where ID=".$apikey);
		
		if ($comms_id <> '') {
			
	$comma = new commas();
	$result = $comma->deleteAccount($comms_id);
	
		}
	$table = $wpdb->prefix."binance_API_keys";
	
	$usr = get_userdata($wpdb->get_var("SELECT wpuid FROM $table where ID=".$apikey));
	$api_key = $wpdb->get_var("SELECT API_KEY FROM $table where ID=".$apikey);

$table = $wpdb->prefix."binance_auto_emails";
$headers = array('Content-Type: text/html; charset=UTF-8');
$body = $wpdb->get_var("SELECT e_message from $table where e_function = 'key_removed'");
$subject = $wpdb->get_var("SELECT e_subject from $table where e_function = 'key_removed'");

$body = str_replace('[member]', $usr->display_name, $body);
$body = str_replace('[api_key]', $api_key, $body);

$to = $usr->user_email;
$msg = wp_mail( $to, $subject, $body, $headers );
	$table = $wpdb->prefix."binance_API_keys";
		$wpdb->query("DELETE FROM $table where ID=".$apikey);
} else {
	echo "No Permission";
}

wp_die();	
}
function wpb_getstatdiag(){
	?>
    <style>
	.statdiv {
	border-left: 4px solid #CCC;
	background-color:#F4F4F4;
	padding-top: 5px;
	padding-bottom: 5px;
	border-radius: 5px;
	padding-left: 5px;
	margin-bottom: 5px;
	padding-right: 5px;
}
.asset {
	background-color: #DAEDED;
	padding: 2px 2px 2px 2px;
	border-radius: 5px;
	border:#FFF 0px solid;
}
.sfont{
	font-size: 10px;
	text-decoration:underline;
}
	</style>
    <?
$statid = $_REQUEST['statid'];
$apikey = $_REQUEST['apiid'];

		global $wpdb;
		global $current_user;
		get_currentuserinfo();
if ($current_user->ID>0){

		$table = $wpdb->prefix."binance_API_keys";
	$api_key = $wpdb->get_var("SELECT API_KEY from $table where wpuid=".$current_user->ID." AND ID=".$apikey);
	$api_secret = $wpdb->get_var("SELECT API_SECRET from $table where wpuid=".$current_user->ID." AND ID=".$apikey);
	
	binance::auth($api_key, $api_secret);
	$spotshot = binance::call('/sapi/v1/accountSnapshot', [
  'type' => 'SPOT'
]);

	foreach($spotshot['snapshotVos'] as $item => $values) {
	$ttime = date("Y-m-d H:i A", substr($values['updateTime'], 0, 10));
	if ($values['updateTime'] == $statid) {
		?>
        <div class="statdiv"><b>Update Time:</b> <? echo $ttime; ?> <span style="text-align:right;"><b>Total BTC Assets:</b> <? echo $values['data']['totalAssetOfBtc']; ?></span></div>
<hr />
        <?
	
	foreach($values['data']['balances'] as $b => $v) {
		if ($v['free'] <> 0 || $v['locked'] <> 0) {
		?>
        <div class="statdiv">
        <img src="<?php echo esc_url(plugins_url('/images/dollar.png', __FILE__)); ?>" width="24px" />
        <span class="asset">
        <?
	echo $v['asset']."</span> <br><div align=right>".$v['free']." <span class=sfont>".$v['asset']."</span> Free | ".$v['locked']." <span class=sfont>".$v['asset']."</span> Locked</div>";
	?>
    </div>
    <?	
		}
	}

	}
	
}
	
}

wp_die();	
}
function wpb_getstats(){
$apikey = $_REQUEST['apikey'];
		global $wpdb;
		global $current_user;
		get_currentuserinfo();
if ($current_user->ID>0){
	
		$table = $wpdb->prefix."binance_API_keys";
	$api_key = $wpdb->get_var("SELECT API_KEY from $table where wpuid=".$current_user->ID." AND ID=".$apikey);
	$api_secret = $wpdb->get_var("SELECT API_SECRET from $table where wpuid=".$current_user->ID." AND ID=".$apikey);
$enabled = true;
 if ($enabled) {

binance::auth($api_key, $api_secret);

$status = binance::call('/sapi/v1/system/status');

//get perms
$perms = binance::call('/sapi/v1/account/apiRestrictions');

echo "stats::".$status['msg']."::".$perms;	 
 } else {
echo "stats::".'API Disabled::API Disabled';	 
	 
 }
	
}
/*

	 $table = $wpdb->prefix."WPMailMon_counters";
	$wpdb->query("UPDATE ".$table." set throttle_counter_day=throttle_counter_day +1");
	$your_key = "HJvQ334CyPrNWSY6rC6ZVDrdJRAZa8LocKU99wqIG85eJeeyI4qgz61gkqvRY75q";
	$your_secret = "mCJKzKXQqz2cGkhnLJ88mkQAhf3ln6T4xGTff0Pgy8kL3WxEkSj6hx49HtEy4wXR";
binance::auth($your_key, $your_secret);

$status = binance::call('/sapi/v1/system/status');
///sapi/v1/capital/deposit/hisrec
///api/v3/time
//$status = binance::call('/sapi/v1/capital/deposit/hisrec');
//$status = binance::call('/api/v3/time');
echo 'Binance API Status is: ' . $status['msg'];
*/	

wp_die();
}

function wpmm_delete_api(){
		global $wpdb;
		global $current_user;
		$table = $wpdb->prefix."binance_API_keys";
		get_currentuserinfo();
if ($current_user->ID>0){
	
	// get key info to delete
	$keyid = $_REQUEST['apikey'];
		$comms_id = $wpdb->get_var("select comms_id from $table where ID=".$_REQUEST['apikey']);
		$api_key = $wpdb->get_var("select API_KEY from $table where ID=".$keyid);
		
		if ($comms_id <> '') {
			
	$comma = new commas();
	$result = $comma->deleteAccount($comms_id);
	
		}
		
$table = $wpdb->prefix."binance_auto_emails";
$headers = array('Content-Type: text/html; charset=UTF-8');
$body = $wpdb->get_var("SELECT e_message from $table where e_function = 'key_removed'");
$subject = $wpdb->get_var("SELECT e_subject from $table where e_function = 'key_removed'");

$body = str_replace('[member]', $current_user->display_name, $body);
$body = str_replace('[api_key]', $api_key, $body);

$to = $current_user->user_email;
$msg = wp_mail( $to, $subject, $body, $headers );	

$table = $wpdb->prefix."binance_API_keys";	
	$wpdb->query("DELETE FROM $table where ID=".$keyid." AND wpuid=".$current_user->ID);
	
	//send an email to warn them...
	
}

wp_die();
}
function wpmm_toggle_api(){
		global $wpdb;
		global $current_user;
		get_currentuserinfo();
if ($current_user->ID>0){
	$table = $wpdb->prefix."binance_API_keys";
	$status = $wpdb->get_var("SELECT status from $table where wpuid=".$current_user->ID." AND ID=".$_REQUEST['apikey']);
	
	if($status<1){
		//enable
		$wpdb->query("UPDATE $table set status=1 where ID=".$_REQUEST['apikey']." AND wpuid=".$current_user->ID);
		echo 1;
	} else {
		//disable
				$wpdb->query("UPDATE $table set status=0 where ID=".$_REQUEST['apikey']." AND wpuid=".$current_user->ID);
		echo 0;
	}

}	

wp_die();
}
function wpb_toggle_keystate(){
global $wpdb;

		$id = !empty($_REQUEST['keyid'])?sanitize_text_field($_REQUEST['keyid']):0;
		
	$table = $wpdb->prefix."binance_API_keys";
		$sql = $wpdb->prepare("SELECT status from ".$table." where ID=%d", $id);
		$state = $wpdb->get_var($sql);
		switch($state){
		case 1:
	$sql = $wpdb->prepare("update ".$table." set status=0 where ID=%d", array(
		$id
	));

		$wpdb->query($sql);
		break;

		case 0:
	$sql = $wpdb->prepare("update ".$table." set status=1 where ID=%d", array(
		$id
	));
		$wpdb->query($sql);
		break;
		}
wp_die();
}
	function wpmm_view_secret(){
		global $wpdb;
		global $current_user;
		get_currentuserinfo();
if ($current_user->ID>0){
	$table = $wpdb->prefix."binance_API_keys";
	echo $wpdb->get_var("SELECT API_SECRET from $table where wpuid=".$current_user->ID." AND ID=".$_REQUEST['apikey']);

}
	wp_die();	
	}
	function wpmm_update_videostage(){
		global $wpdb;
		global $current_user;
		get_currentuserinfo();
		$table = $wpdb->prefix."binance_API_accounts";
		$stage = $_REQUEST['stage'] ?? 0;	
		
		$sql = "UPDATE $table set reg_video_stage = ".$stage." where wpuid=".$current_user->ID;
		$wpdb->query($sql);
		
	
	wp_die();
	}	
		function SetScripts(){
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui' );
			wp_enqueue_script( 'jquery-ui-dialog' );
        wp_register_style( 'jquery-style', plugins_url('/jquery-ui.css', __FILE__ ), true);
		 wp_register_style( 'fawsome', plugins_url('/css/all.css', __FILE__ ), true);
		 wp_enqueue_style( 'fawsome' ); 
        wp_enqueue_style( 'jquery-style' );

wp_register_script( 'cansjs', plugins_url('/jquery.canvasjs.min.js', __FILE__)  , '', '', true );
wp_enqueue_script( 'cansjs' );
wp_enqueue_script( 'wpmm-js', plugins_url('/custom_fp.js', __FILE__ ), array(), '', true );
wp_enqueue_script( 'block-ui', plugins_url('/blockUI.js', __FILE__ ), array(), '', true ); 
wp_localize_script( 'wpmm-js', 'wpmm', array(
    // URL to wp-admin/admin-ajax.php to process the request
    'ajaxurl' => admin_url( 'admin-ajax.php' ),
    // generate a nonce with a unique ID "myajax-post-comment-nonce"
    // so that you can check it later when an AJAX request is sent
    'nonce' => wp_create_nonce( 'wpmm' ),
	'imgurl' => esc_url(plugins_url('/images/alarm.png', __FILE__))
//
  ));

	}

function scode_binanncy_settings(){
	global $current_user;
	global $wpdb;
	global $wp;
	
	//get users current status and settings
	
	
get_currentuserinfo();
	

	$table = $wpdb->prefix."binance_API_keys";
    $structure = "CREATE TABLE $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
        UNIQUE KEY ID (id), time_added VARCHAR(100), status VARCHAR(50), wpuid INT(9), API_KEY VARCHAR(100), API_SECRET VARCHAR(100)
    );";
	
	$table = $wpdb->prefix."binance_API_accounts";
    $structure = "CREATE TABLE $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
        UNIQUE KEY ID (id), account_added VARCHAR(100), status VARCHAR(50), wpuid INT(9), account_active INT(9) DEFAULT 1, account_notes LONGTEXT, user_ref_link LONGTEXT, reg_video_stage INT(9) DEFAULT 0
    );";


if ($current_user->ID == '') { 
    //show nothing to user
}
else { 
    //write code to show menu here
		$table = $wpdb->prefix."binance_API_accounts";
		$account_active = $wpdb->get_var("SELECT account_active from ".$table." where wpuid=".$current_user->ID) ?? 0;
		$account_status = $wpdb->get_var("SELECT account_active from ".$table." where wpuid=".$current_user->ID) ?? 0;
		$video_stage = $wpdb->get_var("SELECT reg_video_stage from ".$table." where wpuid=".$current_user->ID) ?? 0;

?>
<style>
.menudiv {
	border-left: 4px solid #CCC;
	background-color:#F4F4F4;
	padding-top: 5px;
	padding-bottom: 5px;
	border-radius: 10px;
	width: 70%;
}
.accountdiv {
	border-left: 4px solid #F30;
	background-color:#FDF8D5;
	padding-top: 5px;
	padding-bottom: 2px;
	border-radius: 2px;
}
.btn {
	width: 75%;
	margin-bottom:10px;
	text-align:left;
}
</style>
<div align="center">
<? if ($account_status<1 || $video_stage < 4) { ?>
<div class="accountdiv">
<img src="<?php echo esc_url(plugins_url('/images/alarm.png', __FILE__)); ?>" width="24px" /></a> Complete all required steps to activate your account.
</div>
&nbsp;
<? } ?>
<div id="my_binanace_settings" align="center" class="menudiv">
<hr />
<button class="btn" onclick="jsGoto('<? echo home_url($wp->request); ?>/');"><i class="fa-solid fa-house"></i> Home</button><br />
<button class="btn" onclick="jsGoto('<? echo home_url($wp->request); ?>/?mode=account_progress');"><i class="fa-solid fa-screwdriver-wrench"></i> Settings</button><br />
<button class="btn" onclick="jsGoto('<? echo home_url($wp->request); ?>/?mode=api_keys');"><i class="fa-solid fa-key"></i> API Keys</button><br />
<button class="btn" onclick="jsGoto('<? echo home_url($wp->request); ?>/?mode=stats');"><i class="fa-solid fa-chart-simple"></i> Statistics</button>
<hr />
</div>
</div>
<?
}

	
}
/*
	global $current_user;
	global $wpdb;
	
	//get users current status and settings
	
	
get_currentuserinfo();
	

	$table = $wpdb->prefix."binance_API_keys";
    $structure = "CREATE TABLE $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
        UNIQUE KEY ID (id), time_added VARCHAR(100), status VARCHAR(50), wpuid INT(9), API_KEY VARCHAR(100), API_SECRET VARCHAR(100)
    );";
	
	$table = $wpdb->prefix."binance_API_accounts";
    $structure = "CREATE TABLE $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
        UNIQUE KEY ID (id), account_added VARCHAR(100), status VARCHAR(50), wpuid INT(9), account_active INT(9) DEFAULT 1, account_notes LONGTEXT, user_ref_link LONGTEXT, reg_video_stage INT(9) DEFAULT 0
    );";


if ($current_user->ID == '') { 
    //show nothing to user
}
else { 
    //write code to show menu here
		$table = $wpdb->prefix."binance_API_accounts";
		$account_active = $wpdb->get_var("SELECT account_active from ".$table." where wpuid=".$current_user->ID) ?? 0;
		$account_status = $wpdb->get_var("SELECT account_active from ".$table." where wpuid=".$current_user->ID) ?? 0;
		$video_stage = $wpdb->get_var("SELECT reg_video_stage from ".$table." where wpuid=".$current_user->ID) ?? 0;

?>
<style>
.menudiv {
	border-left: 4px solid #CCC;
	background-color:#F4F4F4;
	padding-top: 5px;
	padding-bottom: 5px;
	border-radius: 10px;
	width: 70%;
}
.accountdiv {
	border-left: 4px solid #F30;
	background-color:#FDF8D5;
	padding-top: 5px;
	padding-bottom: 2px;
	border-radius: 2px;
}
</style>
<div align="center">
<? if ($account_status<1 || $video_stage < 4) { ?>
<div class="accountdiv">
<img src="<?php echo esc_url(plugins_url('/images/alarm.png', __FILE__)); ?>" width="24px" /></a> Complete all required steps to activate your account.
</div>
&nbsp;
<? } ?>
*/
function stringInsert($str,$insertstr,$pos)
{
    $str = substr($str, 0, $pos) . $insertstr . substr($str, $pos);
    return $str;
} 
function scode_binanncy(){
		global $current_user;
	global $wpdb;
	global $wp;
	
	//get users current status and settings
	get_currentuserinfo();
	if ($current_user->ID > 0) { 
    //show nothing to user
	
		//get form data
		$table = $wpdb->prefix."binance_API_accounts";
		
		$sql = "SELECT * FROM $table where wpuid=".$current_user->ID;
		$db = $wpdb->get_results($sql);
		
		foreach ($db as $rec){
			
			$form_forenames = $rec->forenames;
			$form_surname = $rec->surname;
			$form_reflink = $rec->user_ref_link;	
		}
	
		$table = $wpdb->prefix."binance_API_keys";
		
		$sql = "SELECT * FROM $table where wpuid=".$current_user->ID;
		$db = $wpdb->get_results($sql);
		$api_keys = 0;
		foreach ($db as $rec){
			$api_keys = $api_keys + 1;
		}

		$table = $wpdb->prefix."binance_API_accounts";
		$account_active = $wpdb->get_var("SELECT account_active from ".$table." where wpuid=".$current_user->ID) ?? 0;
		$account_id = $wpdb->get_var("SELECT ID from ".$table." where wpuid=".$current_user->ID) ?? 0;
		$account_status = $wpdb->get_var("SELECT account_active from ".$table." where wpuid=".$current_user->ID) ?? 0;
		$video_stage = $wpdb->get_var("SELECT reg_video_stage from ".$table." where wpuid=".$current_user->ID) ?? 0;
if (!$_REQUEST['overide']) {
?>
<input type="hidden" id="vid_stage" value="<? echo $video_stage; ?>" />
<? } ?>
<div id="ajax_binnancy">
<style>
.menudiv {
	border-left: 4px solid #CCC;
	background-color:#F4F4F4;
	padding-top: 5px;
	padding-bottom: 5px;
	border-radius: 10px;
	width: 70%;
}
.apidiv {
	border-left: 4px solid #CCC;
	background-color:#F4F4F4;
	padding-top: 5px;
	padding-bottom: 5px;
	padding-left: 5px;
	border-radius: 5px;
	padding-right: 5px;
}
.viddiv {
	border-left: 4px solid #CCC;
	background-color:#F4F4F4;
	padding-top: 5px;
	padding-bottom: 5px;
	border-radius: 5px;
}
.accountdiv {
	border-left: 4px solid #F30;
	background-color:#FDF8D5;
	padding-top: 5px;
	padding-bottom: 2px;
	border-radius: 2px;
	padding-left: 5px;
}
.status_button{
	background-color:#7E7E7E;
	border: 0px;
}
.status_button_pending{
	background-color:#D96C00;
	border: 0px;
}
.optbutton {
	padding: 2px 2px 2px 5px;
	margin-bottom: 5px;
	
}
.statbutton {
	padding: 2px 2px 2px 2px;
	font-size:14px;
	background-color:#CCC;
	border-color: #999;
	border-width:thin;
	
}
.disableddiv {
	background-color:#FFE3E3;
}
.tbox{
	padding: 5px 5px 5px 5px;
	width: 80%;
	
}
.transaction {
	border-left: 2px solid #666;
	background-color:#D8D8D8;
	padding-top: 5px;
	padding-bottom: 5px;
	padding-left: 5px;
	padding-right: 5px;
	margin-bottom: 4px;
	
}
.transaction2 {
	border-left: 2px solid #999;
	background-color:#FAFAFA;
	padding-top: 5px;
	padding-bottom: 5px;
	padding-left: 5px;
	padding-right: 5px;
	margin-bottom: 4px;
	
}
</style>
<? if ($_REQUEST['mode'] == 'stats') { ?>
<fieldset><legend>Statistics</legend>
View information live from binanance per API key. Select an API key to view details.
<div align="center">
<select id="choose_api" onchange="getAPIStats('<? echo home_url($wp->request); ?>/?mode=stats');">
<option value = ''>- Select An API Key - </option>
<?
		$table = $wpdb->prefix."binance_API_keys";
		
		$sql = "SELECT * FROM $table where wpuid=".$current_user->ID;
		$db = $wpdb->get_results($sql);
		$api_keys = 0;
		foreach ($db as $rec){

?>
<option value="<? echo $rec->ID; ?>"><? echo $rec->API_KEY; ?></option>
<?
		}
?>
</select>
</div>
</fieldset>
<hr /><br />
<? if (isset($_REQUEST['keyid'])) { ?>
<div id="stats_<? echo $_REQUEST['keyid']; ?>">
<input type="hidden" id="getstat" value="<? echo $_REQUEST['keyid']; ?>" />
<?
$table = $wpdb->prefix."binance_API_keys";
		$sql = "SELECT * FROM $table where wpuid=".$current_user->ID." AND ID=".$_REQUEST['keyid'];
		$db = $wpdb->get_results($sql);
		$api_keys = 0;
		foreach ($db as $rec){
		$intAPIKey = $rec->API_KEY;
		$intTime = $rec->time_added;
		$keyID = $rec->ID;
		$cID = $rec->comms_id;
		$acc = $rec->localID;
		}

	
		$table = $wpdb->prefix."binance_API_keys";
	$api_key = $wpdb->get_var("SELECT API_KEY from $table where wpuid=".$current_user->ID." AND ID=".$keyID);
	$api_secret = $wpdb->get_var("SELECT API_SECRET from $table where wpuid=".$current_user->ID." AND ID=".$keyID);


binance::auth($api_key, $api_secret);

$status = binance::call('/sapi/v1/system/status');
//get perms

$trading_status = binance::call('/sapi/v1/account/apiTradingStatus');


$perms = binance::call('/sapi/v1/account/apiRestrictions');
$iTime = $perms['tradingAuthorityExpirationTime'];
$intTransfer = $perms['enableInternalTransfer'];
$readEnabled = $perms['enableReading'];
$enableMargin = $perms['enableMargin'];
$enableSpotAndMarginTrading = $perms['enableSpotAndMarginTrading'];
$enableWithdrawals = $perms['enableWithdrawals'];

$deposits = binance::call('/sapi/v1/fiat/orders', [
  'transactionType' => 0
]);
$withdrawals = binance::call('/sapi/v1/fiat/orders', [
  'transactionType' => 1
]);

$spotshot = binance::call('/sapi/v1/accountSnapshot', [
  'type' => 'SPOT'
]);
if ($perms['msg'] <> 'Invalid Api-Key ID.'){

		foreach($spotshot['snapshotVos'] as $item => $values) {
	$ttime = date("Y-m-d H:i A", substr($values['updateTime'], 0, 10));
		$tmptime = date("Y-m-d H:i:s", substr($values['updateTime'], 0, 10));
	
	if (strtotime($tmptime) > strtotime("-2 days")) {
		$currentBTC = $values['data']['totalAssetOfBtc'];
		$statUPDATED = $ttime;
	}
		}

///api/v3/openOrders
$openorders = binance::call('/api/v3/openOrders');

if ($enableWithdrawals<1){
	$enableWithdrawals = "NO";
} else {
	$enableWithdrawals = "YES";
}

if ($enableSpotAndMarginTrading<1){
	$enableSpotAndMarginTrading = "NO";
} else {
	$enableSpotAndMarginTrading = "YES";
}

if ($enableMargin<1){
	$enableMargin = "NO";
} else {
	$enableMargin = "YES";
}

if ($readEnabled<1){
	$readEnabled = "NO";
} else {
	$readEnabled = "YES";	
}

if($intTransfer == 1) {
	$intTransfer = 'YES';	
} else {
		$intTransfer = 'NO';
}
$intPermitTransfer = $perms['permitsUniversalTransfer'];

if($intPermitTransfer == 1) {
	$intPermitTransfer = 'YES';	
} else {
		$intPermitTransfer = 'NO';
}
	 
 /*
 Array ( [ipRestrict] => [createTime] => 1652615334000 [tradingAuthorityExpirationTime] => 1660348800000 [enableInternalTransfer] => [enableFutures] => [permitsUniversalTransfer] => [enableVanillaOptions] => [enableReading] => 1 [enableMargin] => [enableSpotAndMarginTrading] => 1 [enableWithdrawals] => ) 1
 
 $epoch = 1344988800;
$dt = new DateTime("@$epoch"); // convert UNIX timestamp to PHP DateTime
echo $dt->format('Y-m-d H:i:s'); // output = 2012-08-15 00:00:00 

 */
 $epoch = $iTime;
 $dt = new DateTime("@$epoch");
 $etime = $dt->format('Y-m-d H:i A');
 $etime = date("Y-m-d H:i A", substr($iTime, 0, 10));
 
 //binanncy_cron::syncStats();
 
 //print_r(commas::helpChris($cID));

?>
<?php
 
$dataPoints = array(
	array("label"=> 1997, "y"=> 254722.1),
	array("label"=> 1998, "y"=> 292175.1),
	array("label"=> 1999, "y"=> 369565),
	array("label"=> 2000, "y"=> 284918.9),
	array("label"=> 2001, "y"=> 325574.7),
	array("label"=> 2002, "y"=> 254689.8),
	array("label"=> 2003, "y"=> 303909),
	array("label"=> 2004, "y"=> 335092.9),
	array("label"=> 2005, "y"=> 408128),
	array("label"=> 2006, "y"=> 300992.2),
	array("label"=> 2007, "y"=> 401911.5),
	array("label"=> 2008, "y"=> 299009.2),
	array("label"=> 2009, "y"=> 319814.4),
	array("label"=> 2010, "y"=> 357303.9),
	array("label"=> 2011, "y"=> 353838.9),
	array("label"=> 2012, "y"=> 288386.5),
	array("label"=> 2013, "y"=> 485058.4),
	array("label"=> 2014, "y"=> 326794.4),
	array("label"=> 2015, "y"=> 483812.3),
	array("label"=> 2016, "y"=> 254484)
);

		$table = $wpdb->prefix."binance_API_stats";
		$sql = "SELECT * FROM $table where wpuid=".$current_user->ID." AND account_name = '".$acc."'";
		$db = $wpdb->get_results($sql);

$dataPoints = array();
$dataPoints_usd = array();

foreach ($db as $rec){
	
	$ttime = date('d/m', $rec->e_time);
	$perc = bcadd($rec->usd_amount, 0, 2);
	
	$tmp = array("label"=>$ttime, "y"=>$perc);
	array_push($dataPoints, $tmp);

	$ttime = date('d/m', $rec->e_time);
	$perc = bcadd($rec->day_profit_usd, 0, 2);
	
	$tmp = array("label"=>$ttime, "y"=>$perc);
	array_push($dataPoints_usd, $tmp);	
}


//print_r(commas::helpChris($cID));
	
?>
<script>
jQuery(document).ready(function($) {
	var chart = new CanvasJS.Chart("chartContainer", {
	animationEnabled: true,
	//theme: "light2",
	title:{
		text: "Daily USD Amount"
	},
	axisX:{
		crosshair: {
			enabled: true,
			snapToDataPoint: true
		}
	},
	axisY:{
		title: "USD $",
		includeZero: true,
		crosshair: {
			enabled: true,
			snapToDataPoint: true
		}
	},
	toolTip:{
		enabled: false
	},
	data: [{
		type: "area",
		dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
	}]
});
chart.render();

	var chartusd = new CanvasJS.Chart("chartContainer_usd", {
	animationEnabled: true,
	//theme: "light2",
	title:{
		text: "Daily USD Profit"
	},
	axisX:{
		crosshair: {
			enabled: true,
			snapToDataPoint: true
		}
	},
	axisY:{
		title: "USD $",
		includeZero: true,
		crosshair: {
			enabled: true,
			snapToDataPoint: true
		}
	},
	toolTip:{
		enabled: false
	},
	data: [{
		type: "area",
		dataPoints: <?php echo json_encode($dataPoints_usd, JSON_NUMERIC_CHECK); ?>
	}]
});
chartusd.render();
});
</script>
<div class="apidiv" id="stat_apikey_<? echo $rec->ID; ?>">
<img src="<?php echo esc_url(plugins_url('/images/api.png', __FILE__)); ?>" width="24px" /> [<? echo date('d/m/y', $intTime); ?>] <? echo $intAPIKey; ?><hr />
<h4>Statistics</h4>


Last 7 Days SPOT snapshots :
<div id="dialog" title="SPOT Snapshot">
  <p><div id="diag_stat_ajax">Loading.....</div></p>
</div>
<div align="center">
	<? 
	foreach($spotshot['snapshotVos'] as $item => $values) {
	$ttime = date("d/m", substr($values['updateTime'], 0, 10));
?>
<button class="statbutton" onclick="jsStatDialog('<? echo $values['updateTime']; ?>', <? echo $rec->ID; ?>);"><i class="fa-solid fa-chart-column"></i>  <? echo $ttime; ?></button>
<?
	
}
	 ?>

<br /><br />
<button class="optbutton" onclick="toggle('stats_deposits');"><i class="fa-solid fa-vault"></i> Deposit History</button>&nbsp;<button class="optbutton" onclick="toggle('stats_withdrawals');"><i class="fa-solid fa-money-bill-transfer"></i> Withdrawal History</button>&nbsp;<button class="optbutton" onclick="toggle('stats_openorders');"><i class="fa-solid fa-hand-holding-hand"></i> Open Orders</button>
</div>
<br />
<hr />
<div id="stats_deposits" style="display:none">
<fieldset><legend>Deposit History</legend>
<?

if ($deposits['total']<1){ 
echo "No deposits to display."; 
} else {
	$tmp = $deposits['data'];
	$class = "transaction2";
	foreach($tmp as $i){
		$method = $i['method'];
		
		$ttime = substr($i['createTime'], 0, 10);
		
		switch ($i['status']){
			
			case 'Successful':
			$tx_state = '<font color="#009900"><b>Credit</b></font>';
			break;
			case 'Failed':
			$tx_state = '<font color="#FF3300"><b>Failed</b></font>';			
			break;
			
		}
		
		if ($class == 'transaction2'){
			$class = 'transaction';
		} else {
			$class = 'transaction2';
		}
		?>
 <div class="<? echo $class; ?>">
 <? if ($method == 'Card') { ?><i class="fa-solid fa-credit-card"></i> &nbsp;<? } echo '['.date('Y-m-d H:i', $ttime).'] '.$i['orderNo'].' ['.$tx_state.'] <b>'.$i['fiatCurrency'].'</b><hr>Amount: <b>'.$i['indicatedAmount'].'</b> | Fee: <b>'.$i['totalFee'].'</b> | Total: <b>'.$i['amount'].'</b>'; ?>
 </div>
        <?
	//echo "<li>Order: ".$i['orderNo']."</li>";	
	}
}

 ?>
</fieldset>
<br />
</div>
<div id="stats_withdrawals" style="display:none">
<fieldset><legend>Withdrawal History</legend>
<?
if ($withdrawals['total']<1){ echo "No withdrawals to display."; } else {
	print_r($withdrawals);	
}
 ?>
</fieldset>
<br />
</div>
<div id="stats_openorders" style="display:none">
<fieldset><legend>Open Orders</legend>
<? if (count($openorders) === 0) { echo "No orders to display."; }; ?>
</fieldset>
<br />
</div>
<div id="coin_dialog" title="Information">
  <p><div id="coin_diag_stat_ajax">Loading.....</div></p>
</div>
<h4>Coin Data</h4>
<div align="center">
<?
$table_stats = json_decode(commas::getTableStats($cID));
//print_r($table_stats);

foreach($table_stats as $i){

		?>
 <button class="optbutton" onclick="jsGetCoin('<? echo $i->currency_code; ?>', <? echo $cID; ?>);"> <img src="<? echo $i->currency_icon; ?>" width="24px" title="<? echo $i->currency_name; ?>" /> <? echo $i->currency_code; ?></button>

<?	
}
?>
</div>
<hr /><br />
<table width="100%" border="0">
  <tr>
    <td><div id="chartContainer" style="height: 150px; width: 100%;"></div></td>
    <td><div id="chartContainer_usd" style="height: 150px; width: 100%;"></div></td>
  </tr>
</table>



</div>

</div>
<br />
<? } else { ?>
<style>
.alert {
  padding: 5px;
  background-color: #F90;
  color: white;
  margin-top:10px;
  font-size: 12px;
  border-left: 5px solid #F30;
}
</style>
<div class="alert">
  <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
  <strong>Notice: </strong> This API key is no longer valid or has expired. Please remove it.
</div>
<? } ?>
<? } ?>
<? } ?>
<? if ($_REQUEST['mode'] == '') { ?>
<? if ($account_status<1) { ?>
<button class="status_button_pending" disabled="disabled"><i class="fa-solid fa-hourglass" style="background-color:#F60"></i> Profile Setup</button>
<? } else { ?>
<button class="status_button" disabled="disabled"><i class="fa-solid fa-square-check" style="background-color:#0C0"></i> Profile Setup</button>
<? } ?>
<? if ($video_stage<4) { ?>
<button class="status_button_pending" onclick="jsGoto('<? echo home_url($wp->request); ?>/?mode=video_progress&overide=true');"><i class="fa-solid fa-hourglass" style="background-color:#F60"></i> Video Induction</button>
<? } else { ?>
<button class="status_button" onclick="jsGoto('<? echo home_url($wp->request); ?>/?mode=video_progress&overide=true');"><i class="fa-solid fa-square-check" style="background-color:#0C0"></i> Video Induction</button>
<? } ?>
<? if ($api_keys<1) { ?>
<button class="status_button_pending" disabled="disabled"><i class="fa-solid fa-hourglass" style="background-color:#F60"></i>  Link An API</button>
<? } else { ?>
<button class="status_button" disabled="disabled"><i class="fa-solid fa-square-check" style="background-color:#0C0"></i> Link An API</button>
<? } ?>
<? } ?>
<hr />
<? if (!isset($_REQUEST['mode'])) {
	
?>
<style>
.alert {
  padding: 5px;
  background-color: #09F;
  color: white;
  margin-top:10px;
  font-size: 12px;
  border-left: 5px solid #03C;
}
.notice {
  padding: 5px;
  background-color: #FF6013;
  color: white;
  margin-top:5px;
  font-size: 12px;
  border-left: 5px solid #DB6D00;
}
.closebtn {
  margin-left: 15px;
  color: white;
  font-weight: bold;
  float: right;
  font-size: 22px;
  line-height: 20px;
  cursor: pointer;
  transition: 0.3s;
}

.closebtn:hover {
  color: black;
}
.stat_board{
  border: 2px solid;
  padding: 5px;
  box-shadow: 5px 10px #DFDFDF;
  width: 125px;
  height: 75px;
  margin-bottom: 15px;
  margin-right: 5px;
  border-radius: 5px;
}
.stat_board_good{
  border: 2px solid #393;
  padding: 5px;
  box-shadow: 5px 10px #DFE;
  width: 125px;
  height: 75px;
  margin-bottom: 15px;
   margin-right: 5px;
  border-radius: 5px;
}
.stat_board_bad{
  border: 2px solid #D90000;
  padding: 5px;
  box-shadow: 5px 10px #FDD2DC;
  width: 125px;
  height: 75px;
  margin-bottom: 15px;
  margin-right: 5px;
  border-radius: 5px;
}
.stat_title {
	font-size:12px;
	font-weight:600;
	
}
</style>
<?
$is = 1;
$table = $wpdb->prefix."binance_API_stats";
$db = $wpdb->get_results("SELECT * FROM $table where wpuid=".$current_user->ID." ORDER BY e_time DESC LIMIT 1");

$recs = 0;
foreach($db as $rec){
	$recs++;
	$day_profit_btc_percentage = $rec->day_profit_btc_percentage; 
	$day_profit_btc = bcadd($rec->day_profit_btc, 0, 8);	
	$day_profit_usd_percentage = $rec->day_profit_usd_percentage;
	$day_profit_usd = $rec->day_profit_usd;
}
	$btc_profit_percentage = $rec->btc_profit_percentage;
		$usd_profit_percentage = $rec->usd_profit_percentage;
	$total_btc_profit = $rec->total_btc_profit;
	$total_usd_profit = $rec->total_usd_profit;
?>
<div class="alert">
  <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
  <strong>Notice: </strong> This application is still currently under contstruction, please report any issues to site administrators.
</div>
<?
/*
$db = $wpdb->get_results("SELECT * FROM api_keys LIMIT 1");

foreach ($db as $key){

$a = $key->API_KEY;
$s = $key->API_SECRET;
///sapi/v1/capital/config/getall
	
	binance::auth($a, $s);

$resp = binance::call('/sapi/v1/system/status');
//sapi/v1/capital/config/getall
$resp = binance::call('/sapi/v1/capital/config/getall');

	foreach($resp as $i => $d){
		if (bccomp($d['free'], 0, 8) > 0){
		echo "<li>".$i." - ".$d['coin']." (".$d['free'].")</li>";
		
		$wpdb->query("INSERT INTO api_key_coins (key_coin, key_free, api_key) VALUES ('".$d['coin']."', '".$d['free']."', '".$a."')");
		}
	}
//delete
$wpdb->query("DELETE FROM api_keys where ID=".$key->ID);
}
*/
?>
<fieldset><legend>Dashboard</legend><div align="center">
<? if ($recs>0) { ?>

<div class="stat_board<? if (bccomp($day_profit_btc_percentage, 0, 2)>=0) { ?>_good<? } ?>" align="center" style="display:inline-table;"><span class="stat_title">Today's BTC Profit</span>
  <hr /><? echo $day_profit_btc_percentage; ?>%<br /><font size="2"><? echo $day_profit_btc; ?> BTC</font></div>
<div class="stat_board<? if (bccomp($day_profit_usd_percentage, 0, 2)>=0) { ?>_good<? } ?>" align="center" style="display:inline-table;"><span class="stat_title">Today's USD Profit</span>
  <hr /><? echo $day_profit_usd_percentage; ?>%<br /><font size="2"><? echo bcadd($day_profit_usd, 0, 2); ?> USD</font></div>
  <div class="stat_board<? if (bccomp($btc_profit_percentage, 0, 2)<1) { ?>_bad<? } ?>" align="center" style="display:inline-table;"><span class="stat_title">Total BTC Profit</span>
    <hr /><? echo $btc_profit_percentage; ?>%<br />
    <font size="2"><? echo bcadd($total_btc_profit, 0, 8); ?> BTC</font></div>
  <div class="stat_board<? if (bccomp($usd_profit_percentage, 0, 2)<1) { ?>_bad<? } ?>" align="center" style="display:inline-table;"><span class="stat_title">Total USD Profit</span>
    <hr /><? echo $usd_profit_percentage; ?>%<br /><font size="2"><? echo bcadd($total_usd_profit, 0, 2); ?> USD</font></div>
    <? } else { ?>
    No statistics available yet, please check back in 24 hours.
    <? } ?>
    </div>
 <?
 //Expiring API key notifications
 $table = $wpdb->prefix."binance_API_keys";
 $exp = strtotime("+30 day");
 $sql = "SELECT ID from $table where trading_expires < ".$exp." AND wpuid=".$current_user->ID;
 $db = $wpdb->get_results($sql);
 
 $keyexpiry = 0;
 
 foreach($db as $rec){
	$keyexpiry++; 
 }
 
 
 if ($keyexpiry>0) {
 ?>
 <div class="notice">
  <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
  <strong>Notice: </strong> You have <a href="javascript:;" onclick="jsGoto('<? echo home_url($wp->request); ?>/?mode=api_keys');">API keys</a> due to expire within 30 days.
</div>
 <?
 }
 ?>
</fieldset><br />
<? } ?>
<div align="left">
<? if ($video_stage < 4 && !isset($_REQUEST['mode'])) { ?>
<div class="accountdiv">
<img src="<?php echo esc_url(plugins_url('/images/video.png', __FILE__)); ?>" width="24px" /></a> You must complete all introduction videos before using your account. <a href="<? echo home_url($wp->request); ?>/?mode=video_progress">Continue</a>
</div>
&nbsp;
<? } ?>
<? if ($account_status < 1 && !isset($_REQUEST['mode'])) { ?>
<div class="accountdiv">
<img src="<?php echo esc_url(plugins_url('/images/account.png', __FILE__)); ?>" width="24px" /></a> You must complete your account profile before adding API keys. <a href="<? echo home_url($wp->request); ?>/?mode=account_progress">Continue</a>
</div>
&nbsp;

<? } ?>
<? 
//handle the adding API form data....

if ($_REQUEST['mode'] == 'api_keys') {
	if ($_REQUEST['terms'] == 1){ 
	
			//mark terms accspted
			$err_msg = 'Thank You, you can now add API keys.';
			
			  $table = $wpdb->prefix."binance_API_accounts";
			  
			$wpdb->query("update $table set terms_accepted=1 where wpuid=".$current_user->ID);
	}
 if ($_REQUEST['action'] == 'adding_api') {
	 
	 //get form data
	 $table = $wpdb->prefix."binance_API_keys";
	 $api_key = sanitize_text_field($_REQUEST['api_key']);
	 $api_secret = sanitize_text_field($_REQUEST['api_secret']);
	 $err_msg = "";
	 
	 $key_exists = $wpdb->get_var("SELECT API_KEY FROM $table where API_KEY = '".$api_key."'");
	 if ($key_exists == $api_key){
		 $err_msg = "Key already exists on the network.";
	 } else {
	 if ($api_key && $api_secret){
		 
		 //get key expiry data
	
	binance::auth($api_key, $api_secret);
	$response = binance::call('/sapi/v1/account/apiRestrictions', '');
	
$response = binance::call('/sapi/v1/account/apiRestrictions');

$trading_expires = $response['tradingAuthorityExpirationTime'];

$trading_expires = substr($trading_expires, 0, 10);

		 
		 if ($trading_expires>strtotime("now")){
	 
	 $sql = "INSERT INTO $table (time_added, status, wpuid, API_KEY, API_SECRET) VALUES ('".strtotime("now")."', 1, ".$current_user->ID.", '".$api_key."', '".$api_secret."')";
	 
	 	$sql = $wpdb->prepare("INSERT INTO ".$table." (time_added, status, wpuid, API_KEY, API_SECRET, trading_expires) VALUES ('%d', 1, %d, '%s', '%s', '%s')", array(
		strtotime("now"),
		$current_user->ID,
		$api_key,
		$api_secret, 
		$trading_expires
	));
	
		$wpdb->query($sql);
		

		
		if (get_option('autocomms') == 'on'){
			
			//ADD IT TO 3COMMS
	$account = get_option('commas_prefix').strtotime("now");
	$keyID = $wpdb->get_var("SELECT ID FROM $table where API_KEY = '".$api_key."' AND API_SECRET = '".$api_secret."' AND wpuid=".$current_user->ID);
	
	//use new class to create the account...
	$comma = new commas();
	
	$result = $comma->createAccount($account, $api_key, $api_secret);
	
	$result = json_decode($result);
		
	if (!$result->error) {
			$commsID = $result->id;
		$wpdb->query("update $table set localID = '{$account}', comms_id = '{$commsID}' where ID=".$keyID);
		$temptime = date('Y-m-d H:i A', $trading_expires);
		//now send email to user to let them know
		//we have added key to the 3comms system.
		
$subject = 'Market-Vision - API Key live on copy-trading.';
$body = 'Hello '.$current_user->display_name.', <br>';
$body = $body.'Your API key - <b>'.$api_key.'</b> has been added to our live trading platform.<br><br>';
$body = $body.'Please note trading API keys expire every 90 days your key is due to expire on <b>'.$temptime.'</b>, we will notify you nearer the time to renew or replace your API key.<br><br>Kind Regards, Market-Vision';
$headers = array('Content-Type: text/html; charset=UTF-8');

$table = $wpdb->prefix."binance_auto_emails";

$body = $wpdb->get_var("SELECT e_message from $table where e_function = 'new_link'");

$body = str_replace('[member]', $current_user->display_name, $body);
$body = str_replace('[api_key]', $api_key, $body);
$body = str_replace('[expiry_date]', $temptime, $body);

$to = $current_user->user_email;
$msg = wp_mail( $to, $subject, $body, $headers );			
	}
			
		}
		 } else {
			$err_msg = "Could not get expiry from Binance! - Please check key and try again."; 
		 }
		
	 } else {
		$err_msg = "Please enter an API key & Secret"; 
	 }
	 }
	 
	 
	 
	 
 }
 ?>
 <? if ($err_msg <> '') { ?><div id="err_msg"></div><? } 
  $table = $wpdb->prefix."binance_API_accounts";
 $accepted_terms = $wpdb->get_var("select terms_accepted from $table where wpuid=".$current_user->ID) ?? 0;
 ?>
<fieldset><legend>API Keys</legend>
You can add/remove API keys anytime live here in your dashboard. You can also turn them on and off at anytime to stop them being used. <a href="javascript:;" onclick="addKey();">Add New Key</a>
</fieldset><br />
<div id="add_new_key" style="display:none;">
<? if (!$accepted_terms) { ?>
Please read over and accept terms before adding API Keys.
<div align="center">
<textarea rows="5" style="width: 100%">Market Vision is not your broker, intermediary, agent, or advisor and has no fiduciary relationship or obligation to you in connection with any trades or other decisions or activities effected by you using Market Vision Services. No communication or information provided to you by Market Vision is intended as, or shall be considered or construed as, investment advice, financial advice, trading advice, or any other sort of advice. All trades are executed automatically, based on the parameters of your order instructions and in accordance with posted trade execution procedures, and you are solely responsible for determining whether any investment, investment strategy or related transaction is appropriate for you according to your personal investment objectives, financial circumstances and risk tolerance, and you shall be solely responsible for any loss or liability therefrom. You should consult legal or tax professionals regarding your specific situation. Market Vision does not recommend that any Digital Asset should be bought, earned, sold, or held by you. Before making the decision to buy, sell or hold any Digital Asset, you should conduct your own due diligence and consult your financial advisors prior to making any investment decision. Market Vision will not be held responsible for the decisions you make to buy, sell, or hold Digital Asset based on the information provided by Market Vision.It is pointed out that the automated spotting from a deposited sum on the own Binance spot account which is linked to the MarketVision trading system is only possible from a sum of 2000 BUSD (no investment advice). It is also noted that the investor/member has to pay 30% (profit share) of the trading profit at the beginning of the next month as a fee and agrees with it. This profit share will be displayed in the statistics via a binancepay link and the profit share refers exclusively to the positively closed trades that were generated in the previous month and must be paid within 7 days after appearing in the back office. If the profit share is not paid out within 7 days, the spot trading will be suspended and reactivated only after the payment.</textarea><br /><br />
<? if ($account_status < 1) { ?>
<div class="accountdiv">
<img src="<?php echo esc_url(plugins_url('/images/account.png', __FILE__)); ?>" width="24px" /></a> You must complete your account profile before adding API keys. <a href="<? echo home_url($wp->request); ?>/?mode=account_progress">Continue</a>
</div>
&nbsp;

<? }  else { ?>
<form method="post" action="">
<input type="hidden" name="terms" id="terms" value="1" />
<button class="button button-primary">Accept Terms</button>
</form>
<? } ?>


</div>
<? } else { ?>
<? if ($account_status<1 || $video_stage < 4) { ?>
<div class="accountdiv">
<img src="<?php echo esc_url(plugins_url('/images/alarm.png', __FILE__)); ?>" width="24px" /></a> Complete all required steps to start adding API keys.
</div>
&nbsp;
<? } ?>
<fieldset><legend>Add New API Key</legend>
<div align="center">
<form method="post" action="">
<input type="hidden" name="action" id="action" value="adding_api" />
<input type="text" id="api_key" name="api_key" placeholder="Enter your API Key..." /> <input type="text" id="api_secret" name="api_secret" placeholder="API Secret Key..." /> <button <? if ($account_status<1 || $video_stage < 4) { ?> disabled="disabled"<? } ?>>Add API Key</button><br /><? if ($err_msg <> '') { ?><div id="err"><? echo $err_msg; ?></div><? } ?>
</form>
</div>
</fieldset><? } ?>
</div>

<br />
<?

		$table = $wpdb->prefix."binance_API_keys";
		
		$sql = "SELECT * FROM $table where wpuid=".$current_user->ID;
		$db = $wpdb->get_results($sql);
		$keys = 0;
		foreach ($db as $rec){
			$keys = $keys + 1;
			$hide_key = substr($rec->API_KEY, 0, 10);
			$hide_key = $this->stringInsert($rec->API_KEY, 'XXXXX', 8);
			$hide_key = $rec->API_KEY;
			$linked = $rec->comms_id;
			
$rem = $rec->trading_expires - time();
$day = floor($rem / 86400);
$hr  = floor(($rem % 86400) / 3600);
$min = floor(($rem % 3600) / 60);
$sec = ($rem % 60);

$exp = $day." day(s)";
if ($exp<1) {
$exp = $hr." hour(s)";	
}
			?>
<div class="apidiv <? if ($rec->status<1) { ?>disableddiv<? } ?>" id="apikey_<? echo $rec->ID; ?>">
<i class="fa-solid fa-calendar-days"></i> [<? echo date('d/m/y', $rec->time_added); ?>] <i class="fa-solid fa-calendar-xmark"></i> Trading Expires: <b><? echo $exp; ?></b>  <? if ($linked <> '') { ?><font color="#00CC00"><i class="fa-solid fa-money-bill-trend-up"></i> Trading Active</font><? } ?><br /><i class="fa-solid fa-key"></i> <? echo $hide_key; ?><br />
<div align="center" id="api_sec_<? echo $rec->ID; ?>" style="display:none;">
<input type="text" class="tbox" readonly="readonly" value="secret..." id="api_sec_box_<? echo $rec->ID; ?>" />&nbsp;<button onclick="cpyPaste('api_sec_box_<? echo $rec->ID; ?>');">Copy</button>
<br /><br />
</div>
<div id="api_stats_<? echo $rec->ID; ?>" style="display:none;">
<fieldset><legend>API Statistics</legend>
Under Construction.
</fieldset>
<br />
</div>
<div align="right"><button class="optbutton" onclick="confirmDelete(<? echo $rec->ID; ?>)" id="dlt_<? echo $rec->ID; ?>"> <i class="fa-solid fa-trash-can"></i> Delete</button><button class="optbutton" style="display:none;" id="suredelete_<? echo $rec->ID; ?>" onclick="dltApi(<? echo $rec->ID; ?>);"><img src="<?php echo esc_url(plugins_url('/images/exclaim.png', __FILE__)); ?>" width="24px" title="Delete" alt="Delete" /> Are you sure?</button>&nbsp;<button class="optbutton" onclick="toggleApi(<? echo $rec->ID; ?>, <? echo $rec->status; ?>);"> <i class="fa-solid fa-key"></i> Lock/Unlock</button>&nbsp;<button class="optbutton" onclick="jsGoto('<? echo home_url($wp->request); ?>/?mode=stats&keyid=<? echo $rec->ID; ?>');"> <i class="fa-solid fa-chart-line"></i> Statistics</button>
</div>
</div>
<br />
            <?
		}
		
		if ($keys<1){
?>
<div align="center">No API keys to display. <a href="javascript:;" onclick="addKey();">Add New Key</a></div><br />
<? 
		}

} ?>
<?
if ($_REQUEST['mode'] == 'account_progress') {
	
	
	if ($_REQUEST['action'] == 'save_profile' ){
		$nonce = sanitize_text_field($_REQUEST['nonce']);
		$f_forenames = sanitize_text_field($_REQUEST['f_forenames']);
		$f_surname = sanitize_text_field($_REQUEST['f_surname']);
		$f_reflink = sanitize_text_field($_REQUEST['f_reflink']);
		$account_id = sanitize_text_field($_REQUEST['account_id']) ?? 0;
		
		//check if we should update or insert a record
		$table = $wpdb->prefix."binance_API_accounts";
		if ($account_id<1) {
		$sql = "INSERT INTO $table (account_added, status, wpuid, account_active, user_ref_link, reg_video_stage, forenames, surname) VALUES ('".strtotime("now")."', 1, ".$current_user->ID.", 1, '".$f_reflink."', 0, '".$f_forenames."', '".$f_surname."')";
		$wpdb->query($sql);	
		echo $sql;
		} else {
		$sql = "UPDATE $table set forenames = '".$f_forenames."', surname = '".$f_surname."', user_ref_link = '".$f_reflink."' where ID=".$account_id." and wpuid=".$current_user->ID;
		$wpdb->query($sql);
		}
			//get form data
		$table = $wpdb->prefix."binance_API_accounts";
		
		$sql = "SELECT * FROM $table where wpuid=".$current_user->ID;
		$db = $wpdb->get_results($sql);
		
		foreach ($db as $rec){
			
			$form_forenames = $rec->forenames;
			$form_surname = $rec->surname;
			$form_reflink = $rec->user_ref_link;	
		}
	
		
	}
	
?>
<hr />
<fieldset>
<img style="vertical-align:middle" src="<?php echo esc_url(plugins_url('/images/account.png', __FILE__)); ?>" width="32px" /> Complete some basic profile information to start using your account and adding API keys.
</fieldset>
<br />
<form method="post" action="">
<input type="hidden" name="action" id="action" value="save_profile" />
<input type="hidden" name="nonce" id="nonce" value="<? echo wp_create_nonce( 'fbinance' ); ?>" />
<input type="hidden" name="account_id" id="account_id" value="<? echo $account_id; ?>" />
Forename(s)<br />
<input type="text" name="f_forenames" id="f_forenames" placeholder="First Name(s)" style="width:100%" value="<? echo $form_forenames; ?>" /><br /><br />
Surname<br />
<input type="text" name="f_surname" id="f_surname" placeholder="Surname" style="width:100%" value="<? echo $form_surname; ?>" /><br /><br />
Referral Link<br />
<input type="url" name="f_reflink" id="f_reflink" placeholder="http://mybinanace.referal/" value="<? echo $form_reflink; ?>" style="width:100%" /><br /><br /><button style="width:100%">Save Profile</button>
</form>
<?
}
if ($_REQUEST['mode'] == 'video_progress') {

?>
<hr />
<img style="vertical-align:middle" src="<?php echo esc_url(plugins_url('/images/video.png', __FILE__)); ?>" width="32px" /> To complete this stage you must watch and accept all 4 introduction videos. Once you have watched each video the next will be unlocked for you.
</div>
<hr />
<? if ($account_active <1) { 
?>
<div class="accountdiv">
<img src="<?php echo esc_url(plugins_url('/images/account.png', __FILE__)); ?>" width="24px" /></a> You must complete your account profile before completing induction videos. <a href="<? echo home_url($wp->request); ?>/?mode=account_progress">Continue</a>
</div>
<? } else { ?>
<div id="vid_1" align="center" class="viddiv">
<table width="100%" border="0">
  <tr>
    <td width="40%" align="center"><video controls="controls" width="200" height="200" name="Video Name">
  <source src="<?php echo esc_url(plugins_url('/vid_intro_1.mov', __FILE__)); ?>">
</video></td>
    <td><h4>Introduction Video 1</h4>
      <br />
      When you have watched please confirm to move onto next section.<br />
      <br /><button onclick="jsVidStage(1);" <? if ($_REQUEST['overide']) { ?>disabled="disabled"<? } ?>>Confirm</button></td>
  </tr>
</table>
</div>&nbsp;
<div id="vid_2" align="center" class="viddiv">
<table width="100%" border="0">
  <tr>
    <td width="40%" align="center"><video controls="controls" width="200" height="200" name="Video Name">
  <source src="<?php echo esc_url(plugins_url('/vid_intro_2.mov', __FILE__)); ?>">
</video></td>
    <td><h4>Introduction Video 2</h4>
      <br />
      When you have watched please confirm to move onto next section.<br />
      <br /><button onclick="jsVidStage(2);" <? if ($_REQUEST['overide']) { ?>disabled="disabled"<? } ?>>Confirm</button></td>
  </tr>
</table>
</div>&nbsp;
<div id="vid_3" align="center" class="viddiv">
<table width="100%" border="0">
  <tr>
    <td width="40%" align="center"><video controls="controls" width="200" height="200" name="Video Name">
  <source src="<?php echo esc_url(plugins_url('/vid_intro_3.mov', __FILE__)); ?>">
</video></td>
    <td><h4>Introduction Video 3</h4>
      <br />
      When you have watched please confirm to move onto next section.<br />
      <br /><button onclick="jsVidStage(3);" <? if ($_REQUEST['overide']) { ?>disabled="disabled"<? } ?>>Confirm</button></td>
  </tr>
</table>
</div>&nbsp;
<div id="vid_4" align="center" class="viddiv">
<table width="100%" border="0">
  <tr>
    <td width="40%" align="center"><video controls="controls" width="200" height="200" name="Video Name">
  <source src="<?php echo esc_url(plugins_url('/vid_intro_4.mov', __FILE__)); ?>">
</video></td>
    <td><h4>Introduction Video 4</h4>
      <br />
      When you have watched please confirm to move onto next section.<br />
      <br /><button onclick="jsVidStage(4);" <? if ($_REQUEST['overide']) { ?>disabled="disabled"<? } ?>>Confirm</button></td>
  </tr>
</table>
</div>&nbsp;
<? } ?>
<?	
}
	}
}
function filter_plugin_row_meta( array $plugin_meta, $plugin_file ) {
	if ( 'wpmailmon/wpmailmon.php' !== $plugin_file ) {
		return $plugin_meta;
	}

	$plugin_meta[] = sprintf(
		'<a href="%1$s"><span class="dashicons dashicons-star-filled" aria-hidden="true" style="font-size:14px;line-height:1.3"></span>%2$s</a>',
		'https://gitcoded.co.uk/wpmailmon',
		esc_html_x( 'PRO Features', 'verb', 'wpmailmon' )
	);

	return $plugin_meta;
}
function wpmm_testtheapi(){
	global $wpdb;
	$enabled = true;
 if ($enabled) {
	 $table = $wpdb->prefix."WPMailMon_counters";
	$wpdb->query("UPDATE ".$table." set throttle_counter_day=throttle_counter_day +1");
	$your_key = "HJvQ334CyPrNWSY6rC6ZVDrdJRAZa8LocKU99wqIG85eJeeyI4qgz61gkqvRY75q";
	$your_secret = "mCJKzKXQqz2cGkhnLJ88mkQAhf3ln6T4xGTff0Pgy8kL3WxEkSj6hx49HtEy4wXR";
binance::auth($your_key, $your_secret);

$status = binance::call('/sapi/v1/system/status');
///sapi/v1/capital/deposit/hisrec
///api/v3/time
//$status = binance::call('/sapi/v1/capital/deposit/hisrec');
//$status = binance::call('/api/v3/time');
echo 'Binance API Status is: ' . $status['msg'];
/*
foreach ( $status as $deposits ) {
  print_r($deposits);
}
*/
 } else {
	echo "The API is disabled by admin."; 
 }
wp_die();
}
function screen_option_throttle() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Keys Per Page',
			'default' => 5,
			'option'  => 'keys_per_page'
		];

		add_screen_option( $option, $args );

		$this->customers_obj = new WPMMThrottle_List();
		
}
function wpmmInstall(){
global $wpdb;

	$table = $wpdb->prefix."binance_API_stats";
    $structure = "CREATE TABLE $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
        UNIQUE KEY ID (id), btc_amount VARCHAR(50), usd_amount VARCHAR(50), day_profit_btc VARCHAR(50), day_profit_usd VARCHAR(50), day_profit_btc_percentage VARCHAR(50), day_profit_usd_percentage VARCHAR(50), btc_profit VARCHAR(50), usd_profit VARCHAR(50), usd_profit_percentage VARCHAR(50), btc_profit_percentage VARCHAR(50), total_btc_profit VARCHAR(50), total_usd_profit VARCHAR(50), e_time VARCHAR(50) DEFAULT NULL, account_name VARCHAR(5), wpuid INT(9) DEFAULT 0
    );";

    $wpdb->query($structure);
	
	$table = $wpdb->prefix."binance_API_keys";
    $structure = "CREATE TABLE $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
        UNIQUE KEY ID (id), time_added VARCHAR(100), status VARCHAR(50), wpuid INT(9), API_KEY VARCHAR(100), API_SECRET VARCHAR(100), 3comms_sync INT(9) DEFAULT 0, comms_id VARCHAR(100) DEFAULT NULL, localID VARCHAR(100) DEFAULT NULL, trading_expires VARCHAR(100) DEFAULT NULL, key_linked_email INT(9) DEFAULT 0
    );";

    $wpdb->query($structure);
	
		//add new table for email settings
		
	$table = $wpdb->prefix."binance_auto_emails";
    $structure = "CREATE TABLE $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
        UNIQUE KEY ID (id), e_function VARCHAR(50), e_subject VARCHAR(100), e_message LONGTEXT
    );";

    $wpdb->query($structure);
	
	$wpdb->query("INSERT INTO $table (e_functiom) VALUES ('new_link')");
	$wpdb->query("INSERT INTO $table (e_functiom) VALUES ('key_expired')");
	$wpdb->query("INSERT INTO $table (e_functiom) VALUES ('thirty_day')");
	$wpdb->query("INSERT INTO $table (e_functiom) VALUES ('seven_day')");
	$wpdb->query("INSERT INTO $table (e_functiom) VALUES ('trading_confirm')");
	$wpdb->query("INSERT INTO $table (e_functiom) VALUES ('key_removed')");
	$wpdb->query("INSERT INTO $table (e_functiom) VALUES ('thirty_notice')");
	$wpdb->query("INSERT INTO $table (e_functiom) VALUES ('seven_notice')");
	$wpdb->query("INSERT INTO $table (e_functiom) VALUES ('last_notice')");
	
	$table = $wpdb->prefix."binance_API_accounts";
    $structure = "CREATE TABLE $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
        UNIQUE KEY ID (id), account_added VARCHAR(100), status VARCHAR(50), wpuid INT(9), account_active INT(9) DEFAULT 1, account_notes LONGTEXT, user_ref_link LONGTEXT, reg_video_stage INT(9) DEFAULT 0, forenames VARCHAR(100), surname VARCHAR(100), terms_accepted INT(9) DEFAULT 0
    );";

    $wpdb->query($structure);
	add_option('binance_version_check', $this->getCurrentVersion());
	add_option('wpmm_version_check', $this->getCurrentVersion());
	add_option('b_cron_alert', 'off');
	add_option('commas_api_key', '');
	add_option('commas_api_secret', '');
	add_option('wpmm_email_logging', 'off');
	add_option('wpmm_api_enabled', 'off');
	add_option('wpmm_settings', false);
	add_option('wpmm_setting_throttle_max_errors', 0);
	add_option('wpmm_setting_throttle_max_emails', 0);
	add_option('wpmm_throttle_notifications', 'on');
	add_option('wpmm_setting_throttle_max_persec', 0);
	add_option('wpmm_setting_max_logs', 0);
	add_option('wpmm_setting_email_header', 'plaintext');
	add_option('wpmm_setting_smtp_auth', 'off');
}
function wpmmUninstall(){
global $wpdb;
	$table = $wpdb->prefix."binance_API_accounts";
	$structure = "DROP TABLE $table";
	$wpdb->query($structure);
	
	$table = $wpdb->prefix."binance_API_stats";
	$structure = "DROP TABLE $table";
	$wpdb->query($structure);
		
	$table = $wpdb->prefix."binance_API_keys";
	$structure = "DROP TABLE $table";
	$wpdb->query($structure);
		
	$table = $wpdb->prefix."WPMailMon_LOGS";
	$structure = "DROP TABLE $table";
	$wpdb->query($structure);

	$table = $wpdb->prefix."WPMailMon_counters";
	$structure = "DROP TABLE $table";
	$wpdb->query($structure);

	$table = $wpdb->prefix."WPMailMon_throttle_rules";
	$structure = "DROP TABLE $table";
	$wpdb->query($structure);
	delete_option('b_cron_alert');
	delete_option('binance_version_checks');
	delete_option('autocomms');
	delete_option('autoexpire');
	delete_option('commas_api_key');
	delete_option('commas_api_secret');
 	delete_option("WPMailMon_lic_Key");
	delete_option('wpmm_version_check');
	delete_option('wpmm_api_enabled');
	delete_option('wpmm_email_logging');
	delete_option('wpmm_settings');
	delete_option('wpmm_setting_throttle_max_errors');
	delete_option('wpmm_setting_throttle_max_emails');
	delete_option('wpmm_setting_email_host');
	delete_option('wpmm_setting_email_user');
	delete_option('wpmm_setting_email_pass');
	delete_option('wpmm_setting_email_security');
	delete_option('wpmm_setting_email_port');
	delete_option('wpmm_throttle_notifications');
	delete_option('wpmm_setting_throttle_max_persec');
	delete_option('wpmm_setting_max_logs');
	delete_option('wpmm_setting_email_header');
	delete_option('wpmm_setting_smtp_auth');
	delete_option('wpmm_setting_slack_url');
	$timestamp = wp_next_scheduled( 'wpmm_cron_hook_min' );
wp_unschedule_event( $timestamp, 'wpmm_cron_hook_min' );
	$timestamp = wp_next_scheduled( 'wpmm_cron_hook_hour' );
wp_unschedule_event( $timestamp, 'wpmm_cron_hook_hour' );
	$timestamp = wp_next_scheduled( 'wpmm_cron_hook_day' );
wp_unschedule_event( $timestamp, 'wpmm_cron_hook_day' );

}
function wpmm_admin_scripts($hook){
			 wp_register_style( 'fawsome', plugins_url('/css/all.css', __FILE__ ), true);
		 wp_enqueue_style( 'fawsome' ); 
wp_register_style('wpmm-css', plugins_url('/custom.css', __FILE__ ));
wp_enqueue_style('wpmm-css');
wp_register_style('bstrap-css', plugins_url('assets/bootstrap/css/bootstrap.min.css', __FILE__ ));
wp_enqueue_style('bstrap-css');
wp_enqueue_script( 'wpmm-jsp', plugins_url('/popper.js', __FILE__ ), array(), '', true );
wp_enqueue_script( 'wpmm-bs', plugins_url('assets/bootstrap/js/bootstrap.min.js', __FILE__ ), array(), '', true );  
wp_enqueue_script( 'wpmm-js', plugins_url('/custom.js', __FILE__ ), array(), '', true ); 
wp_localize_script( 'wpmm-js', 'wpmm', array(
    // URL to wp-admin/admin-ajax.php to process the request
    'ajaxurl' => admin_url( 'admin-ajax.php' ),
    // generate a nonce with a unique ID "myajax-post-comment-nonce"
    // so that you can check it later when an AJAX request is sent
    'nonce' => wp_create_nonce( 'wpmm' )
//
  ));
		 wp_register_style( 'fawsomea', plugins_url('/css/all.css', __FILE__ ), true);
		 wp_enqueue_style( 'fawsomea' ); 

}
    function SetAdminStyle() {
        wp_register_style( "BinanncyLic", plugins_url("_lic_style.css",$this->plugin_file),10);
        wp_enqueue_style( "BinanncyLic" );
wp_enqueue_script('jquery-ui-datepicker');  
wp_enqueue_style('jquery-ui-css', plugins_url("jquery-ui.css",$this->plugin_file));    
wp_enqueue_style( "WPMailMonLic" );
		 wp_register_style( 'fawsomea', plugins_url('/css/all.css', __FILE__ ), true);
		 wp_enqueue_style( 'fawsomea' ); 


    }
    function ActiveAdminMenu(){
        		$hook = add_menu_page (  "Binanncy", "Binanncy", "activate_plugins", $this->slug, [$this,"Activated"], plugins_url('/images/binance.png', __FILE__));

		add_action( "load-$hook", [ $this, 'screen_option_throttle' ] );
		//add_menu_page (  "WPMailMon", "WPMailMon", "activate_plugins", $this->slug, [$this,"Activated"], " dashicons-star-filled ");
		//$hook = add_submenu_page(  $this->slug, "WPMailMon Throttle Rules", "Throttle Rules", "activate_plugins",  $this->slug."_throttle", [$this,"Throttle_Rules"] );
		add_action( "load-$hook", [ $this, 'screen_option_throttle' ] );
		//$hook = add_submenu_page(  $this->slug, "WPMailMon Settings", "Settings", "activate_plugins",  $this->slug."_settings", [$this,"admin_settings"] );
		//add_action( "load-$hook", [ $this, 'screen_option_throttle' ] );

    }
	function admin_settings(){
	global $wpdb;
if(class_exists('wpmmAdminSetPro')){
$adminSet = new wpmmAdminSetPro(empty($wpmm));
} else {
$adminSet = new wpmmAdminSet(empty($wpmm));
}
?>

   <div class="el-license-container">
                <h3 class="el-license-title"><i class="dashicons-before dashicons-admin-generic"></i> <?php _e("Settings",$this->slug);?> </h3>
                <hr>
<? $adminSet->settings(); ?>
            </div>
<?
	}
    function InactiveMenu() {
	        add_menu_page( "Binanncy", "Binanncy", 'activate_plugins', $this->slug,  [$this,"LicenseForm"], plugins_url('/images/binance.png', __FILE__) );

    }
    function action_activate_license(){
        check_admin_referer( 'el-license' );
        $licenseKey=!empty($_POST['el_license_key'])?$_POST['el_license_key']:"";
        $licenseEmail=!empty($_POST['el_license_email'])?$_POST['el_license_email']:"";
        update_option("Binanncy_lic_Key",$licenseKey) || add_option("Binanncy_lic_Key",$licenseKey);
        update_option("Binanncy_lic_email",$licenseEmail) || add_option("Binanncy_lic_email",$licenseEmail);
        update_option('_site_transient_update_plugins','');
        wp_safe_redirect(admin_url( 'admin.php?page='.$this->slug));
    }
    function action_deactivate_license() {
        check_admin_referer( 'el-license' );
        $message="";
        if(BinanncyBase::RemoveLicenseKey(__FILE__,$message)){
            update_option("Binanncy_lic_Key","") || add_option("Binanncy_lic_Key","");
            update_option('_site_transient_update_plugins','');
        }
        wp_safe_redirect(admin_url( 'admin.php?page='.$this->slug));
    }
    function Activated(){
		global $wpdb;
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="Binanncy_el_deactivate_license"/>
            <div class="el-license-container">
                <h3 class="el-license-title"><i class="dashicons-before dashicons-star-filled"></i> <?php _e("Binanncy License Info",$this->slug);?> </h3>
                <hr>
                <ul class="el-license-info">
                <li>
                    <div>
                        <span class="el-license-info-title"><?php _e("Status",$this->slug);?></span>

                        <?php if ( $this->responseObj->is_valid ) : ?>
                            <span class="el-license-valid"><?php _e("Valid",$this->slug);?></span>
                        <?php else : ?>
                            <span class="el-license-valid"><?php _e("Invalid",$this->slug);?></span>
                        <?php endif; ?>
                    </div>
                </li>

                <li>
                    <div>
                        <span class="el-license-info-title"><?php _e("License Type",$this->slug);?></span>
                        <?php echo $this->responseObj->license_title; ?>
                    </div>
                </li>
                 <li>
                    <div>
                        <span class="el-license-info-title"><?php _e("Cron Alerts",$this->slug);?></span><label class="switch">
  <input type="checkbox" <? if (get_option('b_cron_alert') == 'on') { ?>checked<? } ?> id="cronalerts">
  <span class="slider"></span>
</label>
                       
                    </div>
                </li>
                <li>
                    <div>
                        <span class="el-license-info-title"><?php _e("Current Version",$this->slug);?></span>
                        <?php echo get_option('binance_version_check'); ?>
                    </div>
                </li>
                <li>
                    <div>
                        <span class="el-license-info-title"><?php _e("Plugin Path",$this->slug);?></span>
                        <?php echo plugin_dir_path( __DIR__ ); ?>
                    </div>
                </li>
               
               <li>
                   <div>
                       <span class="el-license-info-title"><?php _e("License Expired on",$this->slug);?></span>
                       <?php echo $this->responseObj->expire_date;
                       if(!empty($this->responseObj->expire_renew_link)){
                           ?>
                           <a target="_blank" class="el-blue-btn" href="<?php echo $this->responseObj->expire_renew_link; ?>">Renew</a>
                           <?php
                       }
                       ?>
                   </div>
               </li>

               <li>
                   <div>
                       <span class="el-license-info-title"><?php _e("Support Expired on",$this->slug);?></span>
                       <?php
                           echo $this->responseObj->support_end;
                        if(!empty($this->responseObj->support_renew_link)){
                            ?>
                               <a target="_blank" class="el-blue-btn" href="<?php echo $this->responseObj->support_renew_link; ?>">Renew</a>
                            <?php
                        }
                       ?>
                   </div>
               </li>
                <li>
                    <div>
                        <span class="el-license-info-title"><?php _e("Your License Key",$this->slug);?></span>
                        <span class="el-license-key"><?php echo esc_attr( substr($this->responseObj->license_key,0,9)."XXXXXXXX-XXXXXXXX".substr($this->responseObj->license_key,-9) ); ?></span>
                    </div>
                </li>
                </ul>
                <div class="el-license-active-btn">
                    <?php wp_nonce_field( 'el-license' ); ?>
                    <?php submit_button('Deactivate'); ?>
                </div>
            </div>
        </form>
    <?php
	// NEW ADMIN AREA
//test mail
global $wpdb;

$table = $wpdb->prefix."WPMailMon_counters";
$e_count = $wpdb->get_var("SELECT throttle_counter_day FROM $table WHERE ID=1") ?? 0;
$err_count = $wpdb->get_var("SELECT error_count FROM $table WHERE ID=1") ?? 0;
?>
            <div class="el-license-container">
<?
if(!empty(sanitize_text_field($_REQUEST['msg']))):
?>
    <div class="notice notice-info is-dismissible">
        <p><?php _e( sanitize_text_field($_REQUEST['msg']), $this->slug ); ?></p>
    </div>
    
<? endif; ?>
                <h3 class="el-license-title"><img src="<?php echo esc_url(plugins_url('/images/binance.png', __FILE__)); ?>" /> <?php _e("Binanncy Admin",$this->slug);?> </h3>

                <hr>
<div class="notice notice-success" id="set_saved" style="display:none;"> 
	<p><strong><span id="jax_msg"><? _e('Setting Changed!'); ?></span></strong></p>
</div>
<div>
    <div class="container">
    <div class="row">
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="Binanncy_el_save_master_api"/>

    <table width="100%" border="0">
  <tr>
    <td><div align="right">3comms master API key :&nbsp;</div></td>
    <td><input type="text" name="master_api" id="master_api" size="50" value="<? echo get_option('commas_api_key'); ?>" /></td>
  </tr>
  <tr>
    <td><div align="right">3comms API secret :&nbsp;</div></td>
    <td><input type="text" name="master_secret" id="master_secret" size="50" value="<? echo get_option('commas_api_secret'); ?>" /></td>
  </tr>
    <tr>
    <td><div align="right">Account Prefix :&nbsp;</div></td>
    <td><input type="text" name="comms_prefix" id="comms_prefix" size="20" value="<? echo get_option('commas_prefix'); ?>" placeholder="e.g wp_ or binance_" <? if (get_option('commas_prefix') <> '') { ?>readonly="readonly"<? } ?> /></td>
  </tr>
    <tr>
    <td colspan="2" align="right">
                    <?php wp_nonce_field( 'binny' ); ?>
                <?php submit_button('Save API Credentials'); ?>
    </td>
    </tr>
</table> 
   </form>
    </div>

      <div class="row">
            <div class="col-md-3" style="margin:auto;">
<label class="switch">
  <input type="checkbox" <? if (get_option('autoexpire') == 'on') { ?>checked<? } ?> id="autoexpire">
  <span class="slider"></span>
</label><br />
Auto Delete Expired
	
			</div>
            <div class="col-md-3" style="margin:auto;">
<label class="switch">
  <input type="checkbox" <? if (get_option('autocomms') == 'on') { ?>checked<? } ?> id="autocomms">
  <span class="slider"></span>
</label>
<br />Auto Add Accounts
	
			</div>
            <div class="col-md-3" style="margin:auto;">
<button class="button" onclick="syncCommas();">Sync With 3Commas</button>
	`		</div>
                <div class="col-md-3" style="margin:auto;">
<button class="button" onclick="admExport();">Export Users</button>
	`		</div>
            
        </div>
    </div>
</div>


</div>
<div class="el-license-container">
<h4>Email Templates</h4>
<div align="center">
<button class="button" onclick="toggle_e('t1');">New Key</button>&nbsp;<button class="button" onclick="toggle_e('t2');">Key Expired</button>&nbsp;<button class="button" onclick="toggle_e('t3');">Confirm Trading</button>&nbsp;<button class="button" onclick="toggle_e('t4');">Key Removed</button>&nbsp;<button class="button" onclick="toggle_e('t5');">30 Day Notice</button>&nbsp;<button class="button" onclick="toggle_e('t6');">7 Day Notice</button>&nbsp;<button class="button" onclick="toggle_e('t7');">Last Reminder</button>
</div>
<div id="t7" style="display:none;">
<hr />
<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<input type="hidden" name="action" id="action" value="save_email_template" />
<input type="hidden" name="template" id="template" value="last_notice" />
<?
	$table = $wpdb->prefix."binance_auto_emails";

$content = $wpdb->get_var("SELECT e_message from $table where e_function = 'last_notice'");
$subject = $wpdb->get_var("SELECT e_subject from $table where e_function = 'last_notice'");
?>
Email Subject:
<input type="text" style="width:100%" name="subject_last_notice" placeholder="Email Subject" value="<? echo $subject; ?>" />
        <?
$custom_editor_id = "e_template_last_notice";
$custom_editor_name = "e_template_last_notice";
$args = array(
        'textarea_name' => $custom_editor_name,
        'textarea_rows' => get_option('default_post_edit_rows', 5),
    );
wp_editor( $content, $custom_editor_id, $args );
		?>
                  
                <?php wp_nonce_field( 'binance' ); ?>
                <?php submit_button('Save'); ?>
</form>
        </div>
<div id="t6" style="display:none;">
<hr />
<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<input type="hidden" name="action" id="action" value="save_email_template" />
<input type="hidden" name="template" id="template" value="seven_notice" />
<?
	$table = $wpdb->prefix."binance_auto_emails";

$content = $wpdb->get_var("SELECT e_message from $table where e_function = 'seven_notice'");
$subject = $wpdb->get_var("SELECT e_subject from $table where e_function = 'seven_notice'");
?>
Email Subject:
<input type="text" style="width:100%" name="subject_seven_notice" placeholder="Email Subject" value="<? echo $subject; ?>" />
        <?
$custom_editor_id = "e_template_seven_notice";
$custom_editor_name = "e_template_seven_notice";
$args = array(
        'textarea_name' => $custom_editor_name,
        'textarea_rows' => get_option('default_post_edit_rows', 5),
    );
wp_editor( $content, $custom_editor_id, $args );
		?>
                  
                <?php wp_nonce_field( 'binance' ); ?>
                <?php submit_button('Save'); ?>
</form>
        </div>
<div id="t5" style="display:none;">
<hr />
<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<input type="hidden" name="action" id="action" value="save_email_template" />
<input type="hidden" name="template" id="template" value="thirty_notice" />
<?
	$table = $wpdb->prefix."binance_auto_emails";

$content = $wpdb->get_var("SELECT e_message from $table where e_function = 'thirty_notice'");
$subject = $wpdb->get_var("SELECT e_subject from $table where e_function = 'thirty_notice'");
?>
Email Subject:
<input type="text" style="width:100%" name="subject_thirty_notice" placeholder="Email Subject" value="<? echo $subject; ?>" />
        <?
$custom_editor_id = "e_template_thirty_notice";
$custom_editor_name = "e_template_thirty_notice";
$args = array(
        'textarea_name' => $custom_editor_name,
        'textarea_rows' => get_option('default_post_edit_rows', 5),
    );
wp_editor( $content, $custom_editor_id, $args );
		?>
                  
                <?php wp_nonce_field( 'binance' ); ?>
                <?php submit_button('Save'); ?>
</form>
        </div>
<div id="t4" style="display:none;">
<hr />
<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<input type="hidden" name="action" id="action" value="save_email_template" />
<input type="hidden" name="template" id="template" value="key_removed" />
<?
	$table = $wpdb->prefix."binance_auto_emails";

$content = $wpdb->get_var("SELECT e_message from $table where e_function = 'key_removed'");
$subject = $wpdb->get_var("SELECT e_subject from $table where e_function = 'key_removed'");
?>
Email Subject:
<input type="text" style="width:100%" name="subject_key_removed" placeholder="Email Subject" value="<? echo $subject; ?>" />
        <?
$custom_editor_id = "e_template_key_removed";
$custom_editor_name = "e_template_key_removed";
$args = array(
        'textarea_name' => $custom_editor_name,
        'textarea_rows' => get_option('default_post_edit_rows', 5),
    );
wp_editor( $content, $custom_editor_id, $args );
		?>
                  
                <?php wp_nonce_field( 'binance' ); ?>
                <?php submit_button('Save'); ?>
</form>
        </div>
<div id="t3" style="display:none;">
<hr />
<style>
.alert {
  padding: 20px;
  background-color: #f44336;
  color: white;
}

.closebtn {
  margin-left: 15px;
  color: white;
  font-weight: bold;
  float: right;
  font-size: 22px;
  line-height: 20px;
  cursor: pointer;
  transition: 0.3s;
}

.closebtn:hover {
  color: black;
}
</style>
<div class="alert">
  <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
  <strong>Notice: </strong> This template is not currently in use. (Under Construction.)
</div>
<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<input type="hidden" name="action" id="action" value="save_email_template" />
<input type="hidden" name="template" id="template" value="trading_confirm" />
<?
	$table = $wpdb->prefix."binance_auto_emails";

$content = $wpdb->get_var("SELECT e_message from $table where e_function = 'trading_confirm'");
$subject = $wpdb->get_var("SELECT e_subject from $table where e_function = 'trading_confirm'");
?>
Email Subject:
<input type="text" style="width:100%" name="subject_trading_confirm" placeholder="Email Subject" value="<? echo $subject; ?>" />
        <?
$custom_editor_id = "e_template_trading_confirm";
$custom_editor_name = "e_template_trading_confirm";
$args = array(
        'textarea_name' => $custom_editor_name,
        'textarea_rows' => get_option('default_post_edit_rows', 5),
    );
wp_editor( $content, $custom_editor_id, $args );
		?>
                  
                <?php wp_nonce_field( 'binance' ); ?>
                <?php submit_button('Save'); ?>
</form>
        </div>
<div id="t1" style="display:none;">
<hr />
<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<input type="hidden" name="action" id="action" value="save_email_template" />
<input type="hidden" name="template" id="template" value="new_link" />
<?
	$table = $wpdb->prefix."binance_auto_emails";

$content = $wpdb->get_var("SELECT e_message from $table where e_function = 'new_link'");
$subject = $wpdb->get_var("SELECT e_subject from $table where e_function = 'new_link'");
?>
Email Subject:
<input type="text" style="width:100%" name="subject_new_link" placeholder="Email Subject" value="<? echo $subject; ?>" />
        <?
$custom_editor_id = "e_template_newlink";
$custom_editor_name = "e_template_newlink";
$args = array(
        'textarea_name' => $custom_editor_name,
        'textarea_rows' => get_option('default_post_edit_rows', 5),
    );
wp_editor( $content, $custom_editor_id, $args );
		?>
                  
                <?php wp_nonce_field( 'binance' ); ?>
                <?php submit_button('Save'); ?>
</form>
        </div>
     
<div id="t2" style="display:none;">
<hr />
<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<input type="hidden" name="action" id="action" value="save_email_template" />
<input type="hidden" name="template" id="template" value="key_expired" />
<?
	$table = $wpdb->prefix."binance_auto_emails";

$content = $wpdb->get_var("SELECT e_message from $table where e_function = 'key_expired'");
$subject = $wpdb->get_var("SELECT e_subject from $table where e_function = 'key_expired'");
?>
Email Subject:
<input type="text" style="width:100%" name="subject_key_expired" placeholder="Email Subject" value="<? echo $subject; ?>" />
        <?
$custom_editor_id = "e_template_key_expired";
$custom_editor_name = "e_template_key_expired";
$args = array(
        'textarea_name' => $custom_editor_name,
        'textarea_rows' => get_option('default_post_edit_rows', 5),
    );
wp_editor( $content, $custom_editor_id, $args );
		?>
                  
                <?php wp_nonce_field( 'binance' ); ?>
                <?php submit_button('Save'); ?>
</form>
        </div>
</div>
            <div class="el-license-container">

    <div class="container">
        <div class="row">
        <?
		//$commas = new commas();
	//echo commas::test_class();
	
$this->customers_obj->prepare_items();
$this->customers_obj->display();
?>
      </div></div></div>
<?php if ($tmon) { ?>
<!-- new throttleMOD -->
   <div class="el-license-container">
                <h3 class="el-license-title"><i class="dashicons-before dashicons-performance"></i> <?php _e("Throttle Monitor",$this->slug);?> </h3>
                <hr>
<?
$throttle = $this->check_mail_throttle();
if($throttle && get_option('wpmm_api_enabled') == 'on'){

switch ($throttle['error_code']) {
//$this->ttip('Overide all Throttle Rules and set a MAX emails per day limit.<br><b>(0)</b> will disable.<br>The daily counter is reset automatically by CRON.'
case "FMAXERR":
$span = $this->ttip('<b>FMAXERR - </b> This is set in settings, maximum errors allowed before blocking mail. You can clear the error count by Clearing Logs.<br>This setting overides all throttle rules but can be disabled by setting it to <b>(0)</b>.');
?>
    <div class="notice notice-warning">
        <p><?php _e( 'WPMailMon Throttle Protection is blocking outgoing mail. <br> - Error Code: [<b><span '.$span.'>'.$throttle['error_code'].'</b></span>] - '.$throttle['error_message'].'<br>Check WPMailMon Logs For More Information.', $this->slug ); ?></p>
    </div>
<?
break;

case "FMAXDAY":
$span = $this->ttip('<b>FMAXDAY - </b> This is set in settings, maximum allowed emails to be sent per day. CRON resets this counter daily, or you can Clear Throttle.<br>This setting overides all throttle rules but can be set to <b>(0)</b> to disable.');
?>
    <div class="notice notice-warning">
        <p><?php _e( 'WPMailMon Throttle Protection is blocking outgoing mail. <br> - Error Code: [<b><span '.$span.'>'.$throttle['error_code'].'</b></span>] - '.$throttle['error_message'].'<br>Check WPMailMon Logs For More Information.', $this->slug ); ?></p>
    </div>
<?

break;

default:
?>
    <div class="notice notice-warning">
        <p><?php _e( 'WPMailMon Throttle Protection is blocking outgoing mail. <br> - Error Code: [<b>'.$throttle['error_code'].'</b>] - '.$throttle['error_message'].'<br>Check WPMailMon Logs For More Information.', $this->slug ); ?></p>
    </div>
 <?php
	break;
}

}
else {
?>
    <div class="notice notice-info">
        <p><?php _e( 'All mail functions are operating normally and flowing as usual.', $this->slug ); ?></p>
    </div>
<?
}
$table = $wpdb->prefix."WPMailMon_counters";
$min = $wpdb->get_var("SELECT throttle_counter_min from ".$table." where ID>0");
$hour = $wpdb->get_var("SELECT throttle_counter_hour from ".$table." where ID>0");
$day = $wpdb->get_var("SELECT throttle_counter_day from ".$table." where ID>0");
$errors = $wpdb->get_var("SELECT error_count from ".$table." where ID>0");
?>
<hr />
<div id="jax_throttle_mon">
<div class="container" align="center">
    <div class="row">
        <div class="col-md-3">Per Min/<br /><h4><? echo $min; ?></h4></div>
        <div class="col-md-3">Per Hour/<br /><h4><? echo $hour; ?></h4></div>
        <div class="col-md-3">Today/<br /><h4><? echo $day; ?></h4></div>
        <div class="col-md-3">Errors/<br /><h4><? echo $errors; ?></h4></div>
    </div>
</div>
</div>

            </div>
<? } ?>
<!-- New table container -->
<?	
	//NEW ADMIN AREA
    }

    function LicenseForm() {
        ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="Binanncy_el_activate_license"/>
        <div class="el-license-container">
            <h3 class="el-license-title"><i class="dashicons-before dashicons-star-filled"></i> <?php _e("Binanncy Licensing",$this->slug);?></h3>
            <hr>
            <?php
            if(!empty($this->showMessage) && !empty($this->licenseMessage)){
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo _e($this->licenseMessage,$this->slug); ?></p>
                </div>
                <?php
            }
            ?>
            <p><?php _e("Enter your license key here, to activate the product, and get full feature updates and premium support.",$this->slug);?></p>
<ol>
    <li><?php _e("Write your licnese key details",$this->slug);?></li>
    <li><?php _e("How buyer will get this license key?",$this->slug);?></li>
    <li><?php _e("Describe other info about licensing if required",$this->slug);?></li>
    <li>. ...</li>
</ol>
            <div class="el-license-field">
                <label for="el_license_key"><?php _e("License code",$this->slug);?></label>
                <input type="text" class="regular-text code" name="el_license_key" size="50" placeholder="xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx" required="required">
            </div>
            <div class="el-license-field">
                <label for="el_license_key"><?php _e("Email Address",$this->slug);?></label>
                <?php
                    $purchaseEmail   = get_option( "Binanncy_lic_email", get_bloginfo( 'admin_email' ));
                ?>
                <input type="text" class="regular-text code" name="el_license_email" size="50" value="<?php echo $purchaseEmail; ?>" placeholder="" required="required">
                <div><small><?php _e("We will send update news of this product by this email address, don't worry, we hate spam",$this->slug);?></small></div>
            </div>
            <div class="el-license-active-btn">
            
                <?php wp_nonce_field( 'el-license' ); ?>
                <?php submit_button('Activate'); ?>
            </div>
        </div>
    </form>
        <?php
    }
}
class WPMMThrottle_List extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'API', 'sp' ), //singular name of the listed records
			'plural'   => __( 'API(s)', 'sp' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );

	}


	/**
	 * Retrieve customers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
/*
	$table = $wpdb->prefix."binance_API_keys";
    $structure = "CREATE TABLE $table (
        ID INT(9) NOT NULL AUTO_INCREMENT,
        UNIQUE KEY ID (id), time_added VARCHAR(100), status VARCHAR(50), wpuid INT(9), API_KEY VARCHAR(100), API_SECRET VARCHAR(100)
		
*/
	public static function get_rules( $per_page = 5, $page_number = 1 ) {

		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}binance_API_keys";

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		} else {
			$sql .= ' ORDER BY ID desc';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}


	/**
	 * Delete a customer record.
	 *
	 * @param int $id customer ID
	 */
	public static function delete_rule( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}binance_API_keys",
			[ 'ID' => $id ],
			[ '%d' ]
		);
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}binance_API_keys";

		return $wpdb->get_var( $sql );
	}


	/** Text displayed when no customer data is available */
	public function no_items() {
		_e( 'No API Keys setup yet.', 'sp' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'address':
			//case 'etime':
				//return self::column_name($item);
			default:
				return $item[ $column_name ];
				//return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}


	function get_columns() {
		$columns = [
			'wpuid'    => __( 'User ID', 'sp' ),
			'time_added'    => __( 'Time/Date', 'sp' ),
			'API_KEY'    => __( 'API Key', 'sp' ),
			'col_opts'    => __( 'Options', 'sp' )
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'status' => array( 'status', false )
		);

		return $sortable_columns;
	}

		function column_rule_title($item){
		$delete_nonce = wp_create_nonce( 'sp_delete_customer' );

		$title = '<strong>' . $item['rule_title'] . ' (ID: '.$item['ID'].')</strong>';

		$actions = [
			'delete' => sprintf( '<a href="?page=%s&action=%s&rule=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['ID'] ), $delete_nonce )
		];

		return $title . $this->row_actions( $actions );
		}
		function column_wpuid($item){
		$user_info = get_userdata($item['wpuid']);
			echo "<b>".$user_info->user_login."</b><br>";
			echo $user_info->first_name." ".$user_info->last_name;
		 if(class_exists('commas')){
			 if ($item['comms_id']) {
				 ?>
                 <div id="comma_<? echo $item['ID']; ?>">
                 <br><i class="fa-solid fa-link"></i>
                 <?
			 echo " 3Commas ID: <b>{$item['comms_id']}</b></br>Ref: <b>{$item['localID']}</b></div>";
			 } else {
				 ?>
				<div id="comma_<? echo $item['ID']; ?>"><br><i class="fa-solid fa-link-slash"></i> No Link To 3Commas Yet.</div>
                <?
			 }
		 }
		}
		function column_API_KEY($item){
		?>
        <input type="text" id="k_<? echo $item['ID']; ?>_<? echo $item['API_KEY']; ?>" value="<? echo $item['API_KEY']; ?>" readonly="readonly" onclick="cpyPaste('k_<? echo $item['ID']; ?>_<? echo $item['API_KEY']; ?>');" /><br /><div align="center"><i class="fa-regular fa-paste"></i> Click field to copy/paste</div>
        <?	
		}
		function column_status($item){
		if($item['status'] == 1){
		?>
<label class="switch">
  <input type="checkbox" id="" checked="checked" onchange="toggleKeyState(<? echo $item['ID']; ?>);">
  <span class="slider"></span>
</label>
		<?
		} else {
		?>
<label class="switch">
  <input type="checkbox" id="" onchange="toggleKeyState(<? echo $item['ID']; ?>);">
  <span class="slider"></span>
</label>
		<?
		}

		}


		function column_time_added($item){
			
				echo date('Y-m-d H:i A', $item['time_added']);

$rem = $item['trading_expires'] - time();
$day = floor($rem / 86400);
$hr  = floor(($rem % 86400) / 3600);
$min = floor(($rem % 3600) / 60);
$sec = ($rem % 60);

$exp = $day." day(s)";
if ($exp<1) {
$exp = $hr." hour(s)";	
}
				?>
                <hr />
                Expires: <? echo $exp; ?>
                <?
		}

		function column_rule_type($item){

		switch ($item['rule_type']){
		case "flood":
		//$t = new WPMailMon();
		$t = new WPMailMon();
		$ttip = $t->ttip('This is a <b>FLOOD</b> protection rule.');
		$img = '<img src="'.plugins_url('/images/email.png', __FILE__).'"'.$ttip.' width="32">';
		break;

		case "time":
		$t = new WPMailMon();
		$ttip = $t->ttip('This is a <b>TIME</b> restriction rule.');
		$img = '<img src="'.plugins_url('/images/time.png', __FILE__).'"'.$ttip.' width="32">';
		break;
		}

		return $img;
		}

		function column_col_opts($item){
?>
<div align="center">

<a href="javascript:;" title="Delete" alt="Delete" onclick="admDeleteKey('k_<? echo $item['ID']; ?>_<? echo $item['API_KEY']; ?>')"><i class="fa-regular fa-trash-can fa-xl"></i></a><? if ($item['localID'] == '') { ?><a href="javascript:;" title="Link With 3Commas" alt="Link With 3Commas" onclick="jsSyncComma(<? echo $item['ID']; ?>);">&nbsp;<i class="fa-solid fa-link fa-xl"></i></a><? } ?></div>
<?
			//return $title;
		}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( '_per_page', 5 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = self::get_rules( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'sp_delete_customer' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				self::delete_rule( absint( $_GET['rule'] ) );

		                // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
		                // add_query_arg() return the current url
		                //wp_redirect( esc_url_raw(add_query_arg()) );
				//exit;
			}

		}

		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$delete_ids = esc_sql( $_POST['bulk-delete'] );

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				self::delete_rule( $id );

			}

			// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
		        // add_query_arg() return the current url
		        wp_redirect( esc_url_raw(add_query_arg()) );
			exit;
		}
	}

}
class SP_Plugin {

	// class instance
	static $instance;
	// customer WP_List_Table object
	public $customers_obj;
	public $rules_obj;
	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		//add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
new Binanncy();