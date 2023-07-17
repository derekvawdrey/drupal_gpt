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
    
    private function getPineconeEnvironment(){
        $config = \Drupal::config('drupal_gpt.settings');
        return $config->get('pinecone_environment');
    }

    private function getPineconeKey(){
        $config = \Drupal::config('drupal_gpt.settings');
        return $config->get('pinecone_key');
    }

    private function getPineconeIndex(){
        $config = \Drupal::config('drupal_gpt.settings');
        return $config->get('pinecone_index_url');
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

    public function getEmbeddingFromMessage($message){
        $ch = curl_init();
        $url = 'https://api.openai.com/v1/embeddings';
        $api_key = $this->getApiKey();
        $post_fields = array(
            "model" => "text-embedding-ada-002",
            "input" => $message,
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
        return json_decode($result,true)["data"][0]["embedding"];
    }


    public function getContextFromMessage($message){

        $embedding = $this->getEmbeddingFromMessage($message);
        \Drupal::logger("embedding")->info(json_encode($embedding));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getPineconeIndex() . '/query');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Api-Key: ' . $this->getPineconeKey(),
            'Content-Type: application/json',
        ]);

        $post_fields = array(
            "vector" => $embedding,
            "topK" => 3,
            "includeValues" => false,
            "includeMetadata" => true
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));

        $response = json_decode(curl_exec($ch),true);

        curl_close($ch);
        return $response["matches"][0]["metadata"]["text"];
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