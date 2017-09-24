<?php
require("../functions.php");
$api = new IOTAPaymentGateway;

if (isset($_POST["api_key"])) {
	$id = $api->matchAPItoID($_POST["api_key"]);

	if (!is_int($id)) {
		echo "ERR_API_KEY_INVALID";
		die(0);
	} else {
		if ($_POST["action"] == "new") {
			$price = $_POST["price"];

			if (!is_numeric($price)) {
				echo "ERR_PRICE_INVALID";
				die(0);
			}

			$custom = $_POST["custom"];

			if (empty($custom)) {
				echo "ERR_CUSTOM_INVALID";
				die(0);
			}

			echo ($api->addPaymentToServer($id, $price, $custom));
		} elseif ($_POST["action"] == "update") {

			$address = $_POST["address"];
			$verification = $_POST["verification"];

			if (empty($address) or empty($verification)) {
				echo "ERR_INPUT_INVALID";
				die(0);
			}

			echo ($api->updatePriceForAddress($address, $verification, $id));


		}

	}

} else {
	die(1);
}

?>