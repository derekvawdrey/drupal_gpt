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
        $json = [];
        if(!empty($this->session_node->get("body")->value)){
            $json = json_decode($this->session_node->get("body")->value, true);
        }
        if(isset($json["messages"])){
            foreach($json["messages"] as $message){
                $accuracy = $message["accuracy"];
                $context = $message["context"];
                $content = $message["content"];
                $role = $message["role"];
                $ai_response = false;
                if($role == "assistant") $ai_response = true;
                $drupal_gpt_message = new DrupalGPTMessage($content, "", true, true, $accuracy);
                $drupal_gpt_message->setAccuracy($accuracy);
                $drupal_gpt_message->setContext($context);
                $drupal_gpt_message->setMessageAuthor($role);
                $drupal_gpt_message->setMessage($content);
                $this->message_chain[] = $drupal_gpt_message;
            }
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

    public function generateMessageArray($prompt = ""){

        if(empty($prompt)){
            $prompt = "You will keep responses less than 60 words. 
            Instead of giving inaccurate information, reply with something like 'Sorry, I am not sure'.
            You will refuse to respond with anything inappropriate or would put BYU in a bad light.
            You will talk in the style of David O. McKay. You will have an energetic writing style, be engaging, and fun.";
        }
        // Do this
        $messages = [];
        $increment = 0;
        $last_message = null;
        foreach($this->message_chain as $message){

            // Append Context for message and also how the AI should act
            
            //Append the users response to the thread
            $message_json = [
                "content"=>$message->getMessage(),
                "role"=>$message->getMessageAuthor(),
            ];
            $messages[] = $message_json;

            $last_message = $message;
            $increment++;

        }

        // Insert the last messages context into the message chain
        if($last_message != null){
            if($last_message->getContext() != null){
                $message_json = [
                    "content" => "START CONTEXT\n" . $last_message->getContext() . "\nEND CONTEXT\n",
                    "role"=> "user"
                ];
                array_unshift($messages, $message_json);
            }
        }

        
        // Inject the system prompt into the context
        $prompt_message = [
            "content" => $prompt,
            "role"=> "system"
        ];
        $messages[] = $prompt_message;

        return $messages;
    }

}