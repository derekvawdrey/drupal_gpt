(function ($) {
    Drupal.behaviors.drupalGPTCustomBehavior = {
      attach: function (context, settings) {

        function generate_uuidv4() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,
            function(c) {
               var uuid = Math.random() * 16 | 0, v = c == 'x' ? uuid : (uuid & 0x3 | 0x8);
               return uuid.toString(16);
            });
         }
         

        // Access the customVariable passed from PHP.
        var chatbotCategoy = settings.drupal_gpt.category;
        var toggled = false;
        const uuid = generate_uuidv4();
        var processingMessage = false;
        var chatbotTypingElement = `
        <div class="chatbot--writing typing">
            <div class="bubble">
                <div class="ellipsis one"></div>
                <div class="ellipsis two"></div>
                <div class="ellipsis three"></div>
            </div>
        </div>`;
        

        var chatbotMessage = `
        <div class='typing incoming'>
            <div class="bubble">{MESSAGE}</div>
        </div>
        `
        var chatbotCaution = `
        <div class='typing incoming'>
            <div class="bubble caution">⚠️ The above message is more likely to contain incorrect information...</div>
        </div>`

        var userMessage = `
        <div class='outgoing'>
        <div class="bubble">{MESSAGE}</div>
        </div>`;

        var chatbotWindow = `
        <div class='chatbot__window'>
            <div class='chatbot__window--header'>
                <h4>Chat Window</h4>
            </div>
            <div class='chatbot__window--messages'>
                
            </div>
            <div class='chatbot__window--footer'>
                <input class='chatbot__window--message'></input>
                <button class='chatbot__window--sendmessage'>Send</button>
            </div>
        </div>
        `
        var chatbotButton = `
        <div class='chatbot__toggle'>
            <span>+</span>
        </div>
        `

        // Use the customVariable as needed.
        console.log(chatbotCategoy);

        $(document).ready(function(){
            initChatbot();
        });

        function initChatbot(){
            if($(".chatbot__window").length < 1){
                appendChatbot();
                initListeners();
            }
        }

        function appendChatbot(){
            $("body").append(chatbotWindow);
            $("body").append(chatbotButton);
            appendChatbotMessage("Hey, how can I help you? 👋😊", 1);
        }

        function appendChatbotMessage(message, accuracy){
            $(".chatbot__window--messages").append(chatbotMessage.replace("{MESSAGE}",message));

            if(accuracy <= 0.5){
                // If the accuracy is less than 0.5
                $(".chatbot__window--messages").append(chatbotCaution);
            }

            $(".chatbot--writing").remove();
            processingMessage = false;

            $(".chatbot__window--messages").animate({ scrollTop: $('.chatbot__window--messages').prop("scrollHeight")}, 400);
        }

        function appendUserMessage(message) {
            $(".chatbot__window--messages").append(userMessage.replace("{MESSAGE}",message));
            $(".chatbot__window--messages").animate({ scrollTop: $('.chatbot__window--messages').prop("scrollHeight")}, 400);
        }

        function appendChatbotIsWriting() {
            $(".chatbot--writing").remove();
            $(".chatbot__window--messages").append(chatbotTypingElement);
        }
        
        function initListeners() {

            $(".chatbot__toggle").on('click', function(){
                $toggle_value = !toggled ? "100%" : "0px";
                $opacity_value = !toggled ? "1" : "0";
                $button_value = !toggled ? "rotate(45deg)" : "";
                toggled = !toggled;
                $(".chatbot__window").css("height", $toggle_value);
                $(".chatbot__window").css("opacity", $opacity_value);
                $(this).css("transform", $button_value)
            });

            // Check when enter is pressed to send

            $(".chatbot__window--message").on('keyup', function(e) {
                if (e.keyCode == 13) {
                  processMessage();
                }
              });
              $(".chatbot__window--sendmessage").on('click', function(e) {
                  processMessage();
              });
            
        }

        function processMessage(){
            if($(".chatbot__window--message").val().length > 0 && !processingMessage){
                processingMessage = true;
                let message = $(".chatbot__window--message").val();
                appendUserMessage(message);
                $(".chatbot__window--message").val("");
                appendChatbotIsWriting();




                $.ajax({
                    url: "/api/open_ai/converse",
                    type: "GET",
                    data: {
                      session_id: uuid,
                      message: message,
                      category: chatbotCategoy,
                    },
                    success: function(response) {
                      appendChatbotMessage(response.message.message, response.message.accuracy);
                    },
                    error: function(xhr) {
                      appendChatbotMessage("There was an error sending your message :(");
                    }
                  });                  
            }
        }





      }
    };
  })(jQuery);