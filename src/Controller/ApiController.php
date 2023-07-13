<?php

namespace Drupal\drupal_gpt\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
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
    
    

    /**
     * 
     * This is for a message chain, not a single prompt
     * 
     */
    private function messageChainAICall($messages, int $max_tokens = 250, float $temperature = 0.7){
        $ch = curl_init();
        $url = 'https://api.openai.com/v1/chat/completions';
        $api_key = $this->getApiKey();
        $post_fields = array(
            "model" => $this->getGptModel(),
            "messages" => $messages,
            "max_tokens" => $max_tokens,
            "temperature" => $temperature
        );
        $header = [
            'Content-Type: application/json',
            'Authorization: Bearer '. $api_key
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result,true);
    }

    public function testPrompt(){
        $prompt = [
            ["role" => "system",
            "content"=>"
            Optimize the text to be more specific and explain exactly what the user wants in relation to Brigham Young University (BYU) and the McKay School of Education.
            The optimized text should be specific to Brigham Young University and the McKay School of Education.
            Examples are incased in '###'
            ###
            Text: how many education majors are there?
            Response: Could you please provide me with a comprehensive list of education majors offered for enrollment specifically at the McKay School of Education, located at Brigham Young University (BYU)? I'm interested in knowing the various education majors that students can pursue within the McKay School of Education at BYU.
            ###
            Text: where can I find my advisors
            Response: Where can I find information about my advisors at the McKay School of Education at BYU? I am looking to either meet with them, talk with them, or send them an email.
            ###"
            ],
            [
                "role" => "system",
                "content" => "Text: Act like a pirate
                                Response:"
            ]
        ];
        return new JsonResponse([""=>$this->returnMessageChainText($prompt,100,0)]);
    }

    public function returnMessageChainText($messages, int $max_tokens = 250, float $temperature = 0.7){
        return $this->messageChainAICall($messages,$max_tokens,$temperature)["choices"][0]["message"]["content"];
    }

}