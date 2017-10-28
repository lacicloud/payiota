<?php 
require("libs/simple_html_dom.php");
require("libs/SwiftMailer/lib/swift_required.php");

define('ROOT', dirname(__FILE__));
define('SALT', "12345678");
define("EMAIL_USERNAME", "username");
define("EMAIL_PASSWORD", "password");
define("MYSQL_USERNAME", "username");
define("MYSQL_PASSWORD", "password");

ini_set('default_socket_timeout', 7);
ignore_user_abort(true);

set_exception_handler(function ($e) {
	chdir(ROOT);
	error_log("Unhandled exception occured: ".$e, 3, "logs/payiota.log");
	echo "Sorry, a fatal error has occured, service is unavailable!";
	die(1);
});

ini_set('display_errors', 0); 

class IOTAPaymentGateway {

	public function getWorkingNode() {
		$nodes = array(
			"http://iota.bitfinex.com", //port 80
			"http://service.iotasupport.com:14265",
			"http://eugene.iota.community:14265",
			"http://eugene.iotasupport.com:14999",
			"http://eugeneoldisoft.iotasupport.com:14265",
			"http://node01.iotatoken.nl:14265",
			"http://node02.iotatoken.nl:14265"
			);

		foreach ($nodes as $key => $value) {
			if ($this->isNodeOnline($value)) {
				$working = $value;
				break;
			}
		}

		if (!isset($working)) {
			$this->logEvent("ERR_FATAL_3RD_PARTY", "Fatal, no nodes are working: ".$working);
		} else {
			return $working;
		}

	}

	function isNodeOnline($url)
	{
		//knock knock
	    $ch = curl_init($url);
	    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 3);
	    curl_setopt($ch,CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

	    //who's there
	    $response = curl_exec($ch);

	    curl_close($ch);

	    //error
	    if (!$response) {
	    	return false;
	    }

	    if ($response !== '{"error":"Invalid API Version","duration":0}') {
	    	return false;
	    }

	    //else OK
	    return true;
	}

	public function setupDB() {
		$db =  $this->getDB();

		$sql = "CREATE TABLE users (id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT, seed TEXT, email TEXT, password TEXT, api_key TEXT, ipn_url TEXT, verification TEXT, confirmed TEXT, count INTEGER)";
		$statement = $db->prepare($sql);
		$statement->execute();

		$sql ='CREATE TABLE payments (id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT, address TEXT, realID INTEGER, price INTEGER, price_iota INTEGER, custom TEXT, ipn_url TEXT, verification TEXT, done INTEGER, created INTEGER)';
		$statement = $db->prepare($sql);
		$statement->execute();

		$sql ='CREATE TABLE pregeneration (id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT, realID INTEGER, address TEXT, position INTEGER)';
		$statement = $db->prepare($sql);
		$statement->execute();
	}

	public function getDB() {
		$db = new PDO('mysql:host=localhost;dbname=payiota', MYSQL_USERNAME, MYSQL_PASSWORD);
		return $db;
	}
	
	public function getNewAddress($seed, $count) {
		chdir(ROOT);
		$output = trim(shell_exec("python scripts/iota_address_generator.py ".$seed." ".$count));

		if ($output == "") {
			$this->logEvent("ERR_FATAL_3RD_PARTY", "Fatal lightnode failure while generating new address: Empty output");
			return "ERR_FATAL_3RD_PARTY";
		}

		return $output;
	}

	public function getNewSeed() {
		chdir(ROOT);
		$output = trim(shell_exec("python scripts/iota_seed_generator.py"));
		return $output;
	}

	public function provideNewAddress($id, $seed, $count) {
		//1. Get from pregenerated table if it is in there
		//2. Generate now, add to DB
		$db = $this->getDB();
		$sql = "SELECT address FROM pregeneration WHERE realID = :realID and position = :position";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(':realID', $id);
		$stmt->bindParam(':position', $count);
		$stmt->execute();

		$address = key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));

		if (!is_null($address)) {
			return $address;
		} else {
			$address = $this->getNewAddress($seed, $count);


			if ($address == "ERR_FATAL_3RD_PARTY") {
				return "ERR_FATAL_3RD_PARTY";
			}

			$sql = "INSERT IGNORE INTO pregeneration (realID, address, position) VALUES (:realID, :address, :position)";
			$stmt = $db->prepare($sql);
			$stmt->bindParam(':realID', $id);
			$stmt->bindParam(':address', $address);
			$stmt->bindParam(':position', $count);
			$stmt->execute();

			return $address;
		}


	}

	public function generateAddresses() {
		$db = $this->getDB();
		$sql = "SELECT id, seed FROM users";
		$stmt = $db->prepare($sql);
		$stmt->execute();

		$data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
		
		foreach ($data as $key => $value) {
			$count = 0;

			for ($i = 1; $i <= 10; $i++) { 
				$id = $key;
				$seed = $value[0]["seed"];

				//continue where left off
				if ($count == 0) {
					$sql = "SELECT position FROM pregeneration WHERE realID = :realID";
					$stmt = $db->prepare($sql);
					$stmt->bindParam(':realID', $id);
					$stmt->execute();
					$data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

					if (empty($data)) {
						$count = 0;
					} else {
						$count = key( array_slice( $data, -1, 1, TRUE ) );
						$count = $count + 1;
					}

					//check whether generation is needed (10 address difference needed, then it generates 10 addresses)
					$count_user = $this->countInvoicesByID($id);
					$difference = ($count - $count_user);

					if ($difference > 10 or $difference == 10) {
						$stop = true;
					} else {
						$this->logEvent("ERR_OK", "Will pregenerate 10 new addresses for user ".$id." because difference is ".$difference."!");
						$stop = false;
					}

				}

				if ($stop == true) {
					continue;
				}


			
				$address = $this->getNewAddress($seed, $count);

				//insert address into DB
				$sql = "INSERT IGNORE INTO pregeneration (realID, address, position) VALUES (:realID, :address, :position)";
				$stmt = $db->prepare($sql);
				$stmt->bindParam(':realID', $id);
				$stmt->bindParam(':address', $address);
				$stmt->bindParam(':position', $count);
				$stmt->execute();

				$count++;
				
	
			}
	
		}
	}

	public function getAddressBalance($address) {
		$url = $this->getWorkingNode();

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"command\": \"getBalances\", \"addresses\": [\"".$address."\"], \"threshold\": 100}");
		curl_setopt($ch, CURLOPT_POST, 1);

		$headers = array();
		$headers[] = "Content-Type: application/json";
		$headers[] = "X-IOTA-API-Version: ANY";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$this->logEvent("ERR_FATAL_3RD_PARTY", "Fatal lightnode failure while getting balance: ".$result);
		    return "ERR_FATAL_3RD_PARTY";
		}

		curl_close ($ch);

		$balance = @json_decode($result, true)["balances"];
		$balance = $balance[0];
		
		$balance = (int)$balance;
		
		return $balance;
	}

	public function hashPassword($password) {
		return sha1(SALT.$password);
	}

	public function generateAPIKey() {
		return bin2hex(openssl_random_pseudo_bytes(32));
	}

	public function getNewVerificationString() {
		return bin2hex(openssl_random_pseudo_bytes(64));
	}

	public function checkIfAlreadyExists($email) {
		$db = $this->getDB();
		$sql = "SELECT email FROM users WHERE email = :email";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":email", $email);
		$stmt->execute();

		$email = key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));

		if (!empty($email)) {
			return "ERR_EXISTS";
		} else {
			return "ERR_OK";
		}
	}

	public function matchCodeToMessage($code) {
		$array = array("ERR_EXISTS" => "Sorry, email already exists in database!", "ERR_LOGIN_INCORRECT" => "Sorry, email or password incorrect!", "ERR_INVALID_INFO" => "Sorry, email or password could not be validated!", "ERR_URL_NOT_VALID" => "Sorry, the URL you entered is invalid!", "ERR_CAPTCHA" => "Sorry, captcha entered is incorrect!","ERR_KEY_WRONG" => "Sorry, confirm key is incorrect!","ERR_IPN_OK" => "Successfully updated IPN url!", "ERR_CONFIRM_OK" => "Successfully confirmed account!", "ERR_REGISTER_OK" => "Successfully created account! Please confirm it via your email address!", "ERR_UNCONFIRMED" => "Account not confirmed! Please confirm it first!");
		return $array[$code];
	}

	public function matchCodeToType($code) {
		$array = array("ERR_EXISTS" => "error", "ERR_LOGIN_INCORRECT" => "error", "ERR_INVALID_INFO" => "error", "ERR_CAPTCHA" => "error", "ERR_URL_NOT_VALID" => "error", "ERR_KEY_WRONG" => "error","ERR_IPN_OK" => "success", "ERR_CONFIRM_OK" => "success", "ERR_REGISTER_OK" => "success", "ERR_UNCONFIRMED" => "warning");
		return $array[$code];
	}

	public function sendEmail($to, $subject, $body) {

		try {
					//sends email to user about signup
					$title = "WebIOTA_Email";
				    $transport = Swift_SmtpTransport::newInstance(gethostbyname("mail.gandi.net"), 465, "ssl") 
						->setUsername(EMAIL_USERNAME)
						->setPassword(EMAIL_PASSWORD)
						->setSourceIp("0.0.0.0");
					$mailer = Swift_Mailer::newInstance($transport);
					$logger = new \Swift_Plugins_Loggers_ArrayLogger();
					$mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($logger));
					$message = Swift_Message::newInstance("$title");
					$message 
						->setSubject($subject)
						->setFrom(array("bot@lacicloud.net" => "WebIOTA"))
						->setTo(array("$to"))
						->setCharset('utf-8') 
						->setBody($body, 'text/html');
					$mailer->send($message, $errors);
					$result = "ERR_OK";
		} catch(\Swift_TransportException $e){
			        $response = $e->getMessage();
			        $result = "ERR_EMAIL_ERROR";
		} catch (Exception $e) {
			    	$response = $e->getMessage();
			    	$result = "ERR_EMAIL_ERROR";
		}

		return $result;

	}

	public function loginUser($email, $password) {
		$email =  trim($email);

		//verify data
		if ($this->validateInfo($email, $password) !== "ERR_OK") {
			return "ERR_INVALID_INFO";
		}

		$db = $this->getDB();
		$sql = "SELECT email, password, id FROM users WHERE email = :email";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":email", $email);
		$stmt->execute();

		$data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
		$email_db = key($data);
		$password_db = @$data[$email][0]["password"];
		$id = @$data[$email][0]["id"];

		$password = $this->hashPassword($password);

		if ($password_db == $password and $email_db == $email) {
			if ($this->checkConfirmed($id) == "ERR_UNCONFIRMED") {
				return "ERR_UNCONFIRMED";
			} else {
				return $id;

			}
		} else {
			return "ERR_LOGIN_INCORRECT";
		}
	}

	public function regenerateSession() {
		session_regenerate_id(true);
	}


	public function validateInfo($email, $password) {

		if (empty($email) or empty($password)) {
			return "ERR_EMPTY_VALUES";
		}

		if (strlen($password) < 8 or !preg_match("#[0-9]+#", $password) or !preg_match("#[a-zA-Z]+#", $password)) {
			return "ERR_PASS_WEAK";
		} 

		if (preg_match('/\s/',$email) or strlen($email) < 5 or strlen($email) > 320 or !strpos($email, "@") or !strpos($email, ".")) {
			return "ERR_EMAIL_INVALID";
		}

		return "ERR_OK";

	}

	public function createAccount($email, $password) {
		$email =  trim($email);
		
		//verify data
		if ($this->validateInfo($email, $password) !== "ERR_OK") {
			return "ERR_INVALID_INFO";
		}

		//verify whether email already exists in DB
		if ($this->checkIfAlreadyExists($email) !== "ERR_OK") {
			return "ERR_EXISTS";
		}

		$password = $this->hashPassword($password);
		$api_key = $this->generateAPIKey();
		$seed = $this->getNewSeed();
		$verification = $this->getNewVerificationString();
		$verification_email = $this->getNewVerificationEmailString();

		$this->sendEmail($email, "WebIOTA - Confirm Account",  "<html><body><p>Hi there!</p><p>To confirm your account, please click <a href='https://payiota.me/confirm.php?key=".$verification_email."'>here</a>.</p><p>Thanks!</p></body></html>");

		//empty by default
		$ipn_url = '';
		$addresses = '';
		$count = 0;

		$db = $this->getDB();
		$sql = "INSERT INTO users (seed, email, password, api_key, ipn_url, verification, confirmed, count) VALUES (:seed, :email, :password, :api_key, :ipn_url, :verification, :confirmed, :count)";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(':seed', $seed);
		$stmt->bindParam(':email', $email);
		$stmt->bindParam(':password', $password);
		$stmt->bindParam(':api_key', $api_key);
		$stmt->bindParam(':ipn_url', $ipn_url);
		$stmt->bindParam(':verification', $verification);
		$stmt->bindParam(':confirmed', $verification_email);
		$stmt->bindParam(':count', $count);
		$stmt->execute();

		return "ERR_REGISTER_OK";
	}

	public function checkConfirmed($id) {
		$db = $this->getDB();
		$sql = "SELECT confirmed FROM users WHERE id = :id";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":id", $id);
		$stmt->execute();

		$confirmed = key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));
		if ($confirmed == 1) {
			return "ERR_OK";
		} else {
			return "ERR_UNCONFIRMED";
		}

	}

	public function confirmAccount($key) {
		$db = $this->getDB();
	$sql = "SELECT confirmed FROM users WHERE confirmed = :confirmed";
	$stmt = $db->prepare($sql);
	$stmt->bindParam(':confirmed', $key);
	$stmt->execute();

	$key = key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));
	if (empty($key)) {
		return "ERR_KEY_WRONG";
	} else {
		$sql = "UPDATE users SET confirmed = 1 WHERE confirmed = :confirmed";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(':confirmed', $key);
		$stmt->execute();

		return "ERR_CONFIRM_OK";
	}
}

	public function getNewVerificationEmailString() {
		return bin2hex(openssl_random_pseudo_bytes(64));
	}

	public function getAccountValues($id) {
		$db = $this->getDB();
		$sql = "SELECT * FROM users WHERE id = :id";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":id", $id);
		$stmt->execute();

		//flatten user array
		return call_user_func_array('array_merge', array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));
	}

	public function getPaymentAccountValues($id) {
		$db = $this->getDB();
			$sql = "SELECT * FROM payments WHERE realID = :id";
			$stmt = $db->prepare($sql);
			$stmt->bindParam(":id", $id);
			$stmt->execute();

			return $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
		}

	public function matchAPItoID($api_key) {
		$db = $this->getDB();
		$sql = "SELECT id FROM users WHERE api_key = :api_key";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":api_key", $api_key);
		$stmt->execute();

		return key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));
	}

	public function convertCurrency($amount, $from, $to) {
			
			if ($from == "IOTA") {
					return $this->getUSDPrice($amount);
			}

			$url  = "https://finance.google.com/finance/converter?a=$amount&from=$from&to=$to";
			$data = file_get_contents($url);
			preg_match("/<span class=bld>(.*)<\/span>/",$data, $converted);
			$converted = preg_replace("/[^0-9.]/", "", $converted[1]);
			return round($converted, 3);
	}

	public function getUSDPrice($iota) {

		$convert_miota = $iota / 1000000;
		$curl = curl_init();
		curl_setopt_array($curl, array(
		    CURLOPT_RETURNTRANSFER => 1,
		    CURLOPT_URL => 'https://api.coinmarketcap.com/v1/ticker/iota/',
		));
		$data = curl_exec($curl);
		curl_close($curl);
		$data = json_decode($data, true);
		$price_usd = (double)$data[0]["price_usd"];
		$price_in_usd = $price_usd * $convert_miota;

		return $price_in_usd;

	}

	public function getIOTAPrice($price) {
		//to get it in number of million IOTAS, divide price by miota price, then round MIOTA
		//to get it in IOTA, multiply MIOTA by million
		$data = file_get_contents("https://api.coinmarketcap.com/v1/ticker/iota/");

		if (!$data) {
			$this->logEvent("ERR_FATAL_3RD_PARTY", "Fatal CMP error: ".$data);
			return "ERR_FATAL_3RD_PARTY";
		}

		$data = json_decode($data, true);

		$price_usd = (double)$data[0]["price_usd"];
		$miota = (double)$price / (double)$price_usd;
		$iota = $miota * 1000000;
		$iota = (int)round($iota);

		if ($iota < 1) {
			$iota = 1;
		}

		return $iota; 
	}

	//for devs storing payment invoices, compensate for price changes 
	public function updatePriceForAddress($address, $verification, $realID) {	
		$data = $this->getPaymentAccountValues($realID);

		foreach ($data as $key => $value) {
			foreach ($value as $key_1 => $value_1) {
				if ($value_1["address"] == $address) {
					$price = $value_1["price"];
					$verification_database = $value_1["verification"];
					$done = $value_1["done"];
					break;
				}
			}
		}

		if (!isset($price) or !isset($verification) or !isset($done)) {
			return "ERR_NOT_FOUND";
		}

		if ($verification_database !== $verification or (int)$done == 1) {
			return "ERR_DISALLOWED";
		}

		$price_iota = $this->getIOTAPrice($price);
		if ($price_iota == "ERR_FATAL_3RD_PARTY") {
			return "ERR_FATAL_3RD_PARTY";
		}

		//now update price
		$db = $this->getDB();	
		$sql = "UPDATE payments SET price_iota = :price_iota WHERE address = :address";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":price_iota", $price_iota);
		$stmt->bindParam(":address", $address);
		$stmt->execute();

		$this->logEvent("ERR_OK", "Updated price for address ".$address." to ".$price_iota." for US dollar ".$price);
		return $price_iota;

	}

	public function addPaymentToServer($realID, $price, $custom, $ipn_url) {
		$data = $this->getAccountValues($realID);
		$seed = $data["seed"];
		$verification = $data["verification"];

		if ($ipn_url == "" or filter_var($ipn_url, FILTER_VALIDATE_URL) == false) {
			//use default IPN url
			$ipn_url = $data["ipn_url"];
		} 

		//no default IPN url was set and no custom IPN url was specified
		if ($ipn_url == "") {
			echo "ERR_IPN_URL_INVALID";
			die(0);
		}

		//make sure nodes are online
		$this->getWorkingNode();


		$count = $this->countInvoicesByID($realID);
		
		//old system
		//$address = $this->getNewAddress($seed, $count + 1);
		//$address = $this->getNewAddress($seed, $count);
		
		$address = $this->provideNewAddress($realID, $seed, $count);

		if ($address == "ERR_FATAL_3RD_PARTY") {
			return "ERR_FATAL_3RD_PARTY";
		}

		$price_iota = $this->getIOTAPrice($price);
		if ($price_iota == "ERR_FATAL_3RD_PARTY") {
			return "ERR_FATAL_3RD_PARTY";
		}

		$this->incrementInvoiceCount($realID, $count); 

		$done = 0;
		$created = time();

		$db = $this->getDB();
		$sql = "INSERT INTO payments (realID, address, price, price_iota, custom, ipn_url, verification, done, created) VALUES (:id, :address, :price, :price_iota, :custom, :ipn_url, :verification, :done, :created)";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":id", $realID);
		$stmt->bindParam(":address", $address);
		$stmt->bindParam(":price", $price);
		$stmt->bindParam(":price_iota", $price_iota);
		$stmt->bindParam(":custom", $custom);
		$stmt->bindParam(":ipn_url", $ipn_url);
		$stmt->bindParam(":verification", $verification);
		$stmt->bindParam(":done", $done);
		$stmt->bindParam(":created", $created);
		$stmt->execute();

		$this->logEvent("ERR_OK", "Generated new payment with ID ".$realID." with address ".$address);
		return json_encode(array($address, $price_iota));
	}

	public function getAddressesTotal($id) {
		$db = $this->getDB();
		$sql = "SELECT address FROM payments WHERE realID = :id";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":id", $id);
		$stmt->execute();
		
		$data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

		$balance = 0;
		foreach ($data as $key => $value) {
			$balance = $balance + $this->getAddressBalance($key);

		}
		return $balance;

	}

	public function updateDonePayment($address) {
		$db = $this->getDB();
		$sql = "UPDATE payments SET done = 1 WHERE address = :address";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":address", $address);
		$stmt->execute();
	}

	public function getAddressesToMonitor() {
		$db = $this->getDB();
		$sql = "SELECT * FROM payments WHERE done = 0";
		$stmt = $db->prepare($sql);
		$stmt->execute();

		$data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

		foreach ($data as $key => $value) {
			$this->checkAddress($value[0]);
		}

	}

	public function logEvent($event, $event_message) {
		chdir(ROOT);
		$message = date('l jS \of F Y h:i:s A')." : "." Event: ".$event." Message: ".$event_message."\n";
		error_log($message, 3, "logs/payiota.log");

		//in case of a serious error, stop script
		if ($event == "ERR_FATAL_3RD_PARTY") {
			echo "Sorry, a fatal error has occured, service is unavailable!";
			die(1);
		}
	}

	public function checkAddress($data) {
		$address = $data["address"];
		$price_iota = $data["price_iota"];

		//check time (invoices can last a maximum of 1 week)
		$created = (int)$data["created"];
		$current = time();

		$difference = $current - $created;

		if ($difference > 630427) {
			return;
		}

		$balance = $this->getAddressBalance($address);

		if ($balance > $price_iota or $balance == $price_iota) {
			$this->updateDonePayment($address);
			$this->sendIPN($data, $balance);
			$this->logEvent("ERR_OK", "Sent IPN and updated done to 1 for address ".$address);
		}
	}		

	public function countInvoicesByID($id) {
		$db = $this->getDB();
		$sql = "SELECT count FROM users WHERE id = :id";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":id", $id);
		$stmt->execute();

		return key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));
	}

	public function incrementInvoiceCount($id, $count) {
		$db = $this->getDB();
		$sql = "UPDATE users SET count = :count WHERE id = :id";
		$stmt = $db->prepare($sql);

		$count = $count + 1;

		$stmt->bindParam(":count", $count);
		$stmt->bindParam(":id", $id);
		$stmt->execute();

		return "ERR_OK";

	}

	public function updateIPN($id, $ipn_url) {

		if (filter_var($ipn_url, FILTER_VALIDATE_URL) == false) {
			return "ERR_URL_NOT_VALID";
		}

		$db = $this->getDB();
		$sql = "UPDATE users SET ipn_url = :ipn_url WHERE id = :id";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":ipn_url", $ipn_url);
		$stmt->bindParam(":id", $id);
		$stmt->execute();

		return "ERR_IPN_OK";
	}


	public function sendIPN($data, $balance) {
		$data["paid_iota"] = $balance;
		$data["done"] = 1;

		$post_data = http_build_query($data);

		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $data["ipn_url"]);
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_REFERER, 'https://payiota.me');
		curl_setopt($ch, CURLOPT_USERAGENT, 'PayIOTA IPN'); 
		$result = curl_exec($ch);
		
		curl_close($ch);

		return "ERR_OK";
	}
}
?>