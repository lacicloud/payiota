<!DOCTYPE html>
<html>
<head>
	<title>PayIOTA.me Payment Gateway</title>
	<script src="/js/qrcode.js"></script>
		<style>
			
			img {
				 display: block; 
				 margin: 0 auto;
			}
		</style>
</head>
<body>
<div style="text-align: center;">
<?php 
$_GET = array_map('htmlspecialchars', $_GET);

echo "Please pay ".$_GET["price"]." IOTAs to address ".$_GET["address"]." !";
echo "<br>";
echo '	<div id="qrcode" ></div><script>new QRCode(document.getElementById("qrcode"), JSON.stringify ( { "address" : "'.$_GET["address"].'", "amount" : "'.$_GET["price"].'", "tag" : "" } ) );</script>';
echo "<br>";
echo "<a href='".$_GET["success_url"]."'>Click here if payment is confirmed on the IOTA network!</a>";
echo "<br>";
echo "<a href='".$_GET["cancel_url"]."'>Click here to cancel!</a>";

?>
</div>
</body>
</html>
