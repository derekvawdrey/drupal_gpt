<?php

namespace Drupal\drupal_gpt\Model;

use Drupal\drupal_gpt\Controller\ApiController;

class DrupalGPTMessage {
    protected ApiController $api_controller;

    
    // The message and context of the string
    protected string $message;
    protected string $context;
    protected string $message_author;

    // Determines if the message is a previous message or not
    protected bool $already_processed;
    protected bool $ai_response;
    
    // Determines if the previous message was accuracy
    protected float $accuracy;

    function __construct($message, $context = "", $already_processed = false, $ai_response = true, $accuarcy = 1){
        $this->api_controller = new ApiController();
        $this->message = $message;
        $this->context = $context;
        $this->already_processed = $already_processed;
        $this->ai_response = $ai_response;

        $this->message_author = "user";
        if($this->ai_response) $this->message_author = "assistant";

        if(!$already_processed && $ai_response){
            $this->accuracy = $this->verifyAccuracy($this->message, $this->context);
        }else {
            $this->accuracy = $accuarcy;
        }
    }

    /**
     * 
     * Be able to implement something that will verify if the response is accuracy or not based on the information pulled in from
     * the vector database
     * 
     */

      private function verifyAccuracy(){
         $config = \Drupal::config('drupal_gpt.settings');
         $enabled = $config->get("enabled_accuracy_meter");
         if($enabled){
            return $this->api_controller->getMessageAccuracy($this->message, $this->context);
         }
         return 1;
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
     public function getMessageAuthor(){
        return $this->message_author;
     }

     public function getContext(){
        return $this->context;
     }

      public function setAccuracy($accuarcy){
         $this->$accuarcy = (float)$accuarcy;
      }

      public function setMessage($message){
         $this->message = $message;
      }
      
      public function setMessageAuthor($role){
         $this->message_author = $role;
      }

      public function setContext($context){
         $this->context = $context;
      }


}