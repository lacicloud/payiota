<?php 
require("../functions.php");
$api = new IOTAPaymentGateway;

if (isset($_GET["key"]) and !empty($_GET["key"]) and $_GET["key"] !== "1") {
	$result = $api->confirmAccount($_GET["key"]);
	if ($result == "ERR_CONFIRM_OK") {
		header("Location: /account.php?confirm=ok");
	} else {
		header("Location: /account.php?confirm=error");
	}
}

?>