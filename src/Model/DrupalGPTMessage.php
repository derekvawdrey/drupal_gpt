<?php

namespace Drupal\drupal_gpt\Model;

use Drupal\drupal_gpt\Controller\ApiController;

class DrupalGPTMessage {
    protected ApiController $api_controller;

    // The message and context of the string
    protected string $message;
    protected string $context;

    // Determines if the message is a previous message or not
    protected boolean $already_processed;
    
    // Determines if the previous message was accuracy
    protected float $accuracy;

    function __construct($message, $context = "", $already_processed = false, $ai_response = true){
        $this->api_controller = new ApiController();
        $this->message = $message;
        $this->context = $context;
        $this->already_processed = $already_processed;
        if(!$already_processed && $ai_response){
            $this->accuracy = $this->verifyAccuracy($this->message, $this->context);
        }else{
            $this->accuracy = 0;
        }
    }

    /**
     * 
     * Be able to implement something that will verify if the response is accuracy or not based on the information pulled in from
     * the vector database
     * 
     */

     private function verifyAccuracy(){
        return $this->api_controller->getMessageAccurate($this->message, $this->context);
     }

     public function isAccurate(){
        if($this->accuracy > 0.6) return true;
        return false;
     }

     public function getAccuracy(){
        return $this->accuracy;
     }

     public function getMessage(){
        return $this->message;
     }



}