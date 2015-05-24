<?php
class store{
    /**
     * This Class Allows You To Add Or Delete Stores
     */ 
    /**
     * 'stores' table colums:
     * | id | business_name | owner_name | address | city | state | phone_number | zip_code | email | billing_id | lead_sale_price | twilio_number | store_sms_number | latitude | longitude
     */ 
    
    public addStore($data){
        $db->insert ('stores', $data);
    }
}

?>