<?php 
//get your key(s) from DB or define at top of script
define("PAYIOTA_VERIFICATION_STRING", "");

if (isset($_POST["address"])) {
	$address = $_POST["address"];
	$custom = $_POST["custom"];
	$verification = $_POST["verification"];
	$paid_iota = $_POST["paid_iota"];
	$price_iota = $_POST["price_iota"];
	//for more variables see documentation

	if ($verification !== PAYIOTA_VERIFICATION_STRING) {
		die(1);
	}

	//OK, do something with values
	//such as insert into DB

}


?>