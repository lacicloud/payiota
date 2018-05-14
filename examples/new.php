<?php 
//get your key(s) from DB or define at top of script
define("PAYIOTA_API_KEY", "");

//1 USD
$price = "1";
//whatever you want for identifying your users or passing back data to your payment processor. This cannot be empty.
$custom = "";

//If no default IPN url is set in PayIOTA interface, you will have to specify if here.
$request = array(
	"api_key" => PAYIOTA_API_KEY,
	"price" => $price,
	"currency" => "USD",
	"custom" => $custom,
	"action" => "new",
	"ipn_url" => "https://example.com/ipn.php"
	);

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$request = http_build_query($request);

curl_setopt($curl,CURLOPT_POST, 1);
curl_setopt($curl,CURLOPT_POSTFIELDS, $request);

curl_setopt($curl, CURLOPT_URL, 'https://payiota.me/api.php');
$response = curl_exec($curl);

$response = json_decode($response, true);
echo $response[0]; //address
echo $response[1]; //price in IOTA's
?>
