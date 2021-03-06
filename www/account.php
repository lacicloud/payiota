<?php 
session_start();
require("../functions.php");
require("securimage/securimage.php");
$api = new IOTAPaymentGateway;

if (isset($_SESSION["logged_in"]) and $_SESSION["logged_in"] == 1) {
  header("Location: /interface.php");
}

if (isset($_POST["email"]) and isset($_POST["password"]) and isset($_POST["captcha_code"]) and !isset($_POST["password_retyped"])) {
	$securimage = new Securimage();
	if ($securimage->check($_POST['captcha_code']) == false) {
	  $result = "ERR_CAPTCHA";
	} else {
	  $result = $api->loginUser($_POST["email"], $_POST["password"]);
	  if (is_numeric($result)) {
	  	$_SESSION["id"] = $result;
	  	header("Location: /interface.php");
	  	die(0);
	  } else {
	  	//do nothing
	  }
	}

	
}

if (isset($_GET["confirm"])) {
	if ($_GET["confirm"] == "ok") {
		$result = "ERR_CONFIRM_OK";
	} else {
		$result = "ERR_KEY_WRONG";
	}
}

if (isset($_GET["result"])) {
	$result = "ERR_RESET_STEP_2_OK";
}

// Registration Code
if (isset($_POST["email"]) and isset($_POST["password"]) and isset($_POST["password_retyped"]) and isset($_POST["captcha_code"]) and !isset($_POST["reset_key"])) {
	//check captcha
	$securimage = new Securimage();

	if ($securimage->check($_POST['captcha_code']) == false) {
	  $result = "ERR_CAPTCHA";
	} else {
	  $result = $api->createAccount($_POST["email"], $_POST["password"], $_POST["password_retyped"]);
	}

}

//forgot step 1
if (isset($_POST["email"]) and !isset($_POST["password"]) and !isset($_POST["password_retyped"]) and isset($_POST["captcha_code"]) ) {
	$securimage = new Securimage();

	if ($securimage->check($_POST['captcha_code']) == false) {
	  $result = "ERR_CAPTCHA";
	} else {
	  $result = $api->forgotLoginStep1($_POST["email"]);
	}
}

//forgot step 2
if (isset($_POST["reset_key"]) and isset($_POST["password"]) and isset($_POST["password_retyped"]) and isset($_POST["captcha_code"])) {
	$securimage = new Securimage();

	if ($securimage->check($_POST['captcha_code']) == false) {
	  $result = "ERR_CAPTCHA";
	} else {
	  $result = $api->forgotLoginStep2($_POST["reset_key"], $_POST["password"], $_POST["password_retyped"]);
	}
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>PayIOTA.me - Account</title>
		<?php include('header.php'); ?>

	</head>
	<body>
		<script type="text/javascript">

			var xhr = new XMLHttpRequest();
			xhr.open("GET", "https://payiota.me/securimage/securimage_show.php");
			xhr.responseType = "blob";
			xhr.onload = response;
			xhr.send();

			function response(e) {
			   var urlCreator = window.URL || window.webkitURL;
			   var imageUrl = urlCreator.createObjectURL(this.response);
			   document.querySelector("#captcha_register").src = imageUrl;
			   document.querySelector("#captcha_login").src = imageUrl;
			   document.querySelector("#captcha_forgot").src = imageUrl;
			}

		</script>
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
					<a class="nav-link <?php if(!isset($_GET['register']) and !isset($_GET['forgot_1']) and !isset($_GET['forgot_2'])) { echo 'active';} ?>" id="login-tab" data-toggle="tab" href="#login" role="tab" aria-controls="login" aria-expanded="true">Login</a>
				  </li>
				  <li class="nav-item">
					<a class="nav-link <?php if(isset($_GET['register'])) { echo 'active';} ?>" id="register-tab" data-toggle="tab" href="#register" role="tab" aria-controls="register">Sign up</a>
				  </li>
				   <li class="nav-item">
				  	<a class="nav-link <?php if(isset($_GET['forgot_1']) or isset($_GET['forgot_2'])) { echo 'active';} ?>" id="forgot-tab" data-toggle="tab" href="#forgot" role="tab" aria-controls="forgot">Forgot Login</a>
				   </li>
				</ul>
				<div class="tab-content" id="myTabContent">
				  <div class="tab-pane fade <?php if(!isset($_GET['register']) and !isset($_GET['forgot_1']) and !isset($_GET['forgot_2'])) { echo 'show active';} ?>" id="login" role="tabpanel" aria-labelledby="login-tab">
					<form action="/account.php" method="POST" onsubmit="return ValidateLogin(this);">
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
						<div class="form-group captcha_area">
						<label for="password">Captcha</label>
						  <input required type="text" name="captcha_code" size="10" maxlength="6" placeholder="Captcha Code"/>
						  <img id="captcha_login" alt="CAPTCHA Image" />
						  <a href="#" onclick="document.getElementById('captcha').src = 'securimage/securimage_show.php?' + Math.random(); return false">[ Different Image ]</a>
						</div>
						<input type="submit" value="Sign in"> 
					</form>
					<a href="/account.php?forgot_1">Forgot password?</a>
				  </div>
				  <div class="tab-pane fade" id="register" role="tabpanel" aria-labelledby="register-tab">
					<form action="/account.php?register" method="POST" onsubmit="return ValidateRegister(this);">
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
						  <img id="captcha_register"  alt="CAPTCHA Image" />
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

				  <?php if (isset($_GET["forgot_2"])) { ?>
				  	  <div class="tab-pane fade  <?php if(isset($_GET['forgot_2'])) { echo 'show active';} ?>" id="forgot" role="tabpanel" aria-labelledby="forgot-tab">
				  		<form action="/account.php?forgot_2&reset_key=<?php echo $_GET['reset_key'] ?>" method="POST" onsubmit="return ValidateForgotStep2(this);">
				  			<div class="form-group">
				  				<label for="email-address">Reset Key</label>
				  				<input type="text" id="email-address" class="form-control" name="reset_key" <?php echo (isset($_GET["reset_key"]) == true) ? "value='".$_GET["reset_key"]."'" : "" ?> required>

				  			</div>	
				  			<input type="hidden" name="email" value="<?php echo $api->matchResetKeyToEmail($_GET['reset_key']) ?>">
				  			<div class="form-row">
				  				<div class="form-group col-md-6">
				  					<label for="register_password" class="col-form-label">New Password</label>
				  					<input type="password" id="forgot_password" name="password" class="form-control" placeholder="*******" required>
				  				</div>
				  				<div class="form-group col-md-6">
				  					<label for="register_password_retype" class="col-form-label">Retype New Password</label>
				  					<input type="password" id="forgot_password_retype" name="password_retyped" class="form-control" placeholder="*******" required>
				  				</div>
				  			</div>

				  			<div class="form-group captcha_area">
				  			<label for="password">Captcha</label>
				  			  <input required type="text" name="captcha_code" size="10" maxlength="6" placeholder="Captcha Code"/>
				  			  <img id="captcha_forgot"  alt="CAPTCHA Image" />
				  			  <a href="#" onclick="document.getElementById('captcha').src = 'securimage/securimage_show.php?' + Math.random(); return false">[ Different Image ]</a>
				  			</div>

				  			<input type="submit" value="Reset My Account"></li>  
				  		</form>
				  	  </div>
				  <?php } else { ?> 
				    <div class="tab-pane fade  <?php if(isset($_GET['forgot_1'])) { echo 'show active';} ?>" id="forgot" role="tabpanel" aria-labelledby="forgot-tab">
				  	<form action="/account.php?forgot_1" method="POST" onsubmit="return ValidateForgotStep1(this);">
				  		<div class="form-group">
				  			<label for="email-address">Email Address</label>
				  			<input type="email" id="email-address" class="form-control" name="email" placeholder="you@yourdomain.com" required>
				  		</div>	
				  		<div class="form-group captcha_area">
				  		<label for="password">Captcha</label>
				  		  <input required type="text" name="captcha_code" size="10" maxlength="6" placeholder="Captcha Code"/>
				  		  <img id="captcha_forgot"  alt="CAPTCHA Image" />
				  		  <a href="#" onclick="document.getElementById('captcha').src = 'securimage/securimage_show.php?' + Math.random(); return false">[ Different Image ]</a>
				  		</div>

				  		<input type="submit" value="Reset My Account"></li>  
				  	</form>
				    </div>
				   <?php } ?>

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

			if (isset($result) and $result == "ERR_RESET_STEP_2_OK" and !isset($_GET["redirect"])) {
				echo "window.location = '/account.php?result=ERR_RESET_STEP_2_OK&redirect'";
			}

			?>
		</script>
	</body>
</html>