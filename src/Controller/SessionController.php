<?php

namespace Drupal\drupal_gpt\Controller;

use Drupal\drupal_gpt\Model\DrupalGPTSession;
use Drupal\Core\Controller\ControllerBase;

class SessionController extends ControllerBase {

    public function cleanSession($request){

    }

    /**
     * 
     * @param Request $request
     * @return JsonResponse - returns session id
     * 
     */
    public function getSession($session_id){
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
        return $node;
    }

    /**
     * 
     * Handle the message and return it to the user
     * 
     * 
     */
    public function testMessage($response){

    }
}