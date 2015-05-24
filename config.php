<?php

/**
 * **IMPORTANT**
 * 
 * CHANGE SETTINGS TO WHAT YOU WANT, Scroll all the way down to make sure settings are correct. 
 * 
 * 
 */ 
$config = array(
    
    /**
     * SMS Lead Alerts to Store?
     */ 
    
    'SMS' => true,
    
    /**
     * Email Lead Alerts To Store?
     */ 
    
    'email' => true,
    
    /**
     * Phone Call Lead Alerts To Store?
     */ 
    
    'phone' => true,
    
    /**
     * Do You Want The Admin To Get SMS alerts for each lead?
     */ 
    
    'adminSMS' => true,
    
    /**
     * Do You Want The Admin To Get Email Alerts for Each Lead?
     */ 
    
    'adminEmail' => true,
    
    /**
     * Do You Want The Admin To Get Phone Call Alerts For Each lead?
     */ 
    
    'adminPhone' => true,
    
    /**
     * Fill In The Following API Keys
     */ 
    'mandrillAPI' => '',
    'twilioAccountSID' => '',
    'twilioAuthToken' => '',
    
    /**
     * Fill In Admin Settings
     */ 
    'adminPhoneNumber' => '',
    'adminEmailAddress' => '',
    
    /**
     * MYSQL DATABASE CONNECTION SETTINGS
     */ 
    'host' => '',
    'username' => '',
    'password' => '',
    'databaseName' => '',
    
    /**
     * USAePAY API
     */ 
    'usaepayAPIkey' => '',
    'usaepayPin' => ''
);

/**
 * Initiates Class, IGNORE
 */ 
$settings = new settings();
$settings->setSettings($config);

?>