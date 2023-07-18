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
    private function getAccuracyGptModel(){
        $config = \Drupal::config('drupal_gpt.settings');
        return $config->get('openai_model_accuracy');
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
     * @param array $messages, The conversation chain so that the AI can return a message
     * @param int $max_tokens, the total number of tokens that the API can return as a response
     * @param int $temperature, the randomness of the message
     * @return Json The json of the message returned
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
     * @return double A number from 1.00 to 0.00 depicting accuracy.
     * 
     */
    private function messageAccuracyVerification($message, $context, int $max_tokens = 250, float $temperature = 0.7){
        
        $prompt = [
            [
                "role" => "system",
                "content"=>"You are to determine if a message is accurate to factual data. 
                You will respond only with a decimal representation of the accuracy of the data: 1.0 is accurate, 0.0 is inaccurate. 
                Based on the context provided below, you will determine if the message is accurate.
                If the message doesn't contain or require factual informaton, respond with 1.0

                Context:
                ###" . $context . "###"
            ],
            [
                "role" => "user",
                "content" => "MESSAGE: " . $message
            ],
        ];
        
        $ch = curl_init();
        $url = 'https://api.openai.com/v1/chat/completions';
        $api_key = $this->getApiKey();
        $accuracy_model = $this->getAccuracyGptModel();
        if(empty($accuracy_model)) $accuracy_model = $this->getGptModel();
        $post_fields = array(
            "model" => $this->getAccuracyGptModel(),
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

    /**
     * 
     * @param string $message, convert the users message into a embedding vector
     * @param array embedding vector
     * 
     */
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

    /**
     * 
     * @param string $id, id of the pinecone embeddeded entry
     * @param string $context, the text that will be pulled in as context for ChatGPT
     * @param string $category, the category the context belongs to
     * 
     */
    public function updateContextFromId($id, $context, $category){
        $embedding = $this->getEmbeddingFromMessage($context);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getPineconeIndex() . '/vectors/update');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Api-Key: ' . $this->getPineconeKey(),
            'Content-Type: application/json',
        ]);

        $post_fields = array(
            "id"=> $id, 
            "setMetadata"=>[
                "context"=>$context,
                "category"=>$category
            ],
            "values"=>$embedding
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
        curl_close($ch);
    }

    /**
     * 
     * Inserts the context into the pinecone database
     * @param string $id, id of the pinecone embeddedd entry
     * @param string $context, the text that will be pulled in as context for ChatGPT
     * @param string $category, the category the context belongs to
     * 
     */
    public function insertContext($id, $context, $category){
        $embedding = $this->getEmbeddingFromMessage($context);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getPineconeIndex() . '/vectors/upsert');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Api-Key: ' . $this->getPineconeKey(),
            'Content-Type: application/json',
        ]);

        $post_fields = array(
            "vectors"=>[
                [
                    "id"=>$id,
                    "values"=>$embedding,
                    "metadata"=>[
                        "context"=>$context,
                        "category"=>$category,
                    ],   
                ],
            ],
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));
        curl_close($ch);
    }


    /**
     * 
     * @param string $id
     *
     */
    public function deleteContextFromId($id){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getPineconeIndex() . '/vectors/delete?ids=' . $id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Api-Key: ' . $this->getPineconeKey(),
            'Content-Type: application/json',
        ]);
        curl_close($ch);
    }

    /**
     * 
     * 
     * @param string $message, The users message
     * @return string The context needed to answer the users question.
     * 
     */
    public function getContextFromMessage($message, $category){

        $embedding = $this->getEmbeddingFromMessage($message . "Program is: " . $category);
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
            \Drupal::logger("accuracy_rating")->info($return_message);
            return (float)$return_message;
        }
        return 0;
    }

    /**
     * 
     * @param array $messages
     * @param int $max_tokens
     * @param int $temperature
     * @return string, the AI's response
     * 
     */
    public function returnMessageChainText($messages, int $max_tokens = 250, float $temperature = 0.7){
        $message_response = $this->messageChainAICall($messages, $max_tokens, $temperature);
        if(isset($message_response["choices"])) return $message_response["choices"][0]["message"]["content"];
        return "There was an error generating the response.";
    }

}