<?php 
session_start();
require("../functions.php");
require("securimage/securimage.php");
$api = new IOTAPaymentGateway;

if (isset($_POST["email"]) and isset($_POST["password"]) and !isset($_POST["password_retyped"])) {
	$result = $api->loginUser($_POST["email"], $_POST["password"]);
	if (is_numeric($result)) {
		$_SESSION["logged_in"] = 1;
		$_SESSION["id"] = $result;
		header("Location: /interface.php");
		die(0);
	} else {
		//do nothing
	}
}

if (isset($_GET["confirm"])) {
	if ($_GET["confirm"] == "ok") {
		$result = "ERR_CONFIRM_OK";
	} else {
		$result = "ERR_KEY_WRONG";
	}
}

// Registration Code
if (isset($_POST["email"]) and isset($_POST["password"]) and isset($_POST["password_retyped"]) and isset($_POST["captcha_code"])) {
	//check captcha
	$securimage = new Securimage();

	if ($securimage->check($_POST['captcha_code']) == false) {
	  $result = "ERR_CAPTCHA";
	} else {
	  $result = $api->createAccount($_POST["email"], $_POST["password"]);
	}

}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>PayIOTA - Account</title>
		<?php include('header.php'); ?>

	</head>
	<body>
		<div class="container-fluid">
		<div class="row">
			<div class="col-md-7 col-12 login_hero" style="background:url('resources/hero-<?php echo rand(1,9);?>.jpg'); background-size: cover;">
				<ul class="menu hero_menu">
					<li><a href="https://github.com/lacicloud/payiota/wiki/PayIOTA-API-Documentation">Read the docs</a></li>
					<li><a href="account.php?register">Sign-up</a></li>
					<li><a href="account.php">Log-in</a></li>
				</ul>
				<div id="hero_text">
					<h1>ZERO Transaction Fees</h1>
					<p>Payment Gateway Built For The Modern Web</p>
				</div>
			</div>
			<div class="col-md-5 col-12 login_details">
				<a href="/" class="logo">
				<!--<img src="resources/iota-logo.png">-->
				<img src="resources/payiota_logo3.png">
				<span>Payment Gateway</span></a>
				<!--<h1>Pay With IOTA</h1>-->
				<div class="row">
					<div class="success"></div>
					<div class="warning"></div>
					<div class="error"></div>
					<div class="info"></div>
				</div>
				
				<ul class="nav nav-pills nav-justified nav-login" id="myTab" role="tablist">
				  <li class="nav-item">
					<a class="nav-link <?php if(!isset($_GET['register'])) { echo 'active';} ?>" id="login-tab" data-toggle="tab" href="#login" role="tab" aria-controls="login" aria-expanded="true">Login</a>
				  </li>
				  <li class="nav-item">
					<a class="nav-link <?php if(isset($_GET['register'])) { echo 'active';} ?>" id="register-tab" data-toggle="tab" href="#register" role="tab" aria-controls="register">Sign up</a>
				  </li>
				</ul>
				<div class="tab-content" id="myTabContent">
				  <div class="tab-pane fade <?php if(!isset($_GET['register'])) { echo 'show active';} ?>" id="login" role="tabpanel" aria-labelledby="login-tab">
					<form action="#" method="POST" onsubmit="return ValidateLogin(this);">
						<div class="form-group">
							<label for="email-address">Email Address</label>
							<input type="email" id="email-address" class="form-control" name="email" placeholder="you@yourdomain.com" required>
						</div>
						<div class="form-row">
							<div class="form-group col">
							  <label for="register_password" class="col-form-label">Password</label>
								<input type="password" id="inputPassword" name="password" class="form-control" placeholder="Password" required>
							</div>
						</div>
						<input type="submit" value="Sign in"> 
					</form>
					<a onclick="alert('Please contact support@payiota.me to reset your password!')" href="#">Forgot password?</a>
				  </div>
				  <div class="tab-pane fade" id="register" role="tabpanel" aria-labelledby="register-tab">
					<form action="#" method="POST" onsubmit="return ValidateRegister(this);">
						<div class="form-group">
							<label for="email-address">Email Address</label>
							<input type="email" id="email-address" class="form-control" name="email" placeholder="you@yourdomain.com" required>
						</div>	
						<div class="form-row">
							<div class="form-group col-md-6">
								<label for="register_password" class="col-form-label">Password</label>
								<input type="password" id="register_password" name="password" class="form-control" placeholder="*******" required>
							</div>
							<div class="form-group col-md-6">
								<label for="register_password_retype" class="col-form-label">Retype Password</label>
								<input type="password" id="register_password_retype" name="password_retyped" class="form-control" placeholder="*******" required>
							</div>
						</div>
						<div class="form-group captcha_area">
						<label for="password">Captcha</label>
						  <input required type="text" name="captcha_code" size="10" maxlength="6" placeholder="Captcha Code"/>
						  <img id="captcha" src="securimage/securimage_show.php" alt="CAPTCHA Image" />
						  <a href="#" onclick="document.getElementById('captcha').src = 'securimage/securimage_show.php?' + Math.random(); return false">[ Different Image ]</a>
						</div>
						  
						<div class="form-check tos_agreement">
						  <label>
							<input required type="checkbox" name="checkbox" id="terms_and_conditions_checkbox" value=""/>
							I agree to the <a href="/resources/payiota_legal.pdf" target="_blank">Terms and Conditions</a>
						  </label>
						</div>
						<input type="submit" value="Register My Account"></li>  
					</form>
				  </div>
				</div>
			</div>
		</div>
		</div>
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
			<?php if(isset($_GET['register'])) { 
			
				echo "
				
				$(function() {
    $('#register').tab('show');
});
";
			} 
			?>
		</script>
	</body>
</html>