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



}