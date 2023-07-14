<?php

namespace Drupal\drupal_gpt\Controller;

use Drupal\drupal_gpt\Model\DrupalGPTSession;
use Drupal\Core\Controller\ControllerBase;

class SessionController extends ControllerBase {


    protected ApiController $api_controller;
    protected DrupalGPTPrompt $drupal_gpt_prompt;

    function __construct($message){
        $this->api_controller = new ApiController();
    }

    public function cleanSession($request){

    }

    /**
     * 
     * @param Request $request
     * @return JsonResponse - returns session id
     * 
     */
    private function getSession($session_id){
        // Check if session exists inside 
        $query = \Drupal::entityQuery('node')
            ->condition('type', 'drupal_gpt_session')
            ->condition('field_chat_session_id', $session_id);
        $nids = $query->execute();

        // Either way we will load the node
        $node = null;
        if(count($nids) > 0){
            $nid = reset($nids);
            $node = \Drupal\node\Entity\Node::load($nid);
        }else{
            $node = \Drupal\node\Entity\Node::create([
                'type' => 'drupal_gpt_session',
                'chat_session_id' => $session_id,
                'chat_session_timestamp' => time(),
                'body' => "",

              ]);
              $node->save();
        }
        return new DrupalGPTSession($session_id, $node);
    }

    /**
     * 
     * Handle a user message and generate an API response
     * 
     */
    public function processUserMessage($session_id, $message){
        $session = $this->getSession($session_id);
        $session->chat_session_timestamp = time();
        $session->addMessage($message, "", true, false);

        $ai_message_context = $this->api_controller->getContextFromMessage($message);
        $ai_message_text = $this->api_controller->returnMessageChainText($session->generateMessageArray());
        $message_object = $session->addMessage($ai_message_text, $ai_message_context);
        return new JsonResponse([
                "message" => 
                    [ 
                        "accuracy" => $message_object->getAccuracy(),
                        "message" => $message_object->getMessage()
                    ],
            ]);
        }
}