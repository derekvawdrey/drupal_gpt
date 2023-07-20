<?php

namespace Drupal\drupal_gpt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\HtmlCommand;

class DrupalGPTSettingsForm extends ConfigFormBase {

  // Implement getFormId() to define a unique form ID.
  public function getFormId() {
    return 'drupal_gpt_settings_form';
  }

  // Implement getEditableConfigNames() to specify the configuration names to edit.
  protected function getEditableConfigNames() {
    return ['drupal_gpt.settings'];
  }

  private function chatBotSettings(array &$form, FormStateInterface &$form_state, $config){
    // Add the vertical tabs element.
    $form['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
      '#attached' => ['library' => ['system/drupal.vertical-tabs']],
    ];

    // Tab 4: 
    // This will load all the chatbot contexts for each department
    $form['display_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Chatbot Display Settings'),
      '#group' => 'vertical_tabs',
      '#attributes' => [
        'id' => 'edit-urls',
      ],
    ];



    // URL field
    $urls = $config->get('urls') ?? [];
    $form['display_settings']['urls'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('URLs'),
      '#prefix' => '<div id="urls-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    $numUrls = $form_state->get('num_urls') ?? count($urls);

    $form_state->set('num_urls', $numUrls);

    $form['display_settings']['urls']['actions']['add_url'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add URL'),
      '#submit' => ['::addUrl'],
      '#ajax' => [
        'callback' => '::addUrlAjaxCallback',
        'wrapper' => 'urls-wrapper',
      ],
    ];

    if (!empty($urls)) {
      $form['display_settings']['urls']['actions']['remove_url'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove URL'),
        '#submit' => ['::removeUrl'],
        '#ajax' => [
          'callback' => '::removeUrlAjaxCallback',
          'wrapper' => 'urls-wrapper',
        ],
      ];
    }
    $categories = $config->get('chatbot_categories');
    $category_map = [];
    foreach($categories as $category){
      $category_map[$category] = $category;
    }



    for ($i = 0; $i < $numUrls; $i++) {
      $url = $urls[$i] ?? ['url' => '', 'category' => 'category1'];
      $form['display_settings']['urls'][$i]['url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('URL'),
        '#default_value' => $url['url'],
      ];
      $form['display_settings']['urls'][$i]['category'] = [
        '#type' => 'select',
        '#title' => $this->t('Category'),
        '#options' => $category_map,
        '#default_value' => $url['category'],
      ];
    }
  }
  private function categorySettings(array &$form, FormStateInterface &$form_state, $config){
    // Add the vertical tabs element.
    $form['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
      '#attached' => ['library' => ['system/drupal.vertical-tabs']],
    ];

    $form['chatbot_categories'] = [
      '#type' => 'details',
      '#title' => $this->t('Chatbot Categories'),
      '#group' => 'vertical_tabs',
      '#attributes' => [
        'id' => 'edit-categories',
      ],
    ];



    // Category field
    $categories = $config->get('chatbot_categories') ?? [];
    $numCategories = $form_state->get('num_categories') ?? count($categories);
    $skipCategories = $form_state->get('skip_categories') ?? [];
    if($numCategories == 0) $numCategories = 1;
    $form_state->set('num_categories', $numCategories);
    $form_state->set('categories',$categories);

    $form['chatbot_categories']['categories'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Categories'),
      '#prefix' => '<div id="categories-wrapper">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    $form['chatbot_categories']['categories']['actions']['add_category'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Category'),
      '#submit' => ['::addCategory'],
      '#ajax' => [
        'callback' => '::addCategoryAjaxCallback',
        'wrapper' => 'categories-wrapper',
      ],
    ];

    for ($i = 0; $i < $numCategories; $i++) {
      if(in_array($i, $skipCategories)) continue;
      $category = $categories[$i] ?? "Category1";
      $form['chatbot_categories']['categories'][$i] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['row'],
        ],
      ];
      $form['chatbot_categories']['categories'][$i]["category"] = [
        '#type' => 'textfield',
        '#title' => $this->t('Category'),
        '#default_value' => $category,
        '#prefix' => '<div class="container-inline">',
        '#suffix' => '</div>',
        '#attributes' => [
          'class' => ['limited-input'],
        ],
      ];
      $form['chatbot_categories']['categories'][$i]['actions']['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => ['::removeCategoryItem'],
        '#ajax' => [
          'callback' => '::removeCategoryItemAjaxCallback',
          'wrapper' => 'categories-wrapper',
        ],
        '#name' => 'remove_category_' . $i,
        '#prefix' => '<div class="container-inline">',
        '#suffix' => '</div>',
        '#attributes' => [
          'class' => ['remove-category','col-12, col-md-3'],
          'data-category-index' => $i, // Store the category index as data attribute
        ],
      ];
    }
  }
  private function openAISettings(array &$form, FormStateInterface &$form_state, $config){
    // Tab 1: OpenAI Settings.
    $form['openai_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('OpenAI Settings'),
      '#group' => 'vertical_tabs',
    ];

    $form['openai_settings']['openai_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenAI API Key'),
      '#default_value' => $config->get('openai_key'),
      '#description' => $this->t('Enter your API key here.'),
    ];

    $form['openai_settings']['openai_model'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenAI Model for Chatbot'),
      '#default_value' => $config->get('openai_model'),
      '#description' => $this->t('Enter the model you want to use.'),
    ];
    $form['openai_settings']['openai_model_accuracy'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenAI Model for determining if a message contains accurate information'),
      '#default_value' => $config->get('openai_model_accuracy'),
      '#description' => $this->t('This allows you to have GPT4.0 or higher to give better accuracy information'),
    ];

    $form['openai_settings']['accuracy_meter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Accuracy Meter'),
      '#default_value' => $config->get('enabled_accuracy_meter'),
      '#description' => $this->t('Disabling this feature will allow you to save money, but will not allow the AI to determine if a message is accurate'),
    ];
  }
  private function pineconeSettings(array &$form, FormStateInterface &$form_state, $config){
    // Tab 2: Pinecone Settings.
    $form['pinecone_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Pinecone Settings'),
      '#group' => 'vertical_tabs',
    ];

    $form['pinecone_settings']['pinecone_environment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pinecone environment'),
      '#default_value' => $config->get('pinecone_environment'),
      '#description' => $this->t('What is the environment for your index?'),
    ];

    $form['pinecone_settings']['pinecone_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pinecone API Key'),
      '#default_value' => $config->get('pinecone_key'),
      '#description' => $this->t('Enter your API key here.'),
    ];

    $form['pinecone_settings']['pinecone_index_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pinecone Index URL'),
      '#default_value' => $config->get('pinecone_index_url'),
      '#description' => $this->t('Remove the / at the end, and add https:// at the beginning'),
    ];
  }
  private function otherSettings(array &$form, FormStateInterface &$form_state, $config){
    // Tab 3: Other Settings.
    $form['other_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Other Settings'),
      '#group' => 'vertical_tabs',
    ];

    $form['other_settings']['max_requests_per_session'] = [
      '#type' => 'number',
      '#title' => $this->t('Max requests per session'),
      '#default_value' => $config->get('max_requests_per_session'),
      '#description' => $this->t('How many messages can the user send before killing the session?'),
    ];

    $form['other_settings']['max_requests_per_minute'] = [
      '#type' => 'number',
      '#title' => $this->t('Max requests per minute'),
      '#default_value' => $config->get('max_requests_per_minute'),
      '#description' => $this->t('How many messages can the user send per minute?'),
    ];

    $form['other_settings']['cleanup_after_minutes'] = [
      '#type' => 'number',
      '#title' => $this->t('Time to inactive session'),
      '#default_value' => $config->get('cleanup_after_minutes'),
      '#description' => $this->t('A session will be declared inactive if a user hasn\'t sent a message in this many minutes.'),
    ];
  }
  private function contextSettings(array &$form, FormStateInterface &$form_state, $config) {
    /**
     * 
     * Static elements
     * 
     */
    $form['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
      '#attached' => ['library' => ['system/drupal.vertical-tabs']],
    ];

    $form['chatbot_context'] = [
      '#type' => 'details',
      '#title' => $this->t('Chatbot Context'),
      '#group' => 'vertical_tabs',
    ];
    
    $selectedCategory = $form_state->get("selected_category") ?? '';
    $form['chatbot_context']['selected_context_category'] = [
      '#type' => 'select',
      '#title' => 'Category',
      '#options' => $config->get('chatbot_categories') ?? [],
      '#default_value' => $selectedCategory,
      '#required' => TRUE,
      '#group' => 'vertical_tabs',
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => '::categoryChangeAjax',
        'event' => 'change',
        'wrapper' => 'vertical_tabs',
      ],
    ];
    
    $form['chatbot_context']['add_context'] = [
      '#type' => 'button',
      '#value' => $this->t('Add Context'),
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
      '#submit' => ['::addContext'],
      '#ajax' => [
        'callback' => '::addContextAjax', // Add a new AJAX callback to handle the button click.
        'event' => 'click',
        'wrapper' => 'context-container', // This ID should match the ID of the container that holds the context textboxes.
      ],
    ];


    $form['chatbot_context']['context_container'] = [
      '#type' => 'container',
      '#prefix' => '<div id="context-container">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];
    
    
    /**
     * 
     * Dynamic elements
     * 
     */
    $context_entries = $form_state->get('context_entries') ?? [];
    $num_context_entries = $form_state->get('num_context_entries') ?? 1;

    // Add context textboxes based on the number of entries stored in the form state.
    for ($i = 0; $i < $num_context_entries; $i++) {
      // You can adjust the settings of the textboxes as needed.
      $form['chatbot_context']['context_container'][$i]['context_textbox'] = [
        '#type' => 'textfield',
        '#title' => 'Context ' . ($i + 1),
        '#size' => 60,
        '#maxlength' => 1750,
        '#required' => TRUE,
      ];
    }

  
    return $form;
  }


  // Implement buildForm() to create the form elements.
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('drupal_gpt.settings');

    $this->chatBotSettings($form, $form_state, $config);
    $this->categorySettings($form, $form_state, $config);
    $this->contextSettings($form, $form_state, $config);
    $this->openAISettings($form, $form_state, $config);
    $this->pineconeSettings($form, $form_state, $config);
    $this->otherSettings($form, $form_state, $config);

    // Add more form elements or tabs as needed.

    return parent::buildForm($form, $form_state);
  }





    /**
     * 
     * These functions are for adding/removing urls
     * 
     */
    public function addUrl(array &$form, FormStateInterface $form_state) {
      $form_state->set('num_urls', $form_state->get('num_urls') + 1);
      $form_state->setRebuild();
    }
    public function addUrlAjaxCallback(array &$form, FormStateInterface $form_state) {
      return $form['display_settings']['urls'];
    }
    public function removeUrl(array &$form, FormStateInterface $form_state) {
      if( $form_state->get('num_urls') - 1 >= 0){
        $form_state->set('num_urls', $form_state->get('num_urls') - 1);
      }
      $form_state->setRebuild();
    }
    public function removeUrlAjaxCallback(array &$form, FormStateInterface $form_state) {
      return $form['display_settings']['urls'];
    }

    /**
     * 
     * These functions are for adding/removing categories
     * 
     */
    public function addCategory(array &$form, FormStateInterface $form_state) {
      $form_state->set('num_categories', $form_state->get('num_categories') + 1);
      $form_state->setRebuild();
    }
    public function addCategoryAjaxCallback(array &$form, FormStateInterface $form_state) {
      return $form['chatbot_categories']['categories'];
    }
    public function removeCategoryItem(array &$form, FormStateInterface $form_state) {
      if ($form_state->get('num_categories') - 1 >= 0) {
          $triggering_element = $form_state->getTriggeringElement();
          $category_index = (int) str_replace('remove_category_', '', $triggering_element['#name']);
          $categories = $form_state->get('skip_categories') ?? [];
          $categories[] = $category_index;
          $form_state->set("skip_categories", $categories);
          $form_state->setRebuild();
      }
  }

    public function removeCategoryItemAjaxCallback(array &$form, FormStateInterface $form_state) {
      return $form['chatbot_categories']['categories'];
    }

  
  /**
   * 
   * These functions help with adding context to the chatbot
   * 
   */
  public function categoryChangeAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
  
    // Get the selected category.
    $selectedCategory = $form_state->getValue('selected_context_category');
    $form_state->set("selected_category",$selectedCategory);
    // Perform any action you want with the selected category.
  
    // If you want to update the form elements dynamically, you can use commands.
    $response->addCommand(new HtmlCommand('#vertical_tabs', $form['vertical_tabs']));
  
    return $response;
  }


  /**
   * 
   * Context functions
   * 
   */


  public function addContext(array &$form, FormStateInterface $form_state){
    // Get the number of existing context textboxes.
    $num_context_entries = $form_state->get('num_context_entries') ?? 0;

    // Increment the number of context entries and store it in the form state.
    $num_context_entries++;
    $form_state->set('num_context_entries', $num_context_entries);

    // Rebuild the form to include the new context textbox.
    $form_state->setRebuild();
  }

  /**
   * AJAX callback to add a new context textbox.
   */
  public function addContextAjax(array &$form, FormStateInterface $form_state) {
    // Return the updated form or the portion you want to update.
    return $form['chatbot_context']['context_container'];
  }




  // Implement submitForm() to save the submitted values.
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('drupal_gpt.settings');
    $config->set('openai_key', $form_state->getValue('openai_key'));
    $config->set('openai_model', $form_state->getValue('openai_model'));
    $config->set('enabled_accuracy_meter', $form_state->getValue('enabled_accuracy_meter'));
    $config->set('openai_model_accuracy', $form_state->getValue('openai_model_accuracy'));
    $config->set('pinecone_environment', $form_state->getValue('pinecone_environment'));
    $config->set('pinecone_key', $form_state->getValue('pinecone_key'));
    $config->set('pinecone_index_url', $form_state->getValue('pinecone_index_url'));
    $config->set('max_requests_per_session', $form_state->getValue('max_requests_per_session'));
    $config->set('max_requests_per_minute', $form_state->getValue('max_requests_per_minute'));
    $config->set('cleanup_after_minutes', $form_state->getValue('cleanup_after_minutes'));

    $urls = [];
    $numUrls = $form_state->get('num_urls');
    for ($i = 0; $i < $numUrls; $i++) {
      $url = $form_state->getValue('urls')[$i]["url"];
      $category = $form_state->getValue('urls')[$i]["category"];
      $urls[$i] = ['url' => $url, 'category' => $category];
    }

    $config->set('urls', $urls);
    $config->set('num_urls', $numUrls);

    $categories = [];
    $skipCategories = $form_state->get('skip_categories') ?? [];
    $numCategories = $form_state->get('num_categories');
    for ($i = 0; $i < $numCategories; $i++) {
      if(isset($skipCategories[$i])) continue;
      if($form_state->getValue('categories')[$i]["category"] == "") continue;
      $category = $form_state->getValue('categories')[$i]["category"];
      $categories[] = $category;
    }
    
    $config->set('chatbot_categories', $categories);

    $config->save();


    parent::submitForm($form, $form_state);
  }
}
