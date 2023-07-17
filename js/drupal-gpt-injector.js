(function ($) {
    Drupal.behaviors.drupalGPTCustomBehavior = {
      attach: function (context, settings) {
        // Access the customVariable passed from PHP.
        var chatbotCategoy = settings.drupal_gpt.category;
        var toggled = false;
        var chatbotTypingElement = `
        <div class="typing">
            <div class="bubble">
                <div class="ellipsis one"></div>
                <div class="ellipsis two"></div>
                <div class="ellipsis three"></div>
            </div>
        </div>`;
        

        var chatbotMessage = `
        <div class='incoming'>
            <div class="bubble">{MESSAGE}</div>
        </div>
        `

        var userMessage = `
        <div class='outgoing'>
        <div class="bubble">{MESSAGE}</div>
        </div>`;

        var chatbotWindow = `
        <div class='chatbot__window'>
            <div class='chatbot__window--header'></div>
            <div class='chatbot__window--messages'>
                
            </div>
            <div class='chatbot__window--footer'>

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
            appendChatbot();
            initListeners();
        }

        function appendChatbot(){
            $("body").append(chatbotWindow);
            $("body").append(chatbotButton);
            appendMessage();
            appendMessage();
            appendMessage();
        }

        function appendMessage(){
            $(".chatbot__window--messages").append(chatbotMessage.replace("{MESSAGE}","Hey, how can I help you? 👋😊"));
            $(".chatbot__window--messages").append(userMessage.replace("{MESSAGE}","i want donuts"));
            $(".chatbot__window--messages").append(chatbotTypingElement);
        }
        
        function initListeners(){

            $(".chatbot__toggle").on('click', function(){
                $toggle_value = toggled ? "450px" : "0px";
                $opacity_value = toggled ? "1" : "0";
                $button_value = toggled ? "-" : "+";
                toggled = !toggled;
                $(".chatbot__window").css("height", $toggle_value);
                $(".chatbot__window").css("opacity", $opacity_value);
                $(this).find("span").text($button_value)
            });
            
        }





      }
    };
  })(jQuery);