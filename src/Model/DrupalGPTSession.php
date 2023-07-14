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
        $this->loadMessagesFromNode();
    }
    
    public function addMessage($message, $context = "", $already_processed = false, $ai_response = true){
        $drupal_gpt_message = new DrupalGPTMessage($message, $context, $already_processed, $ai_response);
        $message_chain[] = $drupal_gpt_message;
        $this->saveToNode();
        return $drupal_gpt_message;
    }

    /**
     * 
     * Load from node to fill message_chain
     * 
     */
    private function loadMessagesFromNode(){
        $json = json_decode($this->session_node->get("body"));
        foreach($json as $message){
            $accuracy = $json["accuracy"];
            $context = $json["context"];
            $ai_response = false;
            if($json["role"]=="assistant") $ai_response = true;
            $this->message_chain[] = new DrupalGPTMessage($json["content"], "", true, true, $accuracy);
        }
    }

    private function saveToNode(){
        $json = [
            "messages"=>[]
        ];
        foreach($this->message_chain as $message){
            $message_json = [
                "accuracy"=>$message->getAccuracy(),
                "content"=>$message->getMessage(),
                "context"=>$message->getContext(),
                "role"=>$message->getMessageAuthor(),
            ];


            $json["messages"][] = $message_json;
        }
        $this->session_node->set("chat_session_timestamp",time());
        $this->session_node->set("body",json_encode($json));
        $this->session_node->save();
    }

    

}