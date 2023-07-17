<?php

namespace Drupal\drupal_gpt\Controller;

use Drupal\drupal_gpt\Model\DrupalGPTSession;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class SessionController extends ControllerBase {


    protected ApiController $api_controller;
    protected DrupalGPTPrompt $drupal_gpt_prompt;

    function __construct(){
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
            ->condition('chat_session_id', $session_id);
        $nids = $query->execute();

        // Either way we will load the node
        $node = null;
        if(count($nids) > 0){
            $nid = reset($nids);
            $node = \Drupal\node\Entity\Node::load($nid);
        }else{
            $node = \Drupal\node\Entity\Node::create([
                'type' => 'drupal_gpt_session',
                'title' => $session_id,
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
    private function processUserMessage($session_id, $message){
        $session = $this->getSession($session_id);
        $message_context = $this->api_controller->getContextFromMessage($message);
        $session->addMessage($message, $message_context, true, false);
        $ai_message_text = $this->api_controller->returnMessageChainText($session->generateMessageArray());
        $message_object = $session->addMessage($ai_message_text, $message_context);
        return new JsonResponse(
            [
                "message" => 
                    [ 
                        "accuracy" => $message_object->getAccuracy(),
                        "message" => $message_object->getMessage(),
                        "context_provided" => $message_object->getContext(),
                    ],
            ]
        );
    }

    public function processMessageEndpoint(Request $request){
        $message = $request->query->get('message');
        $session_id = $request->query->get('session_id');
        return $this->processUserMessage($session_id, $message);
    }
}