<?
class commas {

	public static function test_class(){
		return "Class Under Construction.";	
	}
	
	public static function list_unlinked(){
		global $wpdb;
		$table = $wpdb->prefix."binance_API_keys";
		
		$db = $wpdb->get_results("SELECT * FROM $table where 3comms_id is NULL");
		
		return $db;
		
	}
	public static function link_unlinked(){
		global $wpdb;
		$table = $wpdb->prefix."binance_API_keys";
		
		$db = $wpdb->get_results("SELECT * FROM $table where 3comms_id is NULL");
		
		$comma = new commas();
		
		foreach($db as $rec){
		//try to link each record
		$account = get_option('commas_prefix').strtotime("now");
		$api_key = $rec['API_KEY'];
		$api_secret = $rec['API_SECRET'];
		
		}
		
	}
	function createAccount($account, $api_key, $api_secret) {
		global $wpdb;
		
$ch = curl_init();
$timestamp = round(microtime(true) * 1000);
$secret = get_option('commas_api_secret');
$key = get_option('commas_api_key');


$data = "name={$account}&type=binance&api_key={$api_key}&secret={$api_secret}";
$querystring = '/public/api/ver1/accounts/new?'.$data;
$signature = hash_hmac('SHA256',$querystring ,$secret);
curl_setopt($ch, CURLOPT_URL, "https://api.3commas.io/public/api/ver1/accounts/new");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'APIKEY: '.$key,
    'Signature: '.$signature,
	'Content-Type: application/x-www-form-urlencoded'
));

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
	return $result;
}
curl_close ($ch);
		
	}
function deleteAccount($account) {
		global $wpdb;
		
$ch = curl_init();
$timestamp = round(microtime(true) * 1000);
$secret = get_option('commas_api_secret');
$key = get_option('commas_api_key');


$data = "account_id={$account}";
$querystring = '/public/api/ver1/accounts/'.$account.'/remove?'.$data;
$signature = hash_hmac('SHA256',$querystring ,$secret);
curl_setopt($ch, CURLOPT_URL, "https://api.3commas.io/public/api/ver1/accounts/{$account}/remove");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'APIKEY: '.$key,
    'Signature: '.$signature,
	'Content-Type: application/x-www-form-urlencoded'
));

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
	return $result;
}
curl_close ($ch);	
	}
}

// GET 3COMMAS
/*
$ch = curl_init();
$timestamp = round(microtime(true) * 1000);
$secret = '5e0a162f7f2aa2b1e77bcd11cfcc10b0bfc388bf4c13fe88b635502ed1068b0130dfb566665a9edd9b7eef01a0fc81e5e401693b9766cee5b4bec2e728a6e1d0a7b5a3e06cc3d1f5351a7e7fe23d1b727a86d67a5260e32ef58694008013731032f3de30';

$querystring = '/public/api/ver1/accounts/market_list';
$signature = hash_hmac('SHA256',$querystring ,$secret);

curl_setopt($ch, CURLOPT_URL, "https://api.3commas.io/public/api/ver1/accounts/market_list");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_GET, 1);


curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'APIKEY: 5bf842735a8e43f48ee0b2f56916646802f27bbb27b14c80be2ae6bf94c08d83',
    'Signature: '.$signature,
	'Content-Type: application/x-www-form-urlencoded'
));

$result = curl_exec($ch);
print_r($result);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close ($ch);


$ch = curl_init();
$timestamp = round(microtime(true) * 1000);
$secret = '5e0a162f7f2aa2b1e77bcd11cfcc10b0bfc388bf4c13fe88b635502ed1068b0130dfb566665a9edd9b7eef01a0fc81e5e401693b9766cee5b4bec2e728a6e1d0a7b5a3e06cc3d1f5351a7e7fe23d1b727a86d67a5260e32ef58694008013731032f3de30';

$data = "name=devtest&type=binance&api_key=HJvQ334CyPrNWSY6rC6ZVDrdJRAZa8LocKU99wqIG85eJeeyI4qgz61gkqvRY75q&secret=mCJKzKXQqz2cGkhnLJ88mkQAhf3ln6T4xGTff0Pgy8kL3WxEkSj6hx49HtEy4wXR";
$querystring = '/public/api/ver1/accounts/new?'.$data;
$signature = hash_hmac('SHA256',$querystring ,$secret);
curl_setopt($ch, CURLOPT_URL, "https://api.3commas.io/public/api/ver1/accounts/new");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'APIKEY: 5bf842735a8e43f48ee0b2f56916646802f27bbb27b14c80be2ae6bf94c08d83',
    'Signature: '.$signature,
	'Content-Type: application/x-www-form-urlencoded'
));

$result = curl_exec($ch);
print_r($result);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close ($ch);
*/
?>