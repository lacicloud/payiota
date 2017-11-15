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
	<meta property="og:image" content="https://payiota.me/resources/payiota_icon.png"/>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
  <div class="container">
	<a class="navbar-brand" href="/"><img src="resources/payiota_logo3.png" height="40" alt=""></a>
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
	  <span class="navbar-toggler-icon"></span>
	</button>
	<div class="collapse navbar-collapse" id="navbarResponsive">
	  <ul class="navbar-nav ml-auto">
		<li class="nav-item">
		  <strong class="nav-link account_name"><?php echo $data["email"];?></strong>
		</li>
		<li class="nav-item">
		  <a class="nav-link" href="mailto:support@payiota.me">Support</a>
		</li>
		<li class="nav-item">
		  <a class="nav-link" href="https://github.com/lacicloud/payiota/wiki/PayIOTA-API-Documentation">Documents</a>
		</li>
		<li class="nav-item">
		  <a class="nav-link" href="?logout=true">Logout</a>
		</li>
	  </ul>
	</div>
  </div>
</nav>
	
<section class="content-section-b api_details">
	<div class="warning_list">
		<div class="success"></div>
		<div class="warning"></div>
		<div class="error"></div>
		<div class="info"></div>
	</div>

	<div class="api_list">
		<table>
		  <tr><td>ID:</td><td class="api_data"><?php echo $id;?></td></tr>
		  <tr><td>IOTA seed:</td><td class="api_data"><?php echo $data["seed"];?></td></tr>
		  <tr><td>API key:</td><td class="api_data"><?php echo $data["api_key"];?></td></tr>
		  <tr><td>Verification:</td><td class="api_data"><?php echo $data["verification"];?></td></tr>
		  <tr><td>Your IPN URL:</td><td class="api_data">
		  <?php if($data["ipn_url"]) { ?>
			<a href="<?php echo $data["ipn_url"];?>"><?php echo $data["ipn_url"];?></a>
		  <?php } else { ?>
			<a class="no_ipn" href="#">No IPN URL Added</a>
		  <?php } ?>
		  </td></tr>
		  <tr><td>Number of addresses:</td><td class="api_data"><?php echo $count;?></td></tr>
		  <tr><td>Total balance:</td><td class="api_data"><?php echo $total_balance;?> MI</td></tr>
		</table>

		<a href="?refresh=true">Refresh Balance</a>

		<h4>Update IPN URL:</h4>
		<form action="#" method="POST" onsubmit="return ValidateURL(this);">
			  <input required class="ipn_url_new" type="text" name="ipn_url_new" placeholder="New IPN URL Address"/>
			  <input type="submit" value="Update IPN"></li>  
		</form>
	</div>
</section>
<!-- /.content-section-b -->


<section class="api_payments">
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
		  <td><span>Price (USD)</span>		$".$value[0]["price"]."</td>
		  <td><span>Price in IOTA</span>	".$value[0]["price_iota"]."</td>
		  <td><span>Custom Variable</span>	".$value[0]["custom"]."</td>
		  <td><span>Balance</span>			".$api->getAddressBalance($value[0]["address"])."</td>
		  <td><span>Status</span>			".$value[0]["done"]."</td>
		</tr>";
		$count++;
	}
	?>
  </tbody>
</table>
</section>

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