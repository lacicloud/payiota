<?php 
require_once 'vendor/autoload.php';

use IOTA\Client;
use IOTA\Node;
use IOTA\DI\IOTAContainer;
use IOTA\RemoteApi\RemoteApi;
use IOTA\ClientApi\ClientApi;

define('ROOT', dirname(__FILE__));
define('SALT', "");
define("EMAIL_USERNAME", "");
define("EMAIL_PASSWORD", "");
define("MYSQL_USERNAME", "");
define("MYSQL_PASSWORD", "");
define("PAYIOTA_IPN_URL", "");
define("PAYIOTA_API_KEY", "");
define("PAYIOTA_VERIFICATION_KEY", "");
define("PAYPAL_API_USER", "");
define("PAYPAL_API_PASSWORD", "");
define("PAYPAL_API_SIGNATURE", "");
define("COINLIB_API_KEY", "");
define("PAYIOTA_ID", 574);
define("LACICLOUD_ID", 6);
define("PAYIOTA_SUBSCRIPTION_PRICE", 12); //USD
define("MAX_EXPIRATION", 630427);
define("MIN_EXPIRATION", 60);

ini_set('default_socket_timeout', 7);
ignore_user_abort(true);


set_exception_handler(function ($e) {
	chdir(ROOT);
	error_log("Unhandled exception occured, error: ".$e." POST: ".print_r($POST, true)." GET: ".print_r($_GET, true), 3, "logs/payiota.log");
	echo "Sorry, a fatal error has occured, service is unavailable!";
	die(1);
});



class IOTAPaymentGateway {

	public function getWorkingNode() {
			$uri = $this->getWorkingNodeMain();

			if ($uri == "ERR_FATAL_3RD_PARTY") {
				$uri = $this->getWorkingNodeFallbackOne();
				if ($uri == "ERR_FATAL_3RD_PARTY") {
					$uri = $this->getWorkingNodeFallbackTwo();
					if ($uri == "ERR_FATAL_3RD_PARTY") {
						$uri = $this->getWorkingNodeFallbackThree();
						if ($uri == "ERR_FATAL_3RD_PARTY") {
							$this->logEvent("ERR_FATAL_3RD_PARTY", "No nodes working, all fallbacks tried!");
							return "ERR_FATAL_3RD_PARTY";
						}
					}
				}
			}

			return $uri;
		}

		//we do not actually need to validate the URL, as it comes from already processed services. We just need to check whether the services were offline or not (json_decode returns a NULL array if the data was not JSON-encoded)
		public function validateURI($uri) {
			if (empty($uri) or !isset($uri) or $uri == false or is_null($uri)) {
				return false;
			} else {
				return true;
			}
		}


		public function decodeJSON($data) {
			return json_decode($data, true);
		}

		public function curlGET($url) {
			$curl = curl_init();
			
			curl_setopt_array($curl, array(
			    CURLOPT_RETURNTRANSFER => 1,
			    CURLOPT_URL => $url,
			));

			$data = curl_exec($curl);
			curl_close($curl);

			return $data;
		}

		//sort by health and load
		public function sortBestNodes($data) {
			$data = $this->arrayOrderBy($data, "health", SORT_DESC, "load", SORT_ASC);
			return $data;

		}

		public function arrayOrderBy() {
			$args = func_get_args();
			$data = array_shift($args);
			foreach ($args as $n => $field) {
			    if (is_string($field)) {
			        $tmp = array();
			        foreach ($data as $key => $row)
			            @$tmp[$key] = $row[$field];
			        $args[$n] = $tmp;
			        }
			}
			$args[] = &$data;
			call_user_func_array('array_multisort', $args);
			return array_pop($args);
		}

		//iota.dance/api
		public function getWorkingNodeMain() {
			$data = $this->curlGET("https://iota.dance/api");

			if (!$data) {
				$this->logEvent("ERR_FATAL_3RD_PARTY", "Main node is not working, network exception: ".$data);
				return "ERR_FATAL_3RD_PARTY";
			}

			$data = $this->decodeJSON($data);
			$data = $this->sortBestNodes($data);
			$uri = $data[0]["node"];

			if (!$this->validateURI($uri)) {
				$this->logEvent("ERR_FATAL_3RD_PARTY", "Main node is not working, data exception: ".$uri);
				return "ERR_FATAL_3RD_PARTY";
			}


			return $uri;
		}

		
		//https://nodes.iota.works/api/live/ssl
		public function getWorkingNodeFallbackOne() {
			$data = $this->curlGET("https://nodes.iota.works/api/live/ssl");

			if (!$data) {
				$this->logEvent("ERR_FATAL_3RD_PARTY", "Fallback one node is not working, network exception: ".$data);
				return "ERR_FATAL_3RD_PARTY";
			}


			$data = $this->decodeJSON($data);
			$data = $this->sortBestNodes($data);
			$uri = $data[0]["node"];

			if (!$this->validateURI($uri)) {
				$this->logEvent("ERR_FATAL_3RD_PARTY", "Fallback one node is not working, data exception: ".$uri);
				return "ERR_FATAL_3RD_PARTY";
			}

			return $uri;
		}


		//https://api.iota-nodes.net/
		public function getWorkingNodeFallbackTwo() {
			$data = $this->curlGET("https://api.iota-nodes.net/");

			if (!$data) {
				$this->logEvent("ERR_FATAL_3RD_PARTY", "Fallback two node is not working, network exception: ".$data);
				return "ERR_FATAL_3RD_PARTY";
			}

			$data = $this->decodeJSON($data);
			$data = $this->sortBestNodes($data);
			$uri = $data[0]["hostname"].":".$data[0]["port"];

			if (!$this->validateURI($uri)) {
				$this->logEvent("ERR_FATAL_3RD_PARTY", "Fallback two node is not working, data exception: ".$uri);
				return "ERR_FATAL_3RD_PARTY";
			}

			return $uri;
		}

		//attempt to get a few hardcoded nodes
		public function getWorkingNodeFallbackThree() {
			$nodes = array(
				"https://pool.iota.dance:443",
				"https://potato.iotasalad.org:14265",
				"https://durian.iotasalad.org:14265",
				"https://peanut.iotasalad.org:14265",
				"https://tuna.iotasalad.org:14265",
				"https://turnip.iotasalad.org:14265",
				"http://iotanode.party:14265/",
				"http://node.lukaseder.de:14265",
				"http://node01.iotatoken.nl:14265",
				"http://node02.iotatoken.nl:14265",
				"http://node03.iotatoken.nl:15265",
				"http://node04.iotatoken.nl:14265",
				"http://node05.iotatoken.nl:16265",
				"http://cryptoiota.win:14265",
				"http://iota.bitfinex.com", //port 80
				"http://service.iotasupport.com:14265",
				"http://eugene.iota.community:14265",
				"http://eugene.iotasupport.com:14999",
				"http://eugeneoldisoft.iotasupport.com:14265"
				);

			foreach ($nodes as $key => $value) {
					if ($this->isNodeOnline($value)) {
						$working = $value;
						break;
					}
				}

				if (!isset($working)) {
					$this->logEvent("ERR_FATAL_3RD_PARTY", "Fallback three node is not working, all hardcoded nodes tried!");
					return "ERR_FATAL_3RD_PARTY";
				} else {
					return $working;
				}

		}

		public function isNodeOnline($uri) {

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $uri);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"command\": \"getBalances\", \"addresses\": [\"invalid\"], \"threshold\": 100}");
				curl_setopt($ch, CURLOPT_POST, 1);
				$headers = array();
				$headers[] = "Content-Type: application/json";
				$headers[] = "X-IOTA-API-Version: 1";
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

				$data = curl_exec($ch);
				curl_close($ch);

			    //error
			    if (!$data) {
			    	return false;
			    }

			    if ($data !== '{"error":"Invalid addresses input","duration":0}') {
			    	return false;
			    }

			    //else OK
			    return true;
		}

	public function setupDB() {
		$db =  $this->getDB();

		$sql = "CREATE TABLE users (id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT, seed TEXT, email TEXT, password TEXT, api_key TEXT, verification TEXT, confirmed TEXT, count INTEGER)";
		$statement = $db->prepare($sql);
		$statement->execute();

		$sql ='CREATE TABLE payments (id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT, address TEXT, realID INTEGER, price INTEGER, price_iota INTEGER, currency TEXT, expiration INTEGER, custom TEXT, ipn_url TEXT, verification TEXT, done INTEGER, created INTEGER)';
		$statement = $db->prepare($sql);
		$statement->execute();

		$sql ='CREATE TABLE pregeneration (id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT, realID INTEGER, address TEXT, position INTEGER)';
		$statement = $db->prepare($sql);
		$statement->execute();

		$sql = 'CREATE TABLE subscriptions (id INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT, date TEXT, address TEXT, amount INTEGER, done INTEGER, realID INTEGER, created INTEGER)';
		$statement = $db->prepare($sql);
		$statement->execute();

	}

	public function getDB() {
		$db = new PDO('mysql:host=localhost;dbname=payiota', MYSQL_USERNAME, MYSQL_PASSWORD);
		return $db;
	}
	
	public function getNewAddress($seed, $count) {
		$uri = $this->getWorkingNode();

		$options = [
		    'ccurlPath' => '/srv/ccurl'
		];

		// initializes a new IOTA instance with the built in container and one iota node
		$container = new IOTAContainer($options);

		$iota = new Client(
		    $container->get(RemoteApi::class),
		    $container->get(ClientApi::class),
		    [new Node($uri)]
		);

		
		$seed = new \IOTA\Type\Seed($seed);
		$security =   new IOTA\Type\SecurityLevel(2);
		
		$result = $iota->getClientApi()->GetNewAddress(new Node($uri), $seed, $count, true, $security);
		$address = $result->serialize()["address"]["trytes"];
		$checksum = $result->serialize()["address"]["checkSum"];
		
		$address = $address.$checksum;

		if (strlen($address) !== 90) {
			$this->logEvent("ERR_FATAL_3RD_PARTY", "Fatal lightnode failure of ".$uri." while generating new address: ".$address);
			return "ERR_FATAL_3RD_PARTY";
		} 


		return $address;



	}

	public function getNewSeed() {
		//all credits to https://github.com/plabbett/php-iota-seeder, not using library for compactness
		$seed = '';
		
		$allowed_characters = [
		    'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I',
		    'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
		    'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '9',
		];
		
		for ($i = 0; $i < 81; $i++) {
		    // Cryptographically secure. (7.1 + built in)
		    // http://php.net/manual/en/function.random-int.php
		    $seed .= $allowed_characters[random_int(0, count($allowed_characters) - 1)];
		}

		return $seed;
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

			for ($i = 1; $i <= 50; $i++) { 
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

					//check whether generation is needed (10 address difference needed, then it generates 50 addresses)
					$count_user = $this->countInvoicesByID($id);
					$difference = ($count - $count_user);

					if ($difference > 10 or $difference == 10) {
						$stop = true;
					} else {
						$this->logEvent("ERR_OK", "Will pregenerate 50 new addresses for user ".$id." because difference is ".$difference."!");
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
		$uri = $this->getWorkingNode();

		//cut out checksum from address
		$address = substr($address, 0, -9);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"command\": \"getBalances\", \"addresses\": [\"".$address."\"], \"threshold\": 100}");
		curl_setopt($ch, CURLOPT_POST, 1);

		$headers = array();
		$headers[] = "Content-Type: application/json";
		$headers[] = "X-IOTA-API-Version: 1";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$this->logEvent("ERR_FATAL_3RD_PARTY", "Fatal lightnode failure while getting balance: ".curl_error($ch));
		    return "ERR_FATAL_3RD_PARTY";
		}

		curl_close ($ch);
		
		$balance = @json_decode($result, true)["balances"];
		$balance = $balance[0];
		
		$balance = (int)$balance;
		
		return $balance;
	}

	public function getNumberOfUsers() {
		$db = $this->getDB();
		$sql = "SELECT count(*) FROM users";
		$stmt = $db->prepare($sql);
		$stmt->execute();

		return key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));
	}

	public function getPaymentStatistics() {
		
		$db = $this->getDB();

		$sql = "SELECT count(*) FROM payments";
		$stmt = $db->prepare($sql);
		$stmt->execute();

		$total = key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));


		$sql = "SELECT count(*) FROM payments WHERE done = 1";
		$stmt = $db->prepare($sql);
		$stmt->execute();

		$paid = key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));


		$sql = "SELECT SUM(price_iota) FROM payments WHERE done = 1";
		$stmt = $db->prepare($sql);
		$stmt->execute();

		$iotas = key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));

		$sql = "SELECT SUM(price) FROM payments WHERE done = 1";
		$stmt = $db->prepare($sql);
		$stmt->execute();

		$dollars = key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));

		return $total.":".$paid.":".$iotas.":".$dollars;
	}

	public function getAddressStatus($address) {
		$uri = $this->getWorkingNode();

		//cut out checksum from address
		$address = substr($address, 0, -9);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"command\": \"findTransactions\", \"addresses\": [\"".$address."\"]}");
		curl_setopt($ch, CURLOPT_POST, 1);

		$headers = array();
		$headers[] = "Content-Type: application/json";
		$headers[] = "X-IOTA-API-Version: 1";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		curl_close ($ch);
		
		$status = @json_decode($result, true)["hashes"];
		$status = @$status[0];

		if (empty($status)) {
			$status = "NOTX";
		} else {
			$status = "TX";
		}
			
		return $status;
		
	}

	public function hashPassword($password) {
		return sha1(SALT.$password);
	}

	public function generateAPIKey() {
		return bin2hex(openssl_random_pseudo_bytes(32));
	}

	//128 chars as bin2hex makes it twice as big
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
		$array = array("ERR_EXISTS" => "Sorry, email already exists in database!", "ERR_LOGIN_INCORRECT" => "Sorry, email or password incorrect!", "ERR_INVALID_INFO" => "Sorry, email or password could not be validated!", "ERR_URL_NOT_VALID" => "Sorry, the URL you entered is invalid!", "ERR_CAPTCHA" => "Sorry, captcha entered is incorrect!","ERR_KEY_WRONG" => "Sorry, confirm key is incorrect!", "ERR_CONFIRM_OK" => "Successfully confirmed account!", "ERR_REGISTER_OK" => "Successfully created account! Please confirm it via your email address!", "ERR_UNCONFIRMED" => "Account not confirmed! Please confirm it first!", "ERR_INVOICE_EXPIRED" => "Error, invoice is expired!",  "ERR_RESET_STEP_1_OK" => "Email sent, please check your email account!", "ERR_RESET_STEP_2_OK" => "Account\"s password reset!", "ERR_TOS_UNCHECKED" => "Please check the TOS box before proceeding!", "ERR_OK" => "Action Successfully completed!");
		return $array[$code];
	}

	public function matchCodeToType($code) {
		$array = array("ERR_EXISTS" => "error", "ERR_LOGIN_INCORRECT" => "error", "ERR_INVALID_INFO" => "error", "ERR_CAPTCHA" => "error", "ERR_URL_NOT_VALID" => "error", "ERR_KEY_WRONG" => "error", "ERR_CONFIRM_OK" => "success", "ERR_INVOICE_EXPIRED" => "error", "ERR_TOS_UNCHECKED" => "error", "ERR_REGISTER_OK" => "success", "ERR_UNCONFIRMED" => "warning", "ERR_RESET_STEP_1_OK" => "success", "ERR_RESET_STEP_2_OK" => "success", "ERR_OK" => "success");
		return $array[$code];
	}

	public function sendEmail($to, $subject, $body) {

		try {
					//sends email to user about signup/payment
					$title = "PayIOTA_Email";
				    $transport = (new Swift_SmtpTransport("mail.gandi.net", 465, "ssl")) 
						->setUsername(EMAIL_USERNAME)
						->setPassword(EMAIL_PASSWORD)
						->setSourceIp("0.0.0.0");
					$mailer = new Swift_Mailer($transport);
					$logger = new \Swift_Plugins_Loggers_ArrayLogger();
					$mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($logger));
					$message = new Swift_Message("$title");
					$message 
						->setSubject($subject)
						->setFrom(array("bot@lacicloud.net" => "PayIOTA.me"))
						->setTo(array("$to"))
						->setCharset('utf-8') 
						->setBody($body, 'text/html');
					if ($subject == "PayIOTA.me - Thank You For Paying") {
						$message->setBcc(array("laci@lacicloud.net" => "Laci"));
					}
					$mailer->send($message, $errors);
					$result = "ERR_OK";
		} catch(\Swift_TransportException $e){
			        $response = $e->getMessage();
			        $result = "ERR_EMAIL_ERROR";
			        $this->logEvent("ERR_EMAIL_ERROR", "Error while sending email to ".$to." with subject ".$subject." and body ".$body." : ".$response);
		} catch (Exception $e) {
			    	$response = $e->getMessage();
			    	$result = "ERR_EMAIL_ERROR";
			    	$this->logEvent("ERR_EMAIL_ERROR", "Exception while sending email to ".$to." with subject ".$subject." and body ".$body." : ".$response);
		}

		return $result;

	}


	public function loginUser($email, $password) {
		$email =  trim($email);

		//verify data
		if ($this->validateInfo($email, $password, $password) !== "ERR_OK") {
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


	public function validateInfo($email, $password, $password_retyped) {

		if (empty($email) or empty($password)) {
			return "ERR_EMPTY_VALUES";
		}

		if ($password !== $password_retyped) {
			return "ERR_PASSWORDS_DO_NOT_MATCH";
		}

		if (strlen($password) < 8 or !preg_match("#[0-9]+#", $password) or !preg_match("#[a-zA-Z]+#", $password)) {
			return "ERR_PASS_WEAK";
		} 

		if (preg_match('/\s/',$email) or strlen($email) < 5 or strlen($email) > 320 or !strpos($email, "@") or !strpos($email, ".")) {
			return "ERR_EMAIL_INVALID";
		}

		return "ERR_OK";

	}

	public function createAccount($email, $password, $password_retyped) {
		$api_payments = new Payments;

		$email =  trim($email);
		
		//verify data
		if ($this->validateInfo($email, $password, $password_retyped) !== "ERR_OK") {
			return "ERR_INVALID_INFO";
		}

		//verify whether email already exists in DB
		if ($this->checkIfAlreadyExists($email) !== "ERR_OK") {
			return "ERR_EXISTS";
		}

		//check terms & conditions checkbox

		if (@count($_POST["checkbox"]) == 0) {
			return "ERR_TOS_UNCHECKED";
		}


		$password = $this->hashPassword($password);
		$api_key = $this->generateAPIKey();
		$seed = $this->getNewSeed();
		$verification = $this->getNewVerificationString();
		$verification_email = $this->getNewVerificationEmailString();

		$this->sendEmail($email, "PayIOTA.me - Confirm Account",  "<html><body><p>Hi there!</p><p>To confirm your account, please click <a href='https://payiota.me/confirm.php?key=".$verification_email."'>here</a>.</p><p>Best Regards,<br>PayIOTA.me</p></body></html>");

		//empty by default
		$addresses = '';
		$reset_key = '1';
		$count = 0;

		$db = $this->getDB();
		$sql = "INSERT INTO users (seed, email, password, api_key, verification, confirmed, count, reset_key) VALUES (:seed, :email, :password, :api_key, :verification, :confirmed, :count, :reset_key)";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(':seed', $seed);
		$stmt->bindParam(':email', $email);
		$stmt->bindParam(':password', $password);
		$stmt->bindParam(':api_key', $api_key);
		$stmt->bindParam(':verification', $verification);
		$stmt->bindParam(':confirmed', $verification_email);
		$stmt->bindParam(':count', $count);
		$stmt->bindParam(':reset_key', $reset_key);
		$stmt->execute();

		$api_payments->generateInvoiceForUser($this->matchEmailtoID($email));

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
	//128 chars as bin2hex makes it twice as big
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
		$api_payments = new Payments;

		$db = $this->getDB();
		$sql = "SELECT id FROM users WHERE api_key = :api_key";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":api_key", $api_key);
		$stmt->execute();

		$id = key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));

		if (is_int($id) and $api_payments->isAPIKeyDisabled($id) == true) {
			return "ERR_API_KEY_DISABLED";
		} else {
			return $id;
		}
	}

	public function matchEmailtoID($email) {
		$db = $this->getDB();
		$sql = "SELECT id FROM users WHERE email = :email";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":email", $email);
		$stmt->execute();

		$id = key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));
		
		return $id;
	}

	public $valid_currencies = ["USD", "EUR", "IOTA", "MIOTA", "CAD", "AUD", "CHF", "CNY", "DKK", "HUF", "GBP", "HKD", "ILS", "INR", "ISK", "NZD", "KRW", "SGD", "ZAR", "PHP"];

	public function validateCurrency($currency) {
		if (!in_array($currency, $this->valid_currencies)) {
		    return false;
		} else {
			return true;
		}

	}

	public function convertCurrencyToIOTA($price, $currency) {
		//first test to see if currency is on the list of approved currencies (compatible with all three fallbacks)
		if (!$this->validateCurrency($currency)) {
			return "ERR_CURRENCY_INVALID";
		}

		//to get it in number of million IOTAS, divide price by miota price, then round MIOTA
		//to get it in IOTA, multiply MIOTA by million
		$rate = $this->getIOTATicker($currency);

		if ($rate == "ERR_FATAL_3RD_PARTY" or !is_numeric($rate)) {
			return "ERR_FATAL_3RD_PARTY";
		}

		$miota = (double)$price / (double)$rate;
		$iota = $miota * 1000000;
		$iota = (int)round($iota);

		if ($iota < 1) {
			return 1;
		}

		return $iota; 
	}

	public function getIOTATicker($currency) {

		//special case
		if ($currency == "IOTA") {
			return 1000000;
		} elseif ($currency == "MIOTA") {
			return 1;
		}

		$rate = $this->getIOTATickerMain($currency);

		if ($rate == "ERR_FATAL_3RD_PARTY") {
			$rate = $this->getIOTATickerFallbackOne($currency);
			if ($rate == "ERR_FATAL_3RD_PARTY") {
				$rate = $this->getIOTATickerFallbackTwo($currency);
				if ($rate == "ERR_FATAL_3RD_PARTY") {
					$this->logEvent("ERR_FATAL_3RD_PARTY", "Error, all IOTA ticker fallbacks failed.");
					return "ERR_FATAL_3RD_PARTY";
				}
			}
		}

		return $rate;

	}	

	public function getIOTATickerFallbackTwo($currency) {
		$data = $this->curlGET("https://api.coincap.io/v2/rates");
		if (!$data) {
			return "ERR_FATAL_3RD_PARTY";
		}
		$data = $this->decodeJSON($data);

		//get and store exchange rate of currency to to USD
		foreach ($data as $key => $value) {
		    if (is_array($value)) {
		        foreach ($value as $key_1 => $value_1) {
		        if ($value_1["symbol"] == $currency) {
		           $rate_conversion = $value_1["rateUsd"];
		           break;
		        }
		    }
		    }   
		}

		if (!is_numeric($rate_conversion)) {
			return "ERR_FATAL_3RD_PARTY";
		}

		$data = $this->curlGET("https://api.coincap.io/v2/assets");
		if (!$data) {
			return "ERR_FATAL_3RD_PARTY";
		}
		$data = $this->decodeJSON($data);
		
		foreach ($data as $key => $value) {
		    if (is_array($value)) {
		        foreach ($value as $key_1 => $value_1) {
		        if ($value_1["symbol"] == "MIOTA") {
		            $rate = $value_1["priceUsd"];
		            break;
		        }
		    }
		    }
		}

		if (!is_numeric($rate)) {
			return "ERR_FATAL_3RD_PARTY";
		}

		$rate = (double)$rate / (double)$rate_conversion;

		return (float)$rate;


	}

	//CoinGecko API
	public function getIOTATickerFallbackOne($currency) {
		$data = $this->curlGET("https://api.coingecko.com/api/v3/simple/price?ids=iota&vs_currencies=".$currency);
		
		if (!$data) {
			return "ERR_FATAL_3RD_PARTY";
		}

		$data = $this->decodeJSON($data);

		$rate = $data["iota"][strtolower($currency)];
		
		if (!is_numeric($rate)) {
			return "ERR_FATAL_3RD_PARTY";
		}

		return (float)$rate;
	}

	//Coinlib.io API with permission to use
	public function getIOTATickerMain($currency) {
		$data = $this->curlGET("https://coinlib.io/api/v1/coin?key=".COINLIB_API_KEY."&pref=".$currency."&symbol=IOT");
		

		if (!$data) {
			return "ERR_FATAL_3RD_PARTY";
		}

		$data = $this->decodeJSON($data);
		$rate = $data["price"];
		
		if (!is_numeric($rate)) {
			return "ERR_FATAL_3RD_PARTY";
		}

		return (float)$rate;
	}

	public function forgotLoginStep1($email) {
		$id = $this->matchEmailtoID($email);

		if (!isset($id)) {
			return "ERR_EMAIL_INVALID";
		}

		$reset_key = $this->getNewResetVerificationString();

		$db = $this->getDB();	
		$sql = "UPDATE users SET reset_key = :reset_key WHERE id = :id";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":reset_key", $reset_key);
		$stmt->bindParam(":id", $id);
		$stmt->execute();

		$this->sendEmail($email, "PayIOTA.me - Reset Account",  "<html><body><p>Hi there!</p><p>To reset your account, please click <a href='https://payiota.me/account.php?forgot_2&reset_key=".$reset_key."'>here</a> or go to <a href='https://payiota.me/account.php?forgot_2'>https://payiota.me/account.php?forgot_2</a> and enter your reset key: ".$reset_key.".</p><p>Best Regards,<br>PayIOTA.me</p></body></html>");

		return "ERR_RESET_STEP_1_OK";

	}

	public function matchResetKeyToEmail($reset_key) {
		$db = $this->getDB();
		$sql = "SELECT email FROM users WHERE reset_key = :reset_key";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":reset_key", $reset_key);
		$stmt->execute();

		$email = key(array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));
		
		return $email;
	}

	//64 chars
	public function getNewResetVerificationString() {	
		return bin2hex(openssl_random_pseudo_bytes(32));
	}

	public function forgotLoginStep2($reset_key, $password, $password_retyped) {
		$email = $this->matchResetKeyToEmail($reset_key);

		if (!isset($email) or is_null($email) or $reset_key == '1' or $reset_key == 1) {
			return "ERR_RESET_KEY_INVALID";
		}

		//verify data
		if ($this->validateInfo($email, $password, $password_retyped) !== "ERR_OK") {
			return "ERR_INVALID_INFO";
		}

		$password = $this->hashPassword($password);

		$db = $this->getDB();	
		$sql = "UPDATE users SET password = :password WHERE email = :email";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":password", $password);
		$stmt->bindParam(":email", $email);
		$stmt->execute();

		$db = $this->getDB();	
		$sql = "UPDATE users SET reset_key = '1' WHERE email = :email";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":email", $email);
		$stmt->execute();

		$this->sendEmail($email, "PayIOTA.me - Account Reset",  "<html><body><p>Hi there!</p><p>Your account's password was reset. The IP of the person who reset your account (this is not stored or recordedin any way): ".$_SERVER["REMOTE_ADDR"]."; if this was not you, please contact support at support@payiota.me as soon as possible!</p><p>Best Regards,<br>PayIOTA.me</p></body></html>");

		return "ERR_RESET_STEP_2_OK";


	}

	public function deleteAccount($id) {
		$db = $this->getDB();	

		$sql = "DELETE FROM users WHERE id = :id";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":id", $id);
		$stmt->execute();


		$sql = "DELETE FROM payments WHERE realID = :id";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":id", $id);
		$stmt->execute();

		$sql = "DELETE FROM pregeneration WHERE realID = :id";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":id", $id);
		$stmt->execute();

		$sql = "DELETE FROM subscriptions WHERE realID = :id";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":id", $id);
		$stmt->execute();

		return "ERR_OK";

	}

	public function regenerateAPIKey($id) {
		$api_key = $this->generateAPIKey();

		$db = $this->getDB();	
		$sql = "UPDATE users SET api_key = :api_key WHERE id = :id";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":api_key", $api_key);
		$stmt->bindParam(":id", $id);
		$stmt->execute();

		return "ERR_OK";

	}

	//for devs storing payment invoices, compensate for price changes 
	public function updateAddress($address, $expiration, $verification, $realID) {	
		$data = $this->getPaymentAccountValues($realID);

		foreach ($data as $key => $value) {
			foreach ($value as $key_1 => $value_1) {
				if ($value_1["address"] == $address) {
					$price = $value_1["price"];
					$verification_database = $value_1["verification"];
					$done = $value_1["done"];
					$currency = $value_1["currency"];
					break;
				}
			}
		}

		if (!isset($price) or !isset($verification_database) or !isset($done)) {
			return "ERR_NOT_FOUND";
		}

		if ($verification_database !== $verification or (int)$done == 1) {
			return "ERR_DISALLOWED";
		}

		if ((int)$expiration > MAX_EXPIRATION or (int)$expiration < MIN_EXPIRATION or preg_match('#[^0-9]#',$expiration)) {
			return "ERR_EXPIRATION_INVALID";
		}

		$price_iota = $this->convertCurrencyToIOTA($price, $currency);
		if ($price_iota == "ERR_FATAL_3RD_PARTY") {
			return "ERR_FATAL_3RD_PARTY";
		}

		$created = time();

		//now update price
		$db = $this->getDB();	
		$sql = "UPDATE payments SET price_iota = :price_iota, created = :created, expiration = :expiration WHERE address = :address";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":price_iota", $price_iota);
		$stmt->bindParam(":created", $created);
		$stmt->bindParam(":address", $address);
		$stmt->bindParam(":expiration", $expiration);
		$stmt->execute();

		$this->logEvent("ERR_OK", "Updated price for address ".$address." to ".$price_iota.", created to ".$created." and expiration to ".$expiration." for US dollar ".$price);
		return $price_iota;

	}

	//API: return invoice data
	public function returnJSONApi($code, $result) {

		$return_array = array(
			"content" => array(
				$result
				),
			"code" => $code,
			"message" =>  $this->matchCodeToMessage($code),
			"boolean" => $this->matchCodeToType($code)
			);

		return json_encode($return_array);

	}

	//API: return invoice data
	public function getInvoice($address, $trusted) {
		$db = $this->getDB();

		$sql = "SELECT * FROM payments WHERE address = :address";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":address", $address);
		$stmt->execute();

		
		$invoice =  @call_user_func_array('array_merge', array_map('reset', $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC)));
	
		if (empty($invoice)) {
			return "ERR_NOT_FOUND";
		} else {
			if ($trusted == false) {
				unset($invoice["verification"]);
				unset($invoice["ipn_url"]);
				unset($invoice["custom"]);
				unset($invoice["realID"]);
			}
			
			return $invoice;
		}


	}

	public function addPaymentToServer($realID, $price, $currency, $expiration, $custom, $ipn_url) {
		//get user data
		$data = $this->getAccountValues($realID);
		$seed = $data["seed"];
		$verification = $data["verification"];

		if (!is_numeric($price)) {
			return "ERR_PRICE_INVALID";
		}

		if (empty($custom)) {
			return "ERR_CUSTOM_INVALID";
		}


		$price_iota = $this->convertCurrencyToIOTA($price, $currency);

		if ($price_iota == "ERR_FATAL_3RD_PARTY" or $price_iota == "ERR_CURRENCY_INVALID") {
			return $price_iota;
		}


		if (filter_var($ipn_url, FILTER_VALIDATE_URL) == false) {
			return "ERR_IPN_URL_INVALID";
		} 

		if ((int)$expiration > MAX_EXPIRATION or (int)$expiration < MIN_EXPIRATION or preg_match('#[^0-9]#',$expiration)) {
			return "ERR_EXPIRATION_INVALID";
		}
		$expiration = (int)$expiration;

		$count = $this->countInvoicesByID($realID);
		
		$address = $this->provideNewAddress($realID, $seed, $count);
		if ($address == "ERR_FATAL_3RD_PARTY") {
			return $address;
		}

		$done = 0;
		$created = time();

		$db = $this->getDB();
		$sql = "INSERT INTO payments (realID, address, price, price_iota, custom, ipn_url, verification, done, created, expiration, currency) VALUES (:id, :address, :price, :price_iota, :custom, :ipn_url, :verification, :done, :created, :expiration, :currency)";
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
		$stmt->bindParam(":expiration", $expiration);
		$stmt->bindParam(":currency", $currency);
		$stmt->execute();

		$this->incrementInvoiceCount($realID, $count); 

		$this->logEvent("ERR_OK", "Generated new payment with ID ".$realID." with address ".$address." for price USD ".$price.", for price IOTA ".$price_iota." and IPN URL ".$ipn_url);
		return json_encode(array($address, $price_iota));
	}

	public function getAddressBalances($id) {
		$db = $this->getDB();
		$sql = "SELECT address FROM payments WHERE realID = :id AND done = 1";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":id", $id);
		$stmt->execute();
		
		$data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

		$total_balance = 0;
		$detailed_balance = array();

		foreach ($data as $key => $value) {
			$balance = $this->getAddressBalance($key);
			$total_balance = $total_balance + $balance;
			$detailed_balance[$key] = $balance;

		}
		return array($total_balance, $detailed_balance);

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
		$expiration = $data["expiration"];

		//check time (invoices can last a maximum of 1 week, unless renewed by updatePriceForAddress)
		$created = (int)$data["created"];
		$current = time();

		$difference = $current - $created;

		if ($difference > (int)$expiration) {
			return "ERR_INVOICE_EXPIRED";
		}
	
		$balance = $this->getAddressBalance($address);

		if ($balance > $price_iota or $balance == $price_iota) {
			$this->updateDonePayment($address);
			$this->sendIPN($data, $balance);
			$this->logEvent("ERR_OK", "Sent IPN and updated done to 1 for address ".$address);
		}

		return ($this->getInvoice($address, false));
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

	public function sendIPN($data, $balance) {
		$data["paid_iota"] = $balance;
		$data["done"] = 1;

		$post_data = http_build_query($data);

		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $data["ipn_url"]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_REFERER, 'https://payiota.me');
		curl_setopt($ch, CURLOPT_USERAGENT, 'PayIOTA.me IPN'); 
		$result = curl_exec($ch);
		
		curl_close($ch);

		return "ERR_OK";
	}
}

class Payments extends IOTAPaymentGateway {

	public function displayPaymentStatusToUser($id) {
		$api = new IOTAPaymentGateway;

		$db = $api->getDB();
		$sql = "SELECT realID, date, address, amount, done, created FROM subscriptions WHERE realID = :realID";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":realID", $id);
		$stmt->execute();

		$data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

		if (!isset($data[$id])) {
			print "<p style='color: green'>Everything is paid!</p>";
			return;
		}

		$data = $data[$id];
		
		$count_of_unpaid_invoices = 0;
		$count_of_due_invoices = 0;

		foreach ($data as $key => $value) {
			if ($value["done"] == 0 and ((int)$value["created"] - time()) > 2629743 ) {
				$count_of_unpaid_invoices++;
			} else if ($value["done"] == 0 and ((int)$value["created"] - time()) < 2629743 ) {
				$count_of_due_invoices++;
			}
		}

		if ($count_of_unpaid_invoices > 0) {
			print "<p style='color: red'>API is locked out due to missed invoice(s)!</p>";
		} else if ($count_of_due_invoices > 0) {
			print "<p style='color: orange'>Please pay for your invoice!</p>";
		} else {
			print "<p style='color: green'>Everything is paid!</p>";
		}
		
		//fix anachronism
		$data = array_reverse($data);

		foreach ($data as $key => $value) {
			
			print("Invoice for: ".$value["date"]);
			print("<br>");
			print("Address is: ".$value["address"]);
			print("<br>");
			print("Amount for: ".$value["amount"]." in IOTA and ".PAYIOTA_SUBSCRIPTION_PRICE." in USD");
			print("<br>");
			print("Due by: ". date("F j, Y, g:i a", (int)$value["created"] + 2629743));
			print("<br>");
			print("Paid: ".$value["done"]);
			if ($value["done"] == 0) {
				print("<br><br>");
				print("<a href='https://payiota.me/external.php?address=".$value["address"]."&success_url=https://payiota.me/subscription.php?result=success&cancel_url=https://payiota.me/subscription.php?result=cancel'>Pay Now using PayIOTA.me</a>");
				print("<br><br>");
				if (isset($_GET["paypal"]) and $_GET["paypal"] == "true" and $_SESSION["paypalclear"] == false and isset($_GET["address"]) and $_GET["address"] == $value["address"]) {
					$this->getPayPalAddress(PAYIOTA_SUBSCRIPTION_PRICE, $id, $value["address"]);
					print("
						<script>
						var forms = document.getElementsByTagName('form');
						for (var i=0; i<forms.length; i++) 
						forms[i].submit();
						</script>

						");
					$_SESSION["paypalclear"] = true;
				} else {
					$_SESSION["paypalclear"] = false;
					print("<a href='https://payiota.me/subscription.php?paypal=true&address=".$value["address"]."'>Pay Now using PayPal</a>");
				}
				
			} else {
				print("<br><br>");
				print("<a href='https://iotasear.ch/hash/".$value["address"]."'>View Payment (if paid using PayIOTA.me)</a>");
			}

			if (isset($_GET["result"]) and $_GET["result"] == "cancel") {
				print("
					<script>
					alert('Payment not accepted, please try again!');
					</script>");
			} else if (isset($_GET["result"]) and $_GET["result"] == "success") {
				print("
					<script>
					alert('Payment successfully accepted, your subscription will be updated soon!');
					</script>");
			}

			print("<br><br>");

		}
	}

	public function getPayPalAddress($price, $id, $address) {

		$sendPayData = array(
		    "METHOD" => "BMCreateButton",
		    "VERSION" => "95.0",
		    "USER" => PAYPAL_API_USER,
		    "PWD" => PAYPAL_API_PASSWORD,
		    "SIGNATURE" => PAYPAL_API_SIGNATURE,
		    "BUTTONCODE" => "ENCRYPTED",
		    "BUTTONTYPE" => "BUYNOW",
		    "BUTTONSUBTYPE" => "SERVICES",
		    "L_BUTTONVAR1" => "item_number=".$price.$id,
		    "L_BUTTONVAR2" => "item_name=PayIOTA.me Yearly Subscription for ".date('Y'),
		    "L_BUTTONVAR3" => "amount=".$price,
		    "L_BUTTONVAR4" => "currency_code=USD",
		    "L_BUTTONVAR5" => "no_shipping=1",
		    "L_BUTTONVAR6" => "no_note=1",
		    "L_BUTTONVAR7" => "notify_url=https://payiota.me/payment_system/paypal.php",
		    "L_BUTTONVAR8" => "cancel_return=https://payiota.me/subscription.php?type=paypal&result=cancel",
		    "L_BUTTONVAR9" => "return=https://payiota.me/subscription.php?type=paypal&result=success",
		    "L_BUTTONVAR10" => "subtotal=".$price,
		    "L_BUTTONVAR11" => "custom=".$id.":".$address.":".date('Y').":".PAYIOTA_VERIFICATION_KEY
		);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		
		$paydata = http_build_query($sendPayData);

		curl_setopt($curl,CURLOPT_POST, 1);
		curl_setopt($curl,CURLOPT_POSTFIELDS, $paydata);

		curl_setopt($curl, CURLOPT_URL, 'https://api-3t.paypal.com/nvp?');
		$nvpPayReturn = curl_exec($curl);

		parse_str($nvpPayReturn, $return);
		print($return["WEBSITECODE"]);
	}

	public function isAPIKeyDisabled($id) {
		$api = new IOTAPaymentGateway;

		$db = $api->getDB();
		$sql = "SELECT realID, done, created FROM subscriptions WHERE realID = :realID";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":realID", $id);
		$stmt->execute();

		$data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

		if (!isset($data[$id])) {
			return false;
		}

		$data = $data[$id];
		
		$count_of_unpaid_invoices = 0;

		//only counts as unpaid if difference is bigger than one month between creation and now
		foreach ($data as $key => $value) {
			if ($value["done"] == 0 and ((int)$value["created"] - time()) > 2629743) {
				$count_of_unpaid_invoices++;
			}
		}

		if ($count_of_unpaid_invoices > 0) {
			return true;
		} else {
			return false;
		}
	}

	//when to call? Every year, by cron.
	public function generateInvoicesForUsers() {
		$api = new IOTAPaymentGateway;

		$db = $api->getDB();

		$sql = "SELECT id FROM users";
		$stmt = $db->prepare($sql);
		$stmt->execute();

		$data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

		foreach ($data as $key => $value) {
			$id = $key;
			if ($id !== PAYIOTA_ID and $id !== LACICLOUD_ID) {
				$this->generateInvoiceForUser($id);
			}
			
		}
	}

	//called every week by cron.
	public function updateInvoicesForUsers() {
			$api = new IOTAPaymentGateway;

			$db = $api->getDB();

			$sql = "SELECT id FROM users";
			$stmt = $db->prepare($sql);
			$stmt->execute();

			$data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

			foreach ($data as $key => $value) {
				$id = $key;
				if ($id !== PAYIOTA_ID and $id !== LACICLOUD_ID) {
					$this->updateInvoiceForUser($id);
				}
				
			}
	}

	public function updateInvoiceForUser($id) {
			
			$api = new IOTAPaymentGateway;

			$db = $api->getDB();
			$sql = "SELECT realID, address FROM subscriptions WHERE realID = :realID AND done = 0";
			$stmt = $db->prepare($sql);
			$stmt->bindParam(":realID", $id);
			$stmt->execute();

			$data = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

			//nothing to update
			if (!isset($data[$id])) {
				return;
			}

			$data = $data[$id];
			foreach ($data as $key => $value) {
				$address = $value["address"];

				$request = array(
					"api_key" => PAYIOTA_API_KEY,
					"address" => $address,
					"verification" => PAYIOTA_VERIFICATION_KEY,
					"action" => "update"
					);

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				
				$request = http_build_query($request);
				curl_setopt($curl,CURLOPT_POST, 1);
				curl_setopt($curl,CURLOPT_POSTFIELDS, $request);

				curl_setopt($curl, CURLOPT_URL, 'https://payiota.me/api.php');
			
				//new price as an integer
				$response = curl_exec($curl);

				if (is_numeric($response)) {
					//update it in DB, does not touch created as that is used internally for due checking	

					$sql = "UPDATE subscriptions SET amount = :amount WHERE address = :address";
					$stmt = $db->prepare($sql);
					$stmt->bindParam(":amount", $response);
					$stmt->bindParam(":address", $address);
					$stmt->execute();


				}




			}



	}

	public function updateInvoiceToPaid($address) {
		$api = new IOTAPaymentGateway;

		$db = $api->getDB();

		$sql = "UPDATE subscriptions SET done = 1 WHERE address = :address";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":address", $address);
		$stmt->execute();
	}

	public function generateInvoiceForUser($id) {
		$api = new IOTAPaymentGateway;

		//check if ID is valid
		if (is_null($id) or !is_numeric($id)) {
			return "ERR_ID_INVALID";
		}

		//check if it already exists for this year
		$db = $api->getDB();

		$sql = "SELECT date FROM subscriptions WHERE realID = :realID";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":realID", $id);
		$stmt->execute();


		//check already existing invoices
		$date = key($stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC));
		
		if ($date == date('Y')) {
				return "ERR_ALREADY_EXISTS";
		}

		$date = date('Y');
		$custom = $id.":".$date;

		$request = array(
			"api_key" => PAYIOTA_API_KEY,
			"price" => PAYIOTA_SUBSCRIPTION_PRICE,
			"currency" => "USD",
			"custom" => $custom,
			"ipn_url" => PAYIOTA_IPN_URL,
			"action" => "new"
			);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		$request = http_build_query($request);
		curl_setopt($curl,CURLOPT_POST, 1);
		curl_setopt($curl,CURLOPT_POSTFIELDS, $request);

		curl_setopt($curl, CURLOPT_URL, 'https://payiota.me/api.php');
		$response = curl_exec($curl);

		$response = json_decode($response, true);
		$address = $response[0];
		$price = $response[1];
		$realID  = $id;
		$created = time();
		$done = 0;

		$sql = "INSERT INTO subscriptions (date, address, amount, done, realID, created) VALUES (:date, :address, :amount, :done, :realID, :created)";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":date", $date);
		$stmt->bindParam(":address", $address);
		$stmt->bindParam(":amount", $price);
		$stmt->bindParam(":done", $done);
		$stmt->bindParam(":realID", $realID);
		$stmt->bindParam(":created", $created);
		$stmt->execute();

	}


}

?>