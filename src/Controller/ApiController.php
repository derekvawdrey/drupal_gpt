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

    /**
     * 
     * @param String $message
     * @param String $context
     * @param int $max_tokens
     * @param int $temperature
     * @return 
     * 
     */
    private function messageAccuracyVerification($message, $context, int $max_tokens = 250, float $temperature = 0.7){
        
        $prompt = [
            [
                "role" => "system",
                "content"=>"You are to determine if a message is accurate to factual data. 
                You will respond only with a decimal representation of the accuracy of the data: 1 is accurate, 0 is inaccurate. 
                Based on the context provided below, you will determine if the message is accurate.
                Context:
                ###" . $context . "###"
            ],
            [
                "role" => "user",
                "content" => $message
            ],
        ];
        
        $ch = curl_init();
        $url = 'https://api.openai.com/v1/chat/completions';
        $api_key = $this->getApiKey();
        $post_fields = array(
            "model" => $this->getGptModel(),
            "messages" => $prompt,
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


    public function getContextFromMessage($message){
        return "
        Graduation requirements
        Complete 68-69 credits of coursework
        No grade lower than a C in any program course
        14-week student teaching OR full year paid internship
        Pass the Praxis II exam
        Application for licensure with the Utah State Board of Education
        ";
    }

    /**
     * 
     * @param String $message
     * @param String $context
     * @param int $max_tokens
     * @param int $temperature
     * @return boolean
     * 
     */
    public function getMessageAccurate($message, $context, int $max_tokens = 250, float $temperature = 0.7){
        $return_string = $this->messageAccuracyVerification($message, $context, $max_tokens, $temperature);
        $return_message = "";
        if (isset($return_string["choices"])){
            $return_message = $return_string["choices"][0]["message"]["content"];
            return (float)$return_message;
        }
        return 0;
    }

    /**
     * 
     * @param array $messages
     * @param int $max_tokens
     * @param int $temperature
     * 
     */
    public function returnMessageChainText($messages, int $max_tokens = 250, float $temperature = 0.7){
        $message_response = $this->messageChainAICall($messages, $max_tokens, $temperature);
        if(isset($message_response["choices"])) return $message_response["choices"][0]["message"]["content"];
        return "There was an error generating the response.";
    }

}