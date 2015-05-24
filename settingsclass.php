<?php

class settings{
    
    private $config = array;
    
    
    public function setSettings($array){
        $this->config = $array;
    }
    
    
    public function getSettings($array){
        return $this->config;
    }
}

?>