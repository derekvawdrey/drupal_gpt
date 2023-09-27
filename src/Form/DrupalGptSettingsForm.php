<?php

namespace Drupal\drupal_gpt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\drupal_gpt\Controller\EmbeddedEntryController;

class DrupalGPTSettingsForm extends ConfigFormBase
{

  // Implement getFormId() to define a unique form ID.
  public function getFormId()
  {
    return 'drupal_gpt_settings_form';
  }

  // Implement getEditableConfigNames() to specify the configuration names to edit.
  protected function getEditableConfigNames()
  {
    return ['drupal_gpt.settings'];
  }

  private function chatBotSettings(array &$form, FormStateInterface &$form_state, $config)
  {
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
    foreach ($categories as $category) {
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
  private function categorySettings(array &$form, FormStateInterface &$form_state, $config)
  {
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
    if ($numCategories == 0) $numCategories = 1;
    $form_state->set('num_categories', $numCategories);
    $form_state->set('categories', $categories);

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
      if (in_array($i, $skipCategories)) continue;
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
          'class' => ['remove-category', 'col-12, col-md-3'],
          'data-category-index' => $i, // Store the category index as data attribute
        ],
      ];
    }
  }
  private function openAISettings(array &$form, FormStateInterface &$form_state, $config)
  {
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
    $form['openai_settings']['enabled_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Filter'),
      '#default_value' => $config->get('enabled_filter'),
      '#description' => $this->t('Enable a filter to stop AI from sending inappropriate, offensive, or otherwise sexual content.'),
    ];
  }
  private function pineconeSettings(array &$form, FormStateInterface &$form_state, $config)
  {
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
  private function otherSettings(array &$form, FormStateInterface &$form_state, $config)
  {
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
  private function contextSettings(array &$form, FormStateInterface &$form_state, $config)
  {
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

    $form['chatbot_context']['Category'] = [
      '#type' => 'markup',
      '#markup' => '<h1>Category Contexts</h1>',
    ];

    $defaultCategory = '';
    if (isset($config->get('chatbot_categories')[0])) {
      $defaultCategory = $config->get('chatbot_categories')[0];
    }
    $toggle_state = $form_state->get('toggle_button_clicked') ?? false;
    $selectedCategory = $form_state->get("selected_category") ?? '';
    $form_state->set("selected_category", $selectedCategory);

    $form['chatbot_context']['button_container'] = [
      '#type' => 'container',
      '#prefix' => '<div id="button-container">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    foreach ($config->get('chatbot_categories') as $category) {
      $form['chatbot_context']['button_container'][$category] = [
        '#type' => 'submit',
        '#value' => $this->t($category),
        '#submit' => ['::categoryChange'],
        '#ajax' => [
          'callback' => '::categoryChangeAjax',
          'wrapper' => 'context-container',
        ],
        '#name' => $category,
        '#attributes' => [
          'class' => ['disable-on-click'], // Add the CSS class to the button.
        ],
      ];
    }
    
    $form['chatbot_context']['button_container']['toggle_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Change Category'),
      '#submit' => ['::toggleButtonSubmit'],
    ];
    $form['chatbot_context']['button_container']['add_context'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Context'),
      '#submit' => ['::addContext'],
      '#ajax' => [
        'callback' => '::addContextAjaxCallback',
        'wrapper' => 'context-container',
      ],
      '#attributes' => [
        'style' => 'background-color: #92D293; color: white;', // Adjust the colors as desired
      ],

    ];


    $form['chatbot_context']['context_container'] = [
      '#type' => 'container',
      '#prefix' => '<div id="context-container"><h3>Category: ' . $selectedCategory . '</h3>',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];


    /**
     * 
     * Dynamic elements
     * 
     */
    $context_from_config = $config->get('chatbot_context') ?? [];

    $context_category_entires = 0;
    if (isset($context_from_config[$selectedCategory])) {
      $context_category_entires = count($context_from_config[$selectedCategory]);
    }

    $num_context_entries = $form_state->get('num_context_entries') ?? $context_category_entires;
    $context_updating = $form_state->get('context_updating') ?? false;
    $removeContexts = $form_state->get('remove_contexts') ?? [];
    $form_state->set('num_context_entries', $num_context_entries);
    $form_state->set('context_updating', $context_updating);
    if (!$context_updating) {
      // Add context textboxes based on the number of entries stored in the form state.
      for ($i = 0; $i < $num_context_entries; $i++) {
        $text = "";
        if (isset($context_from_config[$selectedCategory]) && isset($context_from_config[$selectedCategory][$i])) {
          $text = $context_from_config[$selectedCategory][$i]["context"];
        }
        if (in_array($i, $removeContexts)) continue;
        // Create an accordion fieldset for each context entry.
        $this->buildContextInputs($form, $form_state, $i, $text);
      }
    } else {
      $form['chatbot_context']['context_container']['loading_sign'] = [
        '#type' => 'markup',
        '#markup' => '<div class="loading-sign">Loading...</div>',
        '#prefix' => '<div id="loading-sign-wrapper">',
        '#suffix' => '</div>',
        '#states' => [
          'visible' => [
            ':input[name="loading_variable"]' => ['value' => TRUE],
          ],
        ],
      ];
    }

    $form['chatbot_context']['update_context'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update all context'),
      '#submit' => ['::updateContext'],
      '#ajax' => [
        'callback' => '::updateContextAjaxCallback',
        'wrapper' => 'context-container',
      ],
    ];

    return $form;
  }


  // Implement buildForm() to create the form elements.
  public function buildForm(array $form, FormStateInterface $form_state)
  {
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
  public function addUrl(array &$form, FormStateInterface $form_state)
  {
    $form_state->set('num_urls', $form_state->get('num_urls') + 1);
    $form_state->setRebuild();
  }
  public function addUrlAjaxCallback(array &$form, FormStateInterface $form_state)
  {
    return $form['display_settings']['urls'];
  }
  public function removeUrl(array &$form, FormStateInterface $form_state)
  {
    if ($form_state->get('num_urls') - 1 >= 0) {
      $form_state->set('num_urls', $form_state->get('num_urls') - 1);
    }
    $form_state->setRebuild();
  }
  public function removeUrlAjaxCallback(array &$form, FormStateInterface $form_state)
  {
    return $form['display_settings']['urls'];
  }

  /**
   * 
   * These functions are for adding/removing categories
   * 
   */
  public function addCategory(array &$form, FormStateInterface $form_state)
  {
    $form_state->set('num_categories', $form_state->get('num_categories') + 1);
    $form_state->setRebuild();
  }
  public function addCategoryAjaxCallback(array &$form, FormStateInterface $form_state)
  {
    return $form['chatbot_categories']['categories'];
  }
  public function removeCategoryItem(array &$form, FormStateInterface $form_state)
  {
    if ($form_state->get('num_categories') - 1 >= 0) {
      $triggering_element = $form_state->getTriggeringElement();
      $category_index = (int) str_replace('remove_category_', '', $triggering_element['#name']);
      $categories = $form_state->get('skip_categories') ?? [];
      $categories[] = $category_index;
      $form_state->set("skip_categories", $categories);
      $form_state->setRebuild();
    }
  }

  public function removeCategoryItemAjaxCallback(array &$form, FormStateInterface $form_state)
  {
    return $form['chatbot_categories']['categories'];
  }



  /**
   * 
   * Context functions
   * 
   */
  
  public function categoryChange(array &$form, FormStateInterface $form_state)
  {
    $triggering_element = $form_state->getTriggeringElement();
    $selected_category = $triggering_element['#name'];
    $form_state->set("selected_category",$selected_category);
    $form_state->set('num_context_entries', NULL);
    $form_state->set('remove_contexts', NULL);

    $form_state->setRebuild();
  }

  public function categoryChangeAjax(array &$form, FormStateInterface $form_state)
  {
    return $form['chatbot_context']["context_container"];
  }

  public function addContext(array &$form, FormStateInterface $form_state)
  {
    if(!empty($form_state->get('selected_category'))){
      $form_state->set('num_context_entries', $form_state->get('num_context_entries') + 1);
      $form_state->setRebuild();
    }
  }
  public function addContextAjaxCallback(array &$form, FormStateInterface $form_state)
  {
    return $form['chatbot_context']['context_container'];
  }

  public function generateDefaultValue(array &$element, FormStateInterface $form_state, $text)
  {
    // Use the $customIntArgument to set the default value based on the integer.
    // For example, you can use it to increment the default value by the integer.
    return $text;
  }

  public function updateContext(array &$form, FormStateInterface $form_state)
  {
    $config = $this->config('drupal_gpt.settings');
    //$form_state->set('context_updating', !$form_state->get('context_updating'));



    $skipContexts = $form_state->get('remove_contexts') ?? [];
    $numContexts = $form_state->get('num_context_entries');
    $currentCategory = $form_state->get("selected_category");

    $contexts = $config->get('chatbot_context') ?? [];
    $old_contexts = $contexts[$currentCategory];
    $contexts[$currentCategory] = [];
    $pinecone_controller = new EmbeddedEntryController();
    for ($i = 0; $i < $numContexts; $i++) {
      if (isset($skipContexts[$i])) {
        // TODO: remove the contexts from the pinecone database
        // TODO: Remove from the textarea
        if(isset($old_contexts[$i])){

        }
        continue;
      };

      if ($form_state->getValue('context_container')[$i]["context_textbox"] == "") continue;
      $context = $form_state->getValue('context_container')[$i]["context_textbox"];
      

      // Processing the context for inserting into pinecone:
      $skip = false;
      foreach($old_contexts as $old_context){
        if($context == $old_context["context"]){
          $skip = true;
          $contexts[$currentCategory][] = $old_context;
        }
      }

      if(!$skip){
        // Do pinecone update script
        $response = $pinecone_controller->embedIntoPinecone($context,$currentCategory);
        $contexts[$currentCategory][] = [
          "context" => $context,
          "uuids" => $response["vector_ids"],
        ];
      }

    }
    $config->set('chatbot_context', $contexts);
    $config->save();

    $form_state->setRebuild();
  }
  public function updateContextAjaxCallback(array &$form, FormStateInterface $form_state)
  {
    return $form['chatbot_context']['context_container'];
  }

  // Remove context
  public function removeContext(array &$form, FormStateInterface $form_state)
  {
    $triggering_element = $form_state->getTriggeringElement();
    $context_index = (int) str_replace('remove_context_', '', $triggering_element['#name']);
    $contexts = $form_state->get('remove_contexts') ?? [];
    $contexts[] = $context_index;
    $form_state->set("remove_contexts", $contexts);
    $form_state->setRebuild();
  }
  public function removeContextAjaxCallback(array &$form, FormStateInterface $form_state)
  {
    return $form['chatbot_context']['context_container'];
  }

  public function toggleButtonSubmit(array &$form, FormStateInterface $form_state) {
    // Toggle the value of the flag.
    $form_state->set("selected_category", NULL);
    $form_state->set('num_context_entries', NULL);
    $form_state->set('remove_contexts', NULL);
    $form_state->set('toggle_button_clicked', true);
    // Rebuild the form to reflect the changed state of the toggle button.
    $form_state->setRebuild();
  }

  public function buildContextInputs(array &$form, FormStateInterface $form_state, $i, $text){
        // Create an accordion fieldset for each context entry.
        $form['chatbot_context']['context_container'][$i] = [
          '#type' => 'fieldset',
          '#title' => 'Context ' . ($i + 1),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE, // You can set this to FALSE if you want the fieldset to be initially expanded.
        ];
        // Add the context textbox within the accordion fieldset.
        $form['chatbot_context']['context_container'][$i]['context_textbox'] = [
          '#type' => 'textarea',
          '#default_value' => $text,
          '#title' => $this->t('Context'),
          '#prefix' => '<div class="container-inline">',
          '#suffix' => '</div>',
          '#size' => 60,
          '#rows' => 5,
          '#required' => TRUE,
        ];
        $form['chatbot_context']['context_container'][$i]['actions']['remove'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#submit' => ['::removeContext'],
          '#ajax' => [
            'callback' => '::removeContextAjaxCallback',
            'wrapper' => 'context-container',
          ],
          '#name' => 'remove_context_' . $i,
          '#prefix' => '<div class="container-inline">',
          '#suffix' => '</div>',
          '#attributes' => [
            'class' => ['remove-context', 'col-12, col-md-3'],
            'data-category-index' => $i, // Store the category index as data attribute
          ],
        ];
  }


  // Implement submitForm() to save the submitted values.
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $config = $this->config('drupal_gpt.settings');
    $config->set('openai_key', $form_state->getValue('openai_key'));
    $config->set('openai_model', $form_state->getValue('openai_model'));
    $config->set('enabled_accuracy_meter', $form_state->getValue('enabled_accuracy_meter'));
    $config->set('enabled_filter', $form_state->getValue('enabled_filter'));
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
      if (isset($skipCategories[$i])) continue;
      if ($form_state->getValue('categories')[$i]["category"] == "") continue;
      $category = $form_state->getValue('categories')[$i]["category"];
      $categories[] = $category;
    }

    $config->set('chatbot_categories', $categories);

    $config->save();


    parent::submitForm($form, $form_state);
  }
}
