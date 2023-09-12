(function ($) {
    Drupal.behaviors.drupalGPTCustomBehavior = {
        attach: function (context, settings) {

            function generate_uuidv4() {
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,
                    function (c) {
                        var uuid = Math.random() * 16 | 0, v = c == 'x' ? uuid : (uuid & 0x3 | 0x8);
                        return uuid.toString(16);
                    });
            }


            // Access the customVariable passed from PHP.
            var chatbotCategoy = settings.drupal_gpt.category;
            var toggled = false;
            const uuid = generate_uuidv4();
            var processingMessage = false;

            var chatbotWindow = `
        <div class='chatbot__window'>
            <div class='chatbot__window--header'>
                <h4>Chat Window</h4>
            </div>
            <div class='chatbot__window--content'>
                <div class='chatbot__avatar'>
                </div>
                <div class='chatbot__message' id="chatbot__message">
                    Hey! What can I help you with today?
                </div>
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

            $(document).ready(function () {
                initChatbot();
            });

            function initChatbot() {
                if ($(".chatbot__window").length < 1) {
                    appendChatbot();
                    initListeners();
                }
            }

            function appendChatbot() {
                $("body").append(chatbotWindow);
                $("body").append(chatbotButton);
            }

            function chatbotMessage(message, accuracy) {
                // Append the message
                $(".chatbot__message").text(message);
                if (accuracy <= 0.5) {
                    // If the accuracy is less than 0.5

                }
                // Remove the is writing
                processingMessage = false;
            }

            function backspaceText() {
                var $chatMessage = $(".chatbot__message");
                var divText = $chatMessage.text();

                if (divText.length > 0) {
                    var interval = 1000 / divText.length; // 1 second divided by the number of characters
                    var eraseInterval = setInterval(function () {
                        if(processingMessage){
                        var currentText = $chatMessage.text();
                        var newText = currentText.slice(0, -1);
                        $chatMessage.text(newText);
                        }else{
                            clearInterval(eraseInterval);
                        }
                        if (newText.length === 0) {
                            clearInterval(eraseInterval); // Stop the erasing process when the text is empty
                        }
                    }, interval);
                }
            }

            function chatbotIsTyping() {
                backspaceText();
            }

            function initListeners() {

                $(".chatbot__toggle").on('click', function () {
                    $toggle_value = !toggled ? "100%" : "0px";
                    $opacity_value = !toggled ? "1" : "0";
                    $button_value = !toggled ? "rotate(45deg)" : "";
                    toggled = !toggled;
                    $(".chatbot__window").css("height", $toggle_value);
                    $(".chatbot__window").css("opacity", $opacity_value);
                    $(this).css("transform", $button_value)
                });

                // Check when enter is pressed to send

                $(".chatbot__window--message").on('keyup', function (e) {
                    if (e.keyCode == 13) {
                        processMessage();
                    }
                });
                $(".chatbot__window--sendmessage").on('click', function (e) {
                    processMessage();
                });

            }

            function processMessage() {
                if ($(".chatbot__window--message").val().length > 0 && !processingMessage) {
                    processingMessage = true;
                    let message = $(".chatbot__window--message").val();
                    $(".chatbot__window--message").val("");
                    chatbotIsTyping();



                    $.ajax({
                        url: "/api/open_ai/converse",
                        type: "GET",
                        data: {
                            session_id: uuid,
                            message: message,
                            category: chatbotCategoy,
                        },
                        success: function (response) {
                            chatbotMessage(response.message.message, response.message.accuracy);
                        },
                        error: function (xhr) {
                            chatbotMessage("There was an error sending your message :(");
                        }
                    });
                }
            }





        }
    };
})(jQuery);