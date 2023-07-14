<?php

namespace Drupal\drupal_gpt\Model;

use Drupal\node\Entity\Node;
use Drupal\drupal_gpt\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;

class DrupalGPTSession {

    // Array of DrupalGPTMessages
    protected array $message_chain;
    protected string $session_id;


    // Should be of type drupal_gpt_session that is defined in the config/install
    protected Node $session_node;

    function __construct($session_id, $session_node){
        $this->session_id = $session_id;
        $this->session_node = $session_node;
        $this->message_chain = [];
        $this->loadMessagesFromNode();
    }
    
    public function addMessage($message, $context = "", $already_processed = false, $ai_response = true){
        $drupal_gpt_message = new DrupalGPTMessage($message, $context, $already_processed, $ai_response);
        $this->message_chain[] = $drupal_gpt_message;
        $this->saveToNode();
        return $drupal_gpt_message;
    }

    /**
     * 
     * Load from node to fill message_chain
     * 
     */
    private function loadMessagesFromNode(){
        $this->message_chain = [];
        $json = json_decode($this->session_node->get("body")->value, true);
        \Drupal::logger("DrupalGPT")->info(json_encode($json,true));
        foreach($json["messages"] as $message){
            $accuracy = $message["accuracy"];
            $context = $message["context"];
            $ai_response = false;
            if($json["role"] == "assistant") $ai_response = true;
            $this->message_chain[] = new DrupalGPTMessage($message["content"], "", true, true, $accuracy);
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
        $this->session_node->set("body",json_encode($json,true));
        $this->session_node->save();
    }

    public function generateMessageArray(){
        // Do this
        $messages = [];
        foreach($this->message_chain as $message){
            $message_json = [
                "content"=>$message->getMessage(),
                "role"=>$message->getMessageAuthor(),
            ];


            $messages[] = $message_json;
        }
        \Drupal::logger("DrupalGPT")->info(json_encode($messages, true));
        return $messages;
    }

}