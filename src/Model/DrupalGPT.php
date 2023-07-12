<?php

namespace Drupal\drupal_gpt\Model;

use Drupal\drupal_gpt\Controller\ApiController;

class DrupalGPT{

    protected ApiController $apiController;

    /**
     * 
     * Intitalize the DrupalGPT 
     * 
     */
    function __constructor(){
        $this->apiController = new ApiController;
    }

}