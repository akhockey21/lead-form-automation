<?php

/**
 * Load Config Options
 */ 
$options = $settings->getSettings();

/**
 * if Settings true, then load setting
 */ 
    //verify that the key is in config
if($options['mandrillAPI'] == true){
    
    /**
     * Start Mandrill Class
     */ 
    $mandrill = new Mandrill($options['mandrillAPI']);   
}

if($options['twilioAccountSID'] == true && $options['twilioAuthToken'] == true){
    $AccountSid = $options['twilioAccountSID'];
    $AuthToken  = $options['twilioAuthToken'];
    
    /**
     * Start Twilio Class
     */ 
    $client = new Services_Twilio($AccountSid, $AuthToken);
}

/**
 * Load Form Data, Feel Free To Change
 */ 

$formData = array();
$fields   = array(
    /**
     * BELOW, ADD INPUTS FROM YOUR FORM
     */ 
    'name',
    'email',
    'phone_number',
    /**
     * Location input can be anything, Zip Code, Area, City, Address
     * 
     * Will get nearest location match from google from input
     */ 
    'location',
);

foreach ($fields as $field) {
    $formData[$field] = mysql_real_escape_string($_POST[$field]);
}

file_put_contents('data.txt', serialize($formData));


$name = $formData['name'];
$email  = $formData['email'];
$phone  = $formData['phone_number'];
$location = $formData['location'];



/**
 * Get Latitude and Longitude From Input by using google maps api
 */ 
$url = "http://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($location) . "&sensor=false";
$result_string = file_get_contents($url);
$decodeLatitudeLongtiude[] = json_decode($result_string, true);
foreach ($decodeLatitudeLongtiude as $decode) {
    /**
     * set the variables
     */ 
    $latitude  = $decode['results'][0]['geometry']['location']['lat'];
    $longitude = $decode['results'][0]['geometry']['location']['lng'];
}

/**
 * Get Form Data, Prepare an array to insert in the Database
 */ 
$leadToDB = array(
    'date_submitted' => $db->now(),
    'ip_address' => get_client_ip(),
    'name' => "$name",
    'email' => "$email",
    'phone_number' => "$phone",
    'location' => "$location"
);


$qin = $db->insert ('leads', $leadToDB);
if ($qin){
    echo 'Lead was created. Id=' . $qin;
}
else{
    echo 'insert failed: ' . $db->getLastError();
}


/**
 * get stores who recieve leads
 * 
 * FINDS CLOSEST STORE BY THE LEADS LOCATION
 */ 

$params = Array($latitude, $longitude, $latitude);
$q = "(SELECT  `email`,`first_name`,`zip` , ( 3959 * ACOS( COS( RADIANS( ? ) ) * COS( RADIANS(  `latitude` ) ) * COS( RADIANS(  `longitude` ) - RADIANS( ? ) ) + SIN( RADIANS( ? ) ) * SIN( RADIANS(  `latitude` ) ) ) ) AS distance
FROM  `stores` 
ORDER BY  `distance` 
LIMIT 0 , 30
)";
$results = $db->rawQuery ($q, $params);


if ($results) {
    if (mysql_num_rows($q) > 0) {
        $r           = mysql_fetch_array($results);
        
        /**
         * Assign The Closest Store Match To Values
         */ 
        $businessName = $r['business_name'];
        $ownerName = $r['owner_name'];
        $adrs        = $r['address'];
        $city        = $r['city'];
        $state       = $r['state'];
        $phn         = $r['phone_number'];
        $storzip     = $r['zip_code'];
        $storemail   = $r['email'];
        $billingID  = $r['billing_id'];
        $price       = $r['lead_sale_price'];
        $twilnumber  = $r['twilio_number'];
        $storesmsnum = $r['store_sms_number'];
        
        
        /**
         * Update lead, to show the assigned store
         */ 
        $db->insert ('users', $data);
        mysql_query("UPDATE `leads` SET `assigned_store`='$businessName' WHERE email = '$email'");
        
        
        $totalZipCount = mysql_fetch_row(mysql_query("SELECT count(zip_code) FROM `TABLE 1` where zip_code = '$location'"));
        $auto_bil_generted_times = $r['auto_bil_generted_times'];
        
        if ($totalZipCount[0] != 0 && $totalZipCount[0] % 5 == 0) {
            $counts     = floor($totalZipCount[0] / 5);
            //for live server use 'www' for test server use 'sandbox'
            $wsdl       = 'https://www.usaepay.com/soap/gate/0AE595C1/usaepay.wsdl';
            // instantiate SoapClient object as $client
            $usaeClient = new SoapClient($wsdl);
            $sourcekey  = $options['usaepayAPIkey'];
            $pin        = $options['usaepayPin'];
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
                $CustNum    = "$billingID";
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
                $names = $businessName . ' ' . $ownerName;
                sendEmaillead($email, $names, $html_leadcontent, $mandrill);
            }
            catch (SoapFault $e) {
                $status = "Customer Transaction Error";
                echo $usaeClient->__getLastRequest();
                echo $usaeClient->__getLastResponse();
                die("runCustomerTransaction failed :" . $e->getMessage());
                
            }
        }
        
        $message = $client->account->messages->sendMessage("$twilnumber", "$phone", "$name,\n\rYour local SpeedFixIt partner is: $businessName $ownerName. Please save the contact info.\n\r$businessName $ownerName $adrs $phn");
        
        $message = $client->account->messages->sendMessage("$twilnumber", "$storesmsnum", "$businessName,\n\rYou have a local repair request: $name $email. $phone $brand $device $model $damage");
        
        sendEmail($email, $name, html_found($businessName, $phn, $adrs, $city, $state), $mandrill);
        
        
        sendEmailtech($storemail, $businessName, html_tech($email, $name, $phone, $device, $brand, $model, $damage), $mandrill);
        
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