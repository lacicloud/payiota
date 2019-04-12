<?php 
require("../../functions.php");
$api = new IOTAPaymentGateway;
$api_payments = new Payments;

use overint\PaypalIPN;

$ipn = new PaypalIPN();

$verified = $ipn->verifyIPN();
if ($verified) {
    /*
     * Process IPN
     * A list of variables is available here:
     * https://developer.paypal.com/webapps/developer/docs/classic/ipn/integration-guide/IPNandPDTVariables/
     */

    $custom = explode(":", $_POST["custom"]);
    $id = $custom[0];
    $address = $custom[1];
    $year = $custom[2];
    $verification = $custom[3];

    if ($verification !== PAYIOTA_VERIFICATION_KEY) {
        echo "Verification error.";
        die(1);
    }

    $mc_gross = $_POST["mc_gross"];
    $mc_currency = $_POST["mc_currency"];

    $paymentID = $_POST["txn_id"];

    $api_payments->updateInvoiceToPaid($address);

    $email = $api->getAccountValues($id)["email"];
    $api->sendEmail($email, "PayIOTA.me - Thank You For Paying",  "<html><body><p>Hi there!</p><p>Your invoice for user ID ".$id.", for year ".$year." has been paid for (price ".PAYIOTA_SUBSCRIPTION_PRICE." USD) with ".$mc_gross." ".$mc_currency." and PayPal payment ID ".$paymentID."! Your subscription is valid until next year.</p><p>Best Regards,<br>PayIOTA.me</p></body></html>");
    
}


?>
