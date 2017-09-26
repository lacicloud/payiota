<?php 
//get your key(s) from DB or define at top of script
define("PAYIOTA_API_KEY", "");
define("PAYIOTA_VERIFICATION_STRING", "");

//address you want to update
$address = "";

$request = array(
	"api_key" => PAYIOTA_API_KEY
	"verification" => PAYIOTA_VERIFICATION_STRING,
	"address" => $address,
	"action" => "update"
	);

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$request = http_build_query($request);

curl_setopt($curl,CURLOPT_POST, 1);
curl_setopt($curl,CURLOPT_POSTFIELDS, $request);

curl_setopt($curl, CURLOPT_URL, 'https://payiota.me/api.php');
$response = curl_exec($curl);

//new price in integers
echo $response; 
?>