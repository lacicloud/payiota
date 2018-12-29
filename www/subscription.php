<?php 
//Credits to https://codepen.io/g13nn/pen/LhCIg for base HTML+CSS
session_start();
require("../functions.php");
$api = new IOTAPaymentGateway;
$api_payments = new Payments;

$id = $_SESSION["id"];
if (!is_numeric($id)) {
	header("Location: /account.php");
	die(1);
}

//session expiration
if (isset($_SESSION['FIRST_ACTIVITY']) && (time() - $_SESSION['FIRST_ACTIVITY'] > 1800)) {
    session_destroy();
    header("Location: /account.php");
    die(0);
} elseif (!isset($_SESSION["FIRST_ACTIVITY"])) {
    $_SESSION['FIRST_ACTIVITY'] = time(); //first activity timestamp
}



?>
<!DOCTYPE html>
<html>
<head>
	<title>PayIOTA.me - Subscription Manager</title>
	<style type="text/css">
		body{
		  font-family: 'Open Sans', sans-serif;
		  margin: 0 auto 0 auto;  
		  width:100%; 
		  text-align:center;
		  margin: 20px 0px 20px 0px;   
		}

		h1{
		  font-size:1.5em;
		  color:#525252;
		}
	</style>
</head>
<body>
	<div style="float: left; margin-left: 5px"><button onclick="window.location = '/interface.php'">Go Back to Interface</button></div><br>
	<div style="text-align: center">
		<h2>PayIOTA.me - Subscription Manager</h2>

		<p>Your payment situation as follows:</p>
		<?php 

		$api_payments -> displayPaymentStatusToUser($id);

		?>

	
	</div>

</body>
</html>