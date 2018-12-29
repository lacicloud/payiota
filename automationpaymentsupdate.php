<?php 
require("functions.php");
$api = new IOTAPaymentGateway;
$api_payments  = new Payments;
$api_payments->updateInvoicesForUsers();
?>
