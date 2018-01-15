<?php
require("../functions.php");
$api = new IOTAPaymentGateway;

if (isset($_POST["api_key"])) {
	$id = $api->matchAPItoID($_POST["api_key"]);

	if (!is_int($id)) {
		echo "ERR_API_KEY_INVALID";
		die(0);
	} else {
		if (@$_POST["action"] == "new") {
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

			if (isset($_POST["currency"]) and $_POST["currency"] !== "USD") {
					$price = $api->convertCurrency($price, $_POST["currency"], "USD");
			}

			if (isset($_POST["ipn_url"])) {
				$ipn_url = $_POST["ipn_url"];
			}
			
			echo ($api->addPaymentToServer($id, $price, $custom, $ipn_url));
		} elseif (@$_POST["action"] == "update") {

			$address = $_POST["address"];
			$verification = $_POST["verification"];

			if (empty($address) or empty($verification)) {
				echo "ERR_INPUT_INVALID";
				die(0);
			}

			echo ($api->updatePriceForAddress($address, $verification, $id));

		} else {
			echo "ERR_PARAMETERS_MISSING";
		}

	}
} elseif ($_GET["action"] == "convert_to_usd" and isset($_GET["iota"])) {
	echo $api->getUSDPrice($_GET["iota"]);
} else {
	die(1);
}

?>