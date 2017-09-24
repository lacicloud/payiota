<?php 
require("../functions.php");
$api = new IOTAPaymentGateway;
$api->getAddressesToMonitor();
?>