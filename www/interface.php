<?php 
session_start();
require("../functions.php");
$api = new IOTAPaymentGateway;

if (isset($_GET["logout"])) {
	session_destroy();
	header("Location: /account.php");
	die(0);
}

$id = $_SESSION["id"];
if (!is_numeric($id)) {
	header("Location: /account.php");
	die(1);
}

$data = $api->getAccountValues($id);
$data_payment = $api->getPaymentAccountValues($id);
$count = $api->countInvoicesByID($id);

if (!isset($_SESSION["total_balance"]) or isset($_GET["refresh"])) {
	$_SESSION["total_balance"] = $api->getAddressesTotal($id);
}
$total_balance = $_SESSION["total_balance"];

if (isset($_POST["ipn_url_new"])) {
	$result = $api->updateIPN($id, $_POST["ipn_url_new"]);
	$data = $api->getAccountValues($id);
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>PayIOTA - Interface</title>
		<?php include('header.php'); ?>
		<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />
		<style>
			div, p, h2 {
				text-align: center;
			}
		</style>

</head>
<body>

<?php 

echo "<h2 style='text-align: center;'>Welcome ".$data["email"]."!</h2><br><br>";

?>

<a href="?logout=true" id="logout">Logout</a>

<div>
<div class="success"></div>
<div class="warning"></div>
<div class="error"></div>
<div class="info"></div>
</div>

<div>
<?php 
echo "ID: ".$id."<br>";
echo "IOTA seed: ".$data["seed"]."<br>";
echo "API key: ".$data["api_key"]."<br>";
echo "Verification: ".$data["verification"]."<br>";
echo "Your IPN URL: ".$data["ipn_url"]."<br>";
echo "Number of addresses: ".$count."<br>";
echo "Total balance: ".$total_balance."<br>";
?> <a href="?refresh=true">Refresh Balance</a><br><br> <?php 
?>

<p>Update IPN URL:</p>
<form action="#" method="POST" onsubmit="return ValidateURL(this);">
	  <input required type="text" name="ipn_url_new" placeholder="new ipn url"/>
	  <br><br>
      <input type="submit" value="Update IPN"></li>  
</form>
<br>

</div>



<h2>Your API calls</h2><br>
<p>Scroll right on mobile!</p>

<table class="table table-striped table-responsive">
  <thead>
    <tr>
      <th>API Call #</th>
      <th>Address</th>
      <th>Price (USD)</th>
      <th>Price in IOTA</th>
      <th>Custom Variable</th>
      <th>Balance</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
	<?php 
	$count = 0;
	foreach ($data_payment as $key => $value) {
		echo "
		<tr>
		  <th scope='row'>".$count."</th>
		  <td><a href='https://iotasear.ch/hash/".$value[0]["address"]."' target='_blank'>".$value[0]["address"]."</a></td>
		  <td>$".$value[0]["price"]."</td>
		  <td>".$value[0]["price_iota"]."</td>
		  <td>".$value[0]["custom"]."</td>
		  <td>".$api->getAddressBalance($value[0]["address"])."</td>
		  <td>".$value[0]["done"]."</td>
		</tr>";
		$count++;
	}
	?>
  </tbody>
</table>

<script src="js/main.js"></script>

<script>

var success = document.getElementsByClassName("success")[0];
var error = document.getElementsByClassName("error")[0];
var info = document.getElementsByClassName("info")[0];
var warning = document.getElementsByClassName("warning")[0];

<?php 

if (isset($result)) {

    echo "".$api->matchCodeToType($result).".innerHTML='".$api->matchCodeToMessage($result)."';";
    echo "\n";
    echo "".$api->matchCodeToType($result).".style.display = 'block';";
}


?>

</script>

</body>
</html>