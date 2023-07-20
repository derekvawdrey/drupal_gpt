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

    public function embedIntoPinecone(){
        $strings = ["

        Brigham Young University or BYU offers a program to train students to become school teachers. The BYU Elementary Education program prepares teachers to work in Kindergarten-6th grade classrooms (K-6 Utah Teaching License). Program courses emphasize evidence-based, age appropriate teaching practices for all children in each of the content areas of the core curriculum (math, literacy, science, social studies).
        
        Do you enjoy working with kids? Would you describe yourself as a creative, caring person? Are you interested in helping to inspire and build young minds? If the answer to any of these questions is yes, you’re in the right place! The elementary education program prepares future teachers with the tools and skills needed to teach children from Kindergarten-6th grade. As part of the major, you’ll have the chance to earn a K-6 Teaching License. 
        
        Here are some experiences you’ll have in this program:
        Learn how to teach kids from ages 5-12 using modern teaching styles
        Brush up on your social studies, science, music and math skills! 
        Practice teaching in a real-life setting while you complete a classroom practicum.
        
        For more information on the program, feel free to contact the Education Advisement Center.
        
        
        
        Prerequisites
        If you’re ready to get started on the elementary education major, there are a few steps you’ll need to take before applying:
        
        Declare the Elementary Education pre-major. You can do this online (more instructions here) or by meeting with a program advisor in the Education Advisement Center (EAC). The office is 350 MCKB and their phone number is (801)-422-3426.
        Complete two courses: 
        •SFL 210 - Human Development
        •ELED 200 - Introduction to Education
        
        When those steps are complete, you’ll meet with your advisor. They’ll review your GPA, prerequisite classes, ACT/SAT scores and clear you to begin your application process.
        
        
        How to apply
        Register for a free account on the Educator application system
        Start an Elementary Education program application
        Complete the admissions checklist in the application
        Apply for fingerprinting and FBI background check. Allow from 1-3 weeks for the fingerprints to be processed. FBI background clearance is valid for 3 years
        Complete a graduation plan with a program advisor in the Education  Advisement Center, 350 MCKB
        Graduation requirements
        Complete 68-69 credits of coursework
        No grade lower than a C in any program course
        14-week student teaching OR full year paid internship
        Pass the Praxis II exam
        Application for licensure with the Utah State Board of Education
        All You Need to Know About Elementary Education
        Have you ever considered teaching but have had a hard time choosing a subject? That's okay! Elementary education (El Ed) is one of the all-around best majors BYU has to offer because you get the opportunity to become an expert in a variety of content areas!
        Below are a few fun facts about the El Ed program, mingled with a variety of important things to know
        #1 The Arts: The program is full of classes that help you learn how to incorporate theater, art, music, physical education, and dance into the classroom! The best part is, you learn how to teach this by actually doing it. One of my favorite moments in the drama class was putting on a puppet show with my class after designing and making our own sock puppets! It’s hard not to have a blast while taking these courses! We even have an arts integration minor that is brand new. 
        #2 Hands-On Experience: You have the chance to work with current elementary school teachers in elementary schools during your time at BYU! Throughout the program, students have the opportunity to talk to and observe teachers in their classrooms. In the third year of the program, you participate in “practicum” (essentially, “practice”), in which you go into the schools not only to observe but to start teaching as well.
        #3 Student Teaching: During the last year, students get the chance to do student teaching or be a full-time intern! Both of these options are superb, as both help you build-up to managing and teaching your own class full of students. But both have their upsides! There are four semester-long options for student teachers:
        -Student teach in one of the five partnership districts close to BYU: Alpine, Jordan, Nebo,   Provo City, and Wasatch.
        -Student teach in Houston, Texas.
        -Student teach in Washington, D.C.
        
        
        #4 Internship: When you do an internship you are the actual full-time teacher in a local school district. This being said, you actually get paid half salary! And if you get hired locally the following year, you’ll get paid as if you already are a second-year teacher (which, of course, you will be at that point). But the best perk to the internship is that you get loads of support during your first year of teaching.
        #5 Smaller Class Size: Most of the classes in the El Ed program don’t have more than 30 students, which allows you to work one-on-one with your professors! There are a lot of classes at BYU where you just sit in a big lecture hall and listen to the professor talk. When you have questions about the course, you are directed to speak with a TA first, rather than speaking with the professor. The classes in the elementary education program still have TAs to help you out, but the professors are willing to put in the time to talk to you and help you as much as needed.
        There are two prerequisite courses that you take in the process of applying to the program. They are a great way to explore your interests in education and see if this is the right fit for you!
        SFL 210 - Human Development
        EL ED 200 - Introduction to Education
        In addition to these two classes, the program requires you to complete the following:
        Declare the Elementary Education Pre-Major. See a program advisor in the Education Advisement Center (EAC), 350 MCKB, (801) 422-3426.
        Attend a program orientation meeting - pick up a flier in 350 MCKB, or check the student calendar
        Review GPA and ACT/SAT scores with a program advisor
        Simply put, the Elementary Education program is epic.
        
        
        What made you decide on elementary education?
        
        
        Both of Delaney's parents as well as her older sister are teachers. Delaney explained that she chose education because she '[loves] being around children and teaching, and so elementary education was the perfect fit.' Hearing why others chose to become a teacher is so inspiring. Learning from others, like Delaney, keeps me motivated to be the best teacher I can be!
        
        
        But wait, Delaney didn't stop there. Delaney is also doing a family life minor. She explained to me that she chose this minor because it teaches necessary skills that she can use with her future family.
        
        
        What are your ultimate plans and goals as a teacher?
        
        
        After Delaney graduates she hopes to teach in first or second grade. After she has taught, she hopes to start her own family.
        
        
        Delaney explained that she wants 'to help children develop a love for learning.' She also explained how she wants to be a positive mentor in her students' lives.
        
        
        Any advice for people thinking about becoming a teacher or are just getting started?
        
        
        When asked for advice, Delany simply stated, 'find ways to get into the classroom early because that is the best way to learn.' I could not agree more! The classroom is the perfect place to learn.
        
        
        We are grateful for Delaney and all the other students who are working hard to become a teacher. If you want to learn more about the ElEd major, meet with an advisor or a student ambassador!
        ", "Teacher Salary
        A common concern of prospective teachers is their salary. What will I make? How will I provide for my family? Well, we are here to help ease your fears.
        Welcome to another post in our 'Teacher Salaries' series! Today we want to share some insight from Todd Dawson, the Director of Human Resources for Alpine School District (one of the BYU partnership districts). Todd primarily works with 'secondary certified employee matters, ADA accommodations, teacher evaluation processes, and participate[s] in employee training on policies and procedures.' After talking to Todd, we were able to learn much more about what school districts are doing to help teachers with salary.
        As discussed in previous salary posts, there has been an increase in salary across the state of Utah. Todd explained to us how this works in all districts, and especially in Alpine.
        He shared the following: 'Each district works through a negotiated agreement with the teacher association in the district that is granted negotiation rights (usually based on membership).  Through that process, each district arrives at an agreement regarding salary and benefits, as well as negotiated policies impacted by the contract.  Usually, the salary increases by a percentage each year, and the benefits (health insurance, retirement incentives, working conditions, etc.) are major elements of arriving at an agreed-upon contract.  That being said, districts increase the salary schedule based on the funds received from the state legislature and the agreement arrived at through the negotiation process.  In Alpine School District, in most cases historically, our salary increases through a C.O.L.A. (cost of living allowance) that leads to a permanent adjustment to the salary schedules.'
        In addition to an increase in salary, school districts are doing all they can to maximize the benefits that teachers receive! In fact, Alpine School District has a benefit package near the top of the state! The district 'offers cost-free options for employee and family health insurance' and they also make great efforts in supporting the livelihood of teachers.
        
        
        'Alpine School District provides training, professional development, health and disability insurance, mentoring, mental health resources, additional pay through summer collaboration grants, a professional learning community approach which affords teachers the access to the combined expertise of school and district personnel, salary lane changes based on additional schooling, micro-credentials for licensure and education, and other benefits and resources related to other specific needs.'
        You can rest assured knowing that school districts are doing what they can to help you as a teacher! Teachers make a difference and school districts don't want concerns about salary holding you back. We encourage you to think about both the short-term and long-term benefits of teaching. You won't be disappointed!
        Teacher make an average of 52,000 in their first year of teaching in the state of Utah.
        Have any questions about being a teacher? Reach out to a student ambassador or set up an appointment with an academic advisor. We hope to hear from you soon!
        The elementary program contact information is email tedsec@byu.edu or phone number 801-422-4077
        To contact your advisor also know as an academic advisor call 801-422-3426, come to 350 MCKB during office hours, or email educationadvisement@byu.edu to contact the front desk of the advisement office or schedule an appointment.
        Advisement Office Hours are 9 AM - 5 PM Monday - Friday
        Visit this web page to schedule a time to meet with a student ambassador https://www.beateacherbyu.com/beateacherbyu
        "];
        foreach($strings as $string){
            $chunks = $this->splitIntoSections($string);
            foreach($chunks as $chunk){
                \Drupal::logger("embeddings")->info($chunk);
                if($chunk==$this->api_controller->getErrorMessage()) break;
                if(empty($chunk)) continue;
                $this->api_controller->insertContext($this->format_uuidv4(random_bytes(16)), $chunk, "Elementary Education");
            }
        }
        return new Jsonresponse(["message"=>"successfully inserted"]);
    }


    private function format_uuidv4($data)
    {
    assert(strlen($data) == 16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

}