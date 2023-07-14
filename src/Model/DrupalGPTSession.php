<?php

namespace Drupal\drupal_gpt\Model;

use Drupal\node\Entity\Node;
use Drupal\drupal_gpt\Controller\ApiController;

class DrupalGPTSession {

    // Array of DrupalGPTMessages
    protected array $message_chain;
    protected string $session_id;


    // Should be of type drupal_gpt_session that is defined in the config/install
    protected Node $session_node;

    function __contruct($session_id, $session_node){
        $this->session_id = $session_id;
        $this->session_node = $session_node;

    }
    
    public function addMessage($message, $context = "", $already_processed = false, $ai_response = true){
        $drupal_gpt_message = new DrupalGPTMessage($message, $context, $already_processed, $ai_response);
        $message_chain[] = $drupal_gpt_message;
        $this->saveToNode();
        return $drupal_gpt_message;
    }

    public function generateMessageArray(){
        $this->session_node;
    }

    private function loadMessagesFromNode(){
        
    }

    private function saveToNode(){

    }

    

}