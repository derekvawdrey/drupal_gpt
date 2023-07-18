# DrupalGPT
### Making your Drupal website into a chatbot.
--- 
DrupalGPT is a module that enables you to add a chatbot onto any page you desire.

DrupalGPT will eventually be allowed to have category specific settings to give optimized feedback to the user. In the future you will be able to provide it with documents specific to that category you choose.

For example, lets say you run an education website and have a department called Elementary Education. Through the Admin UI you will be able to upload documents, pdfs, or plain text that will then be served to the chatbot when answering a question related to the information provided.

DrupalGPT is the ultimate solution for making your website more engaging, conversational, and user-friendly. With DrupalGPT, you can turn your website into a chatbot that speaks your brand’s voice and meets your customers’ needs.

### How do you setup?

1. Get access to the openAI API
2. Create a free pinecone database
3. Upload information to the pinecone database through OpenAI Embeddings
4. Create a folder called "drupal_gpt" and put the files in this repo there
5. Enable the Module
6. Insert configuration variables into the "Extend" portion of the admin panel
7. Specify where you want DrupalGPT to appear.
8. Chat with DrupalGPT


### Future todo 
- [ ] Enable a rate limit per conversation/per hour
- [ ] Optimize the accuracy meter
- [ ] Allow users to upload information per category
- [ ] Allow the changing of the chatbot name
- [ ] Allow theme customization for the chatbot
- [ ] Optimze the user input so that more accurate information can be pulled based on what the user is talking about. This will pull previous context from the previous questions into the users query.
