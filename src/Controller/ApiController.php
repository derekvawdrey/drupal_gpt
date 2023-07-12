<?php

namespace Drupal\drupal_gpt\Controller;

class ApiController{

    private function getApiKey(){
        $config = \Drupal::config('drupal_gpt.settings');
        $mySetting = $config->get('openai_key');
    }
    
    private function getGptModel(){
        $config = \Drupal::config('drupal_gpt.settings');
        $mySetting = $config->get('openai_model');
    }
    
    
}