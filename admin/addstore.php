<?php
require_once 'admininit.php';
/**
 * Add Store, Just Run This Page In The Browser after filling in array below. Refresh will duplicate
 * 
     * 'stores' table colums:
     * | id | business_name | owner_name | address | city | state | phone_number | zip_code | email | billing_id | lead_sale_price | twilio_number | store_sms_number | latitude | longitude
     */ 

$store = array(
    'business_name' => '',
    'owner_name' => '',
    'address' => '',
    'city' => '',
    'state' => '',
    'phone_number' => '',
    'zip_code' => '',
    'email' => '',
    'billing_id' => '',
    'lead_sale_price' => '',
    'twilio_number' => '',
    'store_sms_number' => '',
    /**
     * LATITUDE AND LONGITUDE ARE EMPTY, SCRIPT WILL AUTO FILL THAT IN
     */ 
);

?>