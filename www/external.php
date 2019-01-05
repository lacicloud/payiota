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

//set language
if (!isset($_GET["lang"])) {
	$language = "en";
} else {
	$language = $_GET["lang"];
}

//new system is fully compatible with legacy version
if (isset($_GET["address"])) {

	//curl to API
	
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://payiota.me/api.php");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "action=getinvoice&invoice=".$_GET["address"]);
	curl_setopt($ch, CURLOPT_POST, 1);
	$result = curl_exec($ch);

	$price_iota = json_decode($result, true)["content"][0]["price_iota"];
	$address = json_decode($result, true)["content"][0]["address"];

	if (!isset($price_iota)) {
		echo "Error, no such invoice!";
		die(1);
	}

	echo "<h2>IOTA Payment</h2>";
	echo "<br>";
	echo "Please pay ".$price_iota." IOTAs to address ".$address." to complete the checkout!";
	echo "<br><br>";
	echo '	<div id="qrcode" ></div><script>new QRCode(document.getElementById("qrcode"), JSON.stringify ( { "address" : "'.$address.'", "amount" : "'.$price_iota.'", "tag" : "" } ) );</script>';
	echo "<br>";

	echo "<p id='payment_waiting_message'>Waiting for payment!</p>";
	echo "<p id='counter'></p>";
}


?>
</div>

<script>
first_run = true;

function checkPayment() {
	//get GET variables into javascript variables 
	var parts = window.location.search.substr(1).split("&");
	var $_GET = {};
	for (var i = 0; i < parts.length; i++) {
	    var temp = parts[i].split("=");
	    $_GET[decodeURIComponent(temp[0])] = decodeURIComponent(temp[1]);
	}

	//this script checks whether the payment is completed
	var xhttp = new XMLHttpRequest();
	xhttp.open("POST", "api.php", false);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send("action=checkinvoice&invoice=" + $_GET.address);

	if (xhttp.status === 200) {// That's HTTP for 'ok'
	  var data = xhttp.responseText;

	  if (!this.JSON) {  
	    alert("JSON not supported by browser.");
	  }  
	  
	  obj = JSON.parse(data);
	  var status = obj["content"][0]["done"];
	  var success_url = $_GET.success_url;
	  var cancel_url = $_GET.cancel_url;
	  var created = obj["content"][0]["created"];

	  if (status == 1)  {
	  	document.getElementById("payment_waiting_message").innerHTML = "<strong>Payment accepted! You are about to be redirected to " + success_url + "!</strong>";
	  	clearInterval(interval);
	  	setTimeout(function () {redirectTo(success_url);}, 3000);
	  //if expired then go to cancel url
	  } else if (status == 0) {
		var seconds = Math.floor(Date.now() / 1000);

		var difference = seconds - created;

		//1 week currently
		if (difference > 630427) {
			document.getElementById("payment_waiting_message").innerHTML = "<strong>Payment canceled due to time limit! You are about to be redirected to " + cancel_url + "!</strong>";
			clearInterval(interval);
			setTimeout(function () {redirectTo(cancel_url);}, 3000);
		}

		if (first_run) {
			var countdown_seconds = (630427 - difference);
			countdown(countdown_seconds);
			first_run = false;
		}
	

	  }

	}
		
}

function redirectTo(url) {
	window.location = url;
}

function countdown(seconds) {


  function tick() {
    seconds--; 
    var counter = document.getElementById("counter");
    var current_minutes = parseInt(seconds/60);
    var current_seconds = seconds % 60;
    counter.innerHTML = current_minutes + ":" + (current_seconds < 10 ? "0" : "") + current_seconds;
    if( seconds > 0 ) {
      setTimeout(tick, 1000);
    } 
  }
  tick();
}

var interval = null;

interval = setInterval(function() {
  checkPayment();
}, 5000)
</script>

</body>
</html>
