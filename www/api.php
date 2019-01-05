<?php
require("../functions.php");
$api = new IOTAPaymentGateway;

//prevent errors from showing for the public facing API (undefined notices mostly) to maintain the API's programmed error mechanism
ini_set('display_errors', 0); 

if (isset($_POST["api_key"])) {
	$id = $api->matchAPItoID($_POST["api_key"]);

	if ($id == "ERR_API_KEY_DISABLED") {
		echo "ERR_API_KEY_DISABLED";
		die(1);
	}

	if (!is_int($id)) {
		echo "ERR_API_KEY_INVALID";
		die(1);
	} else {
		if (isset($_POST["action"]) and $_POST["action"] == "new") {
			$price = $_POST["price"];

			if (!is_numeric($price)) {
				echo "ERR_PRICE_INVALID";
				die(1);
			}

			$custom = $_POST["custom"];

			if (empty($custom)) {
				echo "ERR_CUSTOM_INVALID";
				die(1);
			}

			if (isset($_POST["currency"]) and $_POST["currency"] !== "USD") {
					$price = $api->convertCurrency($price, $_POST["currency"], "USD");
			}

			if (!is_numeric($price) or $price == 0 or $price == "ERR_FATAL_3RD_PARTY") {
				echo "ERR_FATAL_3RD_PARTY";
				die(1);
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
				die(1);
			}

			echo ($api->updatePriceForAddress($address, $verification, $id));

		} else {
			echo "ERR_PARAMETERS_MISSING";
			die(1);
		}

	}
} elseif (isset($_GET["action"]) and $_GET["action"] == "convert_to_usd" and isset($_GET["iota"])) {
	echo $api->getUSDPrice($_GET["iota"]);
} elseif (isset($_GET["action"]) and $_GET["action"] == "getnumberofusers") {
	echo $api->getNumberOfUsers();
} elseif (isset($_GET["action"]) and $_GET["action"] == "getpaymentstatistics") {
	echo $api->getPaymentStatistics();
} elseif (isset($_POST["action"]) and $_POST["action"] == "getinvoice") {
	
	if (!isset($_POST["invoice"])) {
		echo "ERR_PARAMETERS_MISSING";
		die(1);
	}

	$result = $api->getInvoice($_POST["invoice"]);

	if (!is_array($result)) {
		echo "ERR_NOT_FOUND";
		die(1);
	} else {
		echo $api->returnJSONApi("ERR_OK", $result);
	}

} elseif (isset($_POST["action"]) and $_POST["action"] == "checkinvoice") {

	if (!isset($_POST["invoice"])) {
		echo "ERR_PARAMETERS_MISSING";
		die(1);
	}

	$data = $api->getInvoice($_POST["invoice"]);

	if (!is_array($data)) {
		echo "ERR_NOT_FOUND";
		die(1);
	} else {
		$result = $api->checkAddress($data);
		echo $api->returnJSONApi("ERR_OK", $result);
	}


} else {
	echo "ERR_METHOD_INCORRECT";
	die(1);
}

?>