<?
class binanncy_cron {
	public static function syncStats(){
		global $wpdb;
	
		$table = $wpdb->prefix."binance_API_keys";
		
		$db = $wpdb->get_results("SELECT * FROM $table where comms_id <>''");
		$table = $wpdb->prefix."binance_API_stats";
		foreach($db as $rec){
	//loop each API  key
		$cID = $rec->comms_id;
	//records stats
	
		$summary = commas::getStats($cID);
		$summary = json_decode($summary);
			//echo ":::::".$summary->name;
			
			$sql = "INSERT INTO $table (btc_amount, usd_amount, day_profit_btc, day_profit_usd, day_profit_btc_percentage, day_profit_usd_percentage, btc_profit, usd_profit, usd_profit_percentage, btc_profit_percentage, total_btc_profit, total_usd_profit, e_time, account_name) VALUES ('".$summary->btc_amount."', '".$summary->usd_amount."', '".$summary->day_profit_btc."', '".$summary->day_profit_usd."', '".$summary->day_profit_btc_percentage."', '".$summary->day_profit_usd_percentage."', '".$summary->btc_profit."', '".$summary->usd_profit."', '".$summary->usd_profit_percentage."', '".$summary->btc_profit_percentage."', '".$summary->total_btc_profit."', '".$summary->total_usd_profit."', '".strtotime("now")."', '".$summary->name."')";
			$wpdb->query($sql);
		
		}

	
	}
	public static function cronAlert(){
	global $wpdb;
			//email to say CRON has run
$headers = array('Content-Type: text/html; charset=UTF-8');
$to = get_bloginfo('admin_email');
$subject = 'Daily CRON job Executed';
$body = 'Hello Admin,<br>Daily cron has executed at '.get_bloginfo('url');
$msg = wp_mail( $to, $subject, $body, $headers );
	
	}
	public static function alertUsr(){
			global $wpdb;
		$table = $wpdb->prefix."binance_API_keys";
		
		$db = $wpdb->get_results("SELECT * FROM $table where key_linked_email<1 and comms_id <>''");
		
		foreach($db as $rec){
			$wpdb->query("update $table set key_linked_email=1 where ID=".$rec->ID);
		//try to link each record
		$api_key = $rec->API_KEY;
		$wpuid = $rec->wpuid;
		$trading_expires = $rec->trading_expires;
		$trading_expires = substr($trading_expires, 0, 10);

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
		}
	}
}
?>