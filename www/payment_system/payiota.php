<?php 
require("../../functions.php");
$api = new IOTAPaymentGateway;
$api_payments = new Payments;

if (isset($_POST["address"]) and isset($_POST["custom"]) and isset($_POST["verification"]) and isset($_POST["paid_iota"]) and isset($_POST["price_iota"])) {
	$address = $_POST["address"];
	$custom = explode(":", $_POST["custom"]);
	$verification = $_POST["verification"];
	$paid_iota = $_POST["paid_iota"];
	$price_iota = $_POST["price_iota"];

	if ($verification !== PAYIOTA_VERIFICATION_KEY) {
		$api->logEvent("ERR_FATAL_PAYMENT_IPN", "Verification key does not match PayIOTA's verification key, POSTed ".$verification);
		die(1);
	}

	//update in DB and notify user
	$api_payments->updateInvoiceToPaid($address);

	$email = $api->getAccountValues($custom[0])["email"];
	$api->sendEmail($email, "PayIOTA.me - Thank You For Paying",  "<html><body><p>Hi there!</p><p>Your invoice for user ID ".$custom[0].", for year ".$custom[1]." has been paid for (price ".$price_iota." IOTA) with ".$paid_iota." IOTA! Your subscription is valid until next year.</p><p>Best Regards,<br>PayIOTA.me</p></body></html>");
}


?>