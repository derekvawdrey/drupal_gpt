<?php

namespace Drupal\drupal_gpt\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class EmbeddedEntryController extends ControllerBase {

    protected ApiController $api_controller;

    function __construct(){
        $this->api_controller = new ApiController();
    }
    
    /**
     * 
     * Updates an entry that already exists inside the pinecone database
     * 
     * @param Request $request
     *  Should include:
     *      - id
     *      - context
     *      - category
     * 
     */
    public function updateEmbeddedEntry(Request $response){
        $id = $request->query->get('id');
        $context = $request->query->get('context');
        $category = $request->query->get('category');
        if(empty($id) || empty($context) || empty($category)){
            return new JsonResponse(["error"=>"Requires id, context, and category"]);
        }
        $this->api_controller->updateContextFromId($id, $context, $category);
    }

    /**
     * 
     * Creates an entry that does not already exist inside the pinecone database
     * 
     * @param Request $request
     *  Should include:
     *      - id
     *      - context
     *      - category
     * 
     */
    public function insertEmbeddedEntry(Request $response){
        $id = $request->query->get('id');
        $context = $request->query->get('context');
        $category = $request->query->get('category');
        if(empty($id) || empty($context) || empty($category)){
            return new JsonResponse(["error"=>"Requires id, context, and category"]);
        }
        $this->api_controller->insertContext($id, $context, $category);
    }

    /**
     * 
     * @param Request $request
     *  Should include:
     *      - id
     * 
     */
    public function deleteEmbeddedEntry(Request $response){
        $id = $request->query->get('id');
        if(empty($id)){
            return new JsonResponse(["error"=>"Requires id"]);
        }
    }


    private function splitTextIntoChunks($text, $chunkSize) {
        // Split the text into individual words
        $words = str_word_count($text, 1);
    
        // Initialize an empty array to store the chunks
        $chunks = [];
    
        // Loop through the words and create chunks of specified size
        $currentChunk = '';
        $wordCount = 0;
    
        foreach ($words as $word) {
            $currentChunk .= $word . ' ';
            $wordCount++;
    
            // Check if the chunk size has been reached
            if ($wordCount >= $chunkSize) {
                // Add the current chunk to the array
                $chunks[] = trim($currentChunk);
    
                // Reset variables for the next chunk
                $currentChunk = '';
                $wordCount = 0;
            }
        }
    
        // Add any remaining words as the last chunk
        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }
    
        return $chunks;
    }
    /**
     * 
     * Using chatGPT we seperate out the sections of the paragraphs out into sections.  
     * 
     * 
     */
    private function splitIntoSections($text){
        $delimeter = "###";
        $prompt = "Seperate these paragraphs into sections, Each section will be separated by a single delimeter '" . $delimeter . "' placed only at the beginning of the section. 
        Be sure to include valuable information into each section and include any pertinent factual information such as numbers, contact information, and others. 

        For example:
        " . $delimeter . "title goes here: section summary goes here
        " . $delimeter . "title goes here: section summary goes here
        "
        . "START PARAGRAPHS" . $text . "END PARAGRAPHS";

        $messages = [
            ["role"=>"system",
            "content"=>$prompt],
        ];
        // Full summary of the text
        $summary = $this->api_controller->returnMessageChainText($messages, 2000);
        // Summaries split by delimeter
        $summaries = explode($delimeter, $summary);
        return $summaries;
    }


    /**
     * 
     * This function will take a POST parameter 'context' and then 
     *  1. Seperate it out into chunks of 1750 words
     *  2. Have openAI seperate it out into section summaries
     *  3. embed each section as a singular context into pinecone for a specific category
     * @request POST context - The text of the document
     * @request POST category - What category does the context belong to?
     * @return JsonResponse, Gives the user a general uuid to attribute to that specific context, and then a list of uuids
     * 
     */
    public function embedIntoPinecone(Request $request){
        $context = \Drupal::request()->request->get('context');
        $category = \Drupal::request()->request->get('category');
        // If the context is too large to be processed, we will split it into seperate chunks to be summarized
        $contexts = $this->splitTextIntoChunks($context,1750);

        // This is the general uuid for the group of contexts
        $general_uuid = $this->format_uuidv4(random_bytes(16));
        $uuids = [];
        foreach($contexts as $string){
            $chunks = $this->splitIntoSections($string);
            foreach($chunks as $chunk){
                \Drupal::logger("embeddings")->info($chunk);
                if($chunk==$this->api_controller->getErrorMessage()) break;
                if(empty($chunk)) continue;
                $current_uuid = $this->format_uuidv4(random_bytes(16));
                $uuids[] = $current_uuid;
                $this->api_controller->insertContext($current_uuid, $chunk, $category);
            }
        }
        return new Jsonresponse(["uuid"=>$general_uuid, "vector_ids"=>$uuids]);
    }

    // TODO: Move to a utilities class
    private function format_uuidv4($data)
    {
        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
            
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

}