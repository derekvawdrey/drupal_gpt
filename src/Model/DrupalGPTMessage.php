<?php

namespace Drupal\drupal_gpt\Model;

use Drupal\drupal_gpt\Controller\ApiController;

class DrupalGPTMessage {
    protected ApiController $api_controller;
    protected string $message;
    protected string $gpt_modified_message;

    function __construct($message){
        $this->api_controller = new ApiController();
        $this->message = $message;
        $this->gpt_modified_message = $this->promptifyMessage($this->message);
    }

    private function promptifyMessage(string $message){
        
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
                "content" => "Text: ".$message."
                                Response:"
            ]
        ];


        return $this->api_controller->returnMessageChainText($prompt);
    }





}