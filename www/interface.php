<?php 
session_start();
require("../functions.php");
$api = new IOTAPaymentGateway;

if (isset($_GET["logout"])) {
	session_destroy();
	header("Location: /account.php");
	die(0);
}

//session expiration
if (isset($_SESSION['FIRST_ACTIVITY']) && (time() - $_SESSION['FIRST_ACTIVITY'] > 1800)) {
    session_destroy();
    header("Location: /account.php");
    die(0);
} elseif (!isset($_SESSION["FIRST_ACTIVITY"])) {
    $_SESSION['FIRST_ACTIVITY'] = time(); //first activity timestamp
}


$id = $_SESSION["id"];
if (!is_numeric($id)) {
	header("Location: /account.php");
	die(1);
}

$data = $api->getAccountValues($id);
$data_payment = $api->getPaymentAccountValues($id);
$count = $api->countInvoicesByID($id);
$seed = "*********************************************************************************";
$hidden = true;

if (isset($_POST["viewseed"]) and isset($_POST["password"])) {
	
	$db = $api->getDB();
	$sql = "SELECT password FROM users WHERE id = :id";
	$stmt = $db->prepare($sql);
	$stmt->bindParam(":id", $id);
	$stmt->execute();

	$password = key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));
	$password_entered = $api->hashPassword($_POST["password"]);

	if ($password == $password_entered) {
		$seed = $data["seed"];
		$hidden = false;
	} else {
		$result = "ERR_LOGIN_INCORRECT";
		$hidden = true;
	}

}



if (!isset($_SESSION["total_balance"]) or isset($_GET["refresh"])) {
	$_SESSION["total_balance"] = $api->getAddressBalances($id);
}

if (isset($_GET["regenerate"])) {
	$api->regenerateAPIKey($id);
	header("Location: /interface.php?refresh=true");
	die(0);
}

if (isset($_GET["delete"])) {
	$api->deleteAccount($id);
	header("Location: /account.php");
	die(0);
}

$balance_array = $_SESSION["total_balance"];

$total_balance = $balance_array[0];
$detailed_balance = $balance_array[1];



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
		  <tr><td>IOTA seed:</td><td class="api_data"><?php echo $seed;?></td></tr>
		  <tr><td>API key:</td><td class="api_data"><?php echo $data["api_key"];?></td></tr>
		  <tr><td>Verification:</td><td class="api_data"><?php echo $data["verification"];?></td></tr>
		  <tr><td>Number of addresses:</td><td class="api_data"><?php echo $count;?></td></tr>
		  <tr><td>Total balance:</td><td class="api_data"><?php echo $total_balance; ?> &nbsp (IOTA)</td></tr>
		</table>

		<a href="?refresh=true">Refresh Data</a>
		<?php if ($hidden == true) { echo "<a onclick='return ViewSeed()' href='#'>View Seed</a>"; } else { echo "<a href='/interface.php'>Hide Seed</a>"; } ?>
		<a href="?regenerate=true"  onclick='return ValidateAPIKeyRegeneration()'>Regenerate API Key</a>
		<a href="?hide_empty=true">Hide Empty</a>
		<a href="?hide_empty=false">Unhide Empty</a>
		<a href="/subscription.php">Payment Manager</a>
		<a href="?delete=true" onclick='return ValidateAccountDelete()'>Delete Account</a>

	</div>
</section>
<!-- /.content-section-b -->


<section class="api_payments">
<h2>Your API calls</h2><br>
<p>Scroll right on mobile!</p>

<table class="table table-striped table-responsive">
  <thead>
    <tr>
      <th>API Call # and Invoice</th>
      <th>Address</th>
      <th>Price</th>
      <th>Price in IOTA</th>
      <th>Custom Variable</th>
      <th>Created</th>
       <th>Expiration</th>
      <th>Balance</th>
      <th>Status</th>
      <th>Paid</th>
    </tr>
  </thead>
  <tbody>
	<?php 
	$count = 0;
	foreach ($data_payment as $key => $value) {
		
		if ($value[0]["done"] == 1) {
			$balance = $detailed_balance[$value[0]["address"]];
		} else {
			$balance = 0;
		}
		
		if ($balance == 0 and isset($_GET["hide_empty"]) and $_GET["hide_empty"] == "true") {
			continue;
		}

		if ($value[0]["done"] == 1) {
			$status = "TX";
		} else {
			$status = "NOTX";
		}
		

		echo "
		<tr>
		  <th scope='row'><a href='https://payiota.me/external.php?address=".$value[0]["address"]."&success_url=/interface.php&cancel_url=/interface.php'>".$count."</a></th>
		  <td><a href='https://iotasear.ch/hash/".$value[0]["address"]."' target='_blank'>".$value[0]["address"]."</a></td>
		  <td><span>Price</span>		".$value[0]["price"]." (".$value[0]["currency"].")</td>
		  <td><span>Price in IOTA</span>	".$value[0]["price_iota"]."</td>
		  <td><span>Custom Variable</span>	".$value[0]["custom"]."</td>
		  <td><span>Created</span>	".$value[0]["created"]."</td>
		   <td><span>Expiration</span>	".$value[0]["expiration"]."</td>
		  <td><span>Balance</span>			". $balance  ."</td>
		  <td><span>Status</span>			". $status ."</td>
		  <td><span>Paid</span>			".$value[0]["done"]."</td>
		</tr>";
		$count++;
	}
	?>
  </tbody>
</table>
</section>

<script>
	var success = document.getElementsByClassName("success")[0];
	var error = document.getElementsByClassName("error")[0];
	var info = document.getElementsByClassName("info")[0];
	var warning = document.getElementsByClassName("warning")[0];

    if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
    }

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