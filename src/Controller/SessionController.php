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
    public function initSession($request){
        // Get the session manager.
        $session_manager = \Drupal::service('session_manager');
        // Retrieve the session identifier from the form.
        $gpt_session = new DrupalGPTSession();
        $session_id = $gpt_session->getSessionId();
    }
}