<?php
/**
 *  PHP-PayPal-IPN Example
 *
 *  This shows a basic example of how to use the IpnListener() PHP class to 
 *  implement a PayPal Instant Payment Notification (IPN) listener script.
 *
 *  For a more in depth tutorial, see my blog post:
 *  http://www.micahcarrick.com/paypal-ipn-with-php.html
 *
 *  This code is available at github:
 *  https://github.com/Quixotix/PHP-PayPal-IPN
 *
 *  @package    PHP-PayPal-IPN
 *  @author     Micah Carrick
 *  @copyright  (c) 2011 - Micah Carrick
 *  @license    http://opensource.org/licenses/gpl-3.0.html
 */
 
 
/*
Since this script is executed on the back end between the PayPal server and this
script, you will want to log errors to a file or email. Do not try to use echo
or print--it will not work! 

Here I am turning on PHP error logging to a file called "ipn_errors.log". Make
sure your web server has permissions to write to that file. In a production 
environment it is better to have that log file outside of the web root.
*/
ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__).'/ipn_errors.log');


// instantiate the IpnListener class
include('ipnlistener.php');
//include_once('../inc/functions.php');
$listener = new IpnListener();


/*
When you are testing your IPN script you should be using a PayPal "Sandbox"
account: https://developer.paypal.com
When you are ready to go live change use_sandbox to false.
*/
$listener->use_sandbox = false;

/*
By default the IpnListener object is going  going to post the data back to PayPal
using cURL over a secure SSL connection. This is the recommended way to post
the data back, however, some people may have connections problems using this
method. 

To post over standard HTTP connection, use:
$listener->use_ssl = false;

To post using the fsockopen() function rather than cURL, use:
$listener->use_curl = false;
*/

/*
The processIpn() method will encode the POST variables sent by PayPal and then
POST them back to the PayPal server. An exception will be thrown if there is 
a fatal error (cannot connect, your server is not configured properly, etc.).
Use a try/catch block to catch these fatal errors and log to the ipn_errors.log
file we setup at the top of this file.

The processIpn() method will send the raw data on 'php://input' to PayPal. You
can optionally pass the data to processIpn() yourself:
$verified = $listener->processIpn($my_post_data);
*/


//echo  '<div class="debug" style="position: absolute; height: 50px; width: 100px; bottom: 10px; border: 1px solid black;">ipn</div>';

try {
    $listener->requirePostMethod();
    $verified = $listener->processIpn();
} catch (Exception $e) {
    error_log($e->getMessage());
    exit(0);
}


$invoiceid = $_POST['invoice'];
$pemail = $_POST['payer_email'];
$json = $listener->getJSONReport();

/*
The processIpn() method returned true if the IPN was "VERIFIED" and false if it
was "INVALID".
*/
if ($verified) {
    
    //If $json mc_gross < 0 then valid == 4 (refund) otherwise it == 1 (purchase)
	$state = 1;    

    try {
		$ipndata = json_decode ($row->vcrJSON, true);
		$ipngross = $ipndata['mc_gross'];
 	
		if ($ipngross < 0)
		{
				$state = 4; //refund through paypal and remove spot taken
		}
	} catch (Exception $e) {
		mail('gadimus@gmail.com', 'Payment Data Missing',
		 'mc_gross and things missing from this: ' . $invoiceid . ' ' . $pemail .
		 ' <br />' . $json);
	}
    
    HandleIPN($invoiceid, $state, $json, $pemail);
    
    //mail('gadimus@gmail.com', 'Payment', $json);

} else {

    HandleIPN($invoiceid, 2, $json);
    
    mail('gadimus@gmail.com', 'Invalid Payment', "You're receiving this email because someone" .
    "tried to send a payment through the system that was ultimately flagged as invalid.<br /><br/>" .
    "The following is a data dump you should send to your technical contact.<br /><br />" .
    $json);
}

?>
