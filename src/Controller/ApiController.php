<?php

namespace Drupal\drupal_gpt\Controller;

use Drupal\Core\Controller\ControllerBase;

class ApiController extends ControllerBase {

    private function getApiKey(){
        $config = \Drupal::config('drupal_gpt.settings');
         return $config->get('openai_key');
    }
    
    private function getGptModel(){
        $config = \Drupal::config('drupal_gpt.settings');
        return $config->get('openai_model');
    }
    
    public function handleMessage($sessionId, $message) {
        // Retrieve the session object based on the provided session ID
        $session = $this->getSession($sessionId);

        // Check if the session exists and is active
        if ($session && $session->isActive()) {
            // Update the session timestamp to mark activity
            $session->updateTimestamp();

            // Process the message and perform any necessary actions
            // For example, send the message to your chatbot engine

            // Return a response indicating success
            return new JsonResponse(['status' => 'success']);
        }

        // Return a response indicating session not found or inactive
        return new JsonResponse(['status' => 'error', 'message' => 'Invalid session']);
    }

}