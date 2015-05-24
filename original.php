<?php
/**
 * 
 * 
 * This script is the original version. It was my script I have ever created, so I apologize for the sloppyness.
 * 
 * 
 * 
 * 
 * 
 */ 
require_once 'db.php';
require_once 'api/Twilio.php';
require_once 'api/Email/Mandrill.php';
require_once 'email.php';

$formData = array();
$fields   = array(
    'name',
    'email',
    'phone_number',
    'zip_code',
    'device',
    'damage',
    'model'
);

foreach ($fields as $field) {
    $formData[$field] = mysql_real_escape_string($_POST[$field]);
}

file_put_contents('data.txt', serialize($formData));

$mandrill = new Mandrill('');

$AccountSid = "";
$AuthToken  = "";

$client = new Services_Twilio($AccountSid, $AuthToken);


function get_client_ip()
{
    if ($_SERVER['HTTP_CLIENT_IP'])
        return $_SERVER['HTTP_CLIENT_IP'];
    else if ($_SERVER['HTTP_X_FORWARDED_FOR'])
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if ($_SERVER['HTTP_X_FORWARDED'])
        return $_SERVER['HTTP_X_FORWARDED'];
    else if ($_SERVER['HTTP_FORWARDED_FOR'])
        return $_SERVER['HTTP_FORWARDED_FOR'];
    else if ($_SERVER['HTTP_FORWARDED'])
        return $_SERVER['HTTP_FORWARDED'];
    else if ($_SERVER['REMOTE_ADDR'])
        return $_SERVER['REMOTE_ADDR'];
    else
        return 'UNKNOWN';
}

$name   = $formData['name'];
$email  = $formData['email'];
$phone  = $formData['phone_number'];
$zip    = $formData['zip_code'];
$device = $formData['device'];
$brand  = $formData['brand']; // Doesnt exist
$damage = $formData['damage'];
$model  = $formData['model'];
$ip     = get_client_ip();
$date   = date('Y-m-d');

$status = "success";

/****/
$url           = "http://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($zip) . "&sensor=false";
$result_string = file_get_contents($url);
$abc[]         = json_decode($result_string, true);
foreach ($abc as $abc1) {
    
    $latitude  = $abc1['results'][0]['geometry']['location']['lat'];
    $longitude = $abc1['results'][0]['geometry']['location']['lng'];
}
/****/

$qin = mysql_query("INSERT INTO `TABLE 1` (date_submitted, time_submitted, ip_address, name, email, phone_number, zip_code, device, brand, damage, model) VALUES ('$date', '', '$ip', '$name', '$email', '$phone', '$zip', '$device', '$brand', '$damage', '$model');");
if (!$qin) {
    file_put_contents('mysql_error.txt', mysql_error());
} else {
    // file_put_contents('mysql.txt', $qin);
}

//$q = mysql_query("SELECT * FROM `TABLE 2` WHERE zip = '$zip' OR assigned_zip_codes LIKE '%$zip%' LIMIT 1;");
$q = mysql_query("SELECT  `email`,`first_name`,`zip` , ( 3959 * ACOS( COS( RADIANS( $latitude ) ) * COS( RADIANS(  `latitude` ) ) * COS( RADIANS(  `longitude` ) - RADIANS( $longitude ) ) + SIN( RADIANS( $latitude ) ) * SIN( RADIANS(  `latitude` ) ) ) ) AS distance
FROM  `TABLE 2` 
ORDER BY  `distance` 
LIMIT 0 , 30");

if ($q) {
    if (mysql_num_rows($q) > 0) {
        $r           = mysql_fetch_array($q);
        $fn          = $r['first_name'];
        $ln          = $r['last_name'];
        $adrs        = $r['address_1'];
        $city        = $r['city'];
        $state       = $r['state'];
        $phn         = $r['phone_number'];
        $asn         = $r['assigned_zip_codes'];
        $storzip     = $r['zip'];
        $storemail   = $r['email'];
        $customerid  = $r['storenum'];
        $price       = $r['price'];
        $twilnumber  = $r['twilionumber'];
        $storesmsnum = $r['storesmsnum'];
        
        
        mysql_query("UPDATE `TABLE 1` SET `assigned_store`='$fn' WHERE email = '$email'");
        
        
        $totalZipCount           = mysql_fetch_row(mysql_query("SELECT count(zip_code) FROM `TABLE 1` where zip_code = '$zip'"));
        $auto_bil_generted_times = $r['auto_bil_generted_times'];
        
        if ($totalZipCount[0] != 0 && $totalZipCount[0] % 5 == 0) {
            $counts     = floor($totalZipCount[0] / 5);
            //for live server use 'www' for test server use 'sandbox'
            $wsdl       = 'https://www.usaepay.com/soap/gate/0AE595C1/usaepay.wsdl';
            // instantiate SoapClient object as $client
            $usaeClient = new SoapClient($wsdl);
            $sourcekey  = 'ry1th5n5QP4EV93en4GhSGvl5LW1amQm';
            $pin        = '1921';
            // generate random seed value
            $seed       = time() . rand();
            
            // make hash value using sha1 function
            $clear = $sourcekey . $seed . $pin;
            $hash  = sha1($clear);
            
            // assembly ueSecurityToken as an array
            $token = array(
                'SourceKey' => $sourcekey,
                'PinHash' => array(
                    'Type' => 'sha1',
                    'Seed' => $seed,
                    'HashValue' => $hash
                ),
                'ClientIP' => $_SERVER['REMOTE_ADDR']
            );
            try {
                $Parameters = array(
                    'Command' => 'Sale',
                    'Details' => array(
                        'Invoice' => rand(),
                        'PONum' => '',
                        'OrderID' => '',
                        'Description' => 'Sample Credit Card Sale',
                        'Amount' => $price
                    )
                );
                // Please enter here the custom ID
                $CustNum    = "$customerid";
                $PayMethod  = '0';
                
                $res = $usaeClient->runCustomerTransaction($token, $CustNum, $PayMethod, $Parameters);
                mysql_query("UPDATE `TABLE 2` SET `auto_bil_generted_times`='$counts' WHERE email = '$email'");
                //print_r($res);
                $html_leadcontent = '<table style="border-width: 2px; border-spacing: 2px; border-style: solid; border-color: #131416;  border-collapse: collapse; background-color: #fff;">
					<tr><th colspan="2" style="padding: 5px;text-align:left;background:#131416;color:#fff;"><h4 style="margin:0">You have reached 5 leads, we have billed you for them. Please save this information.</h4></th></tr>';
                
                $leadSql = mysql_query("SELECT 'name' FROM `TABLE 1` WHERE zip_code = $asn ORDER BY date_submitted DESC limit 0,5");
                while ($r = mysql_fetch_array($leadSql)) {
                    $html_leadcontent .= '<tr>
						<th style="border-width: 1px;padding: 5px 10px 5px 5px; border-style: solid; border-color: #999999; background-color: #DDE0E1;" width="120" valign="top" align="right"><strong>Lead ' . $sno . ':</strong></th>
						<td style="border-width: 1px;padding: 5px; border-style: solid; border-color: #999999; background-color: #f9f9f9;" width="400" valign="top">' . $r['name'] . '</td>
					</tr>';
                }
                
                $html_leadcontent .= '</table>';
                $names = $fn . ' ' . $ln;
                sendEmaillead($email, $names, $html_leadcontent, $mandrill);
            }
            catch (SoapFault $e) {
                $status = "Customer Transaction Error";
                echo $usaeClient->__getLastRequest();
                echo $usaeClient->__getLastResponse();
                die("runCustomerTransaction failed :" . $e->getMessage());
                
            }
        }
        
        $message = $client->account->messages->sendMessage("$twilnumber", "$phone", "$name,\n\rYour local SpeedFixIt partner is: $fn $ln. Please save the contact info.\n\r$fn $ln $adrs $phn");
        
        $message = $client->account->messages->sendMessage("$twilnumber", "$storesmsnum", "$fn,\n\rYou have a local repair request: $name $email. $phone $brand $device $model $damage");
        
        sendEmail($email, $name, html_found($fn, $phn, $adrs, $city, $state), $mandrill);
        
        
        sendEmailtech($storemail, $fn, html_tech($email, $name, $phone, $device, $brand, $model, $damage), $mandrill);
        
        $msg = "You have a new potential customer, name is $name, Device is $device $model, and Damage is $damage, Press 1 to connect to customer.";
        $xml = '<Response><Gather timeout="60" finishOnKey="1"><Say loop="2">' . $msg . '</Say></Gather><Dial timeout="30" record="true" action="http://sms.speedfixit.com/webhook/rec.php">' . $phone . '</Dial><Say>We are sorry. There was a problem.</Say></Response>';
        
        
        $url = "http://twimlets.com/echo?Twiml=" . urlencode($xml);
        
        try {
            $call = $client->account->calls->create("$twilnumber", $phn, $url);
        }
        catch (Exception $e) {
            // $status = "Unable to call service provider.";
            file_put_contents('call.txt', 'Error: ' . $e->getMessage());
        }
        
    } else {
        $status  = "no tech";
        $message = $client->account->messages->sendMessage("855-800-2259", "$phone", "Hello $name,\n\rWe do not currently have a tech in your area. We will very soon. Sorry for the inconvenience.\n\rThanks,\n\rSpeedFixIt.com");
        
        sendEmail($email, $name, $html_not, $mandrill);
    }
} else {
    $status = "Database Error";
}

echo $status;
?>