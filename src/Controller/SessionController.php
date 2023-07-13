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
        // Check if session exists inside 
        
    }
}