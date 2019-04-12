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

		//fix for old clients still running until email is sent out
		if (!isset($_POST["expiration"])) {
			$_POST["expiration"] = 630427;
		}	

			echo ($api->addPaymentToServer($id, $_POST["price"], $_POST["currency"], $_POST["expiration"], $_POST["custom"], $_POST["ipn_url"]));
			die(0);
		} elseif (@$_POST["action"] == "update") {

			$address = $_POST["invoice"];
			$verification = $_POST["verification"];
			$expiration = $_POST["expiration"];

			if (empty($address) or empty($verification) or empty($expiration)) {
				echo "ERR_INPUT_INVALID";
				die(1);
			}

			echo ($api->updateAddress($address, $expiration, $verification, $id));
			die(0);

		} else {
			echo "ERR_PARAMETERS_MISSING";
			die(1);
		}

	}
} elseif (isset($_GET["action"]) and $_GET["action"] == "getnumberofusers") {
	echo $api->getNumberOfUsers();
} elseif (isset($_GET["action"]) and $_GET["action"] == "getpaymentstatistics") {
	echo $api->getPaymentStatistics();
} elseif (isset($_POST["action"]) and $_POST["action"] == "getinvoice") {
	
	if (!isset($_POST["invoice"])) {
		echo "ERR_PARAMETERS_MISSING";
		die(1);
	}

	$result = $api->getInvoice($_POST["invoice"], false);

	if (!is_array($result)) {
		echo "ERR_NOT_FOUND";
		die(1);
	} else {
		echo $api->returnJSONApi("ERR_OK", $result);
		die(0);
	}

} elseif (isset($_POST["action"]) and $_POST["action"] == "checkinvoice") {

	if (!isset($_POST["invoice"])) {
		echo "ERR_PARAMETERS_MISSING";
		die(1);
	}

	$data = $api->getInvoice($_POST["invoice"], true);

	if (!is_array($data)) {
		echo "ERR_NOT_FOUND";
		die(1);
	} else {
		$result = $api->checkAddress($data);
		if (is_array($result)) {
			echo $api->returnJSONApi("ERR_OK", $result);
		} else {
			echo $api->returnJSONApi("ERR_INVOICE_EXPIRED", $result);
		}
		
		die(0);
	}


} else {
	echo "ERR_METHOD_INCORRECT";
	die(1);
}

?>