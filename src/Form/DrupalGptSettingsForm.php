<?php

namespace Drupal\drupal_gpt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DrupalGPTSettingsForm extends ConfigFormBase {

  // Implement getFormId() to define a unique form ID.
  public function getFormId() {
    return 'drupal_gpt_settings_form';
  }

  // Implement getEditableConfigNames() to specify the configuration names to edit.
  protected function getEditableConfigNames() {
    return ['drupal_gpt.settings'];
  }

  // Implement buildForm() to create the form elements.
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('drupal_gpt.settings');

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
        '#options' => [
          'category1' => $this->t('Category 1'),
          'category2' => $this->t('Category 2'),
          'category3' => $this->t('Category 3'),
        ],
        '#default_value' => $url['category'],
      ];
    }









    
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

    // Add more form elements or tabs as needed.

    return parent::buildForm($form, $form_state);
  }







    // Add URL form submission handler
    public function addUrl(array &$form, FormStateInterface $form_state) {
      $form_state->set('num_urls', $form_state->get('num_urls') + 1);
      $form_state->setRebuild();
    }

    // Add URL form AJAX callback
    public function addUrlAjaxCallback(array &$form, FormStateInterface $form_state) {
      return $form['display_settings']['urls'];
    }

    // Remove URL form submission handler
    public function removeUrl(array &$form, FormStateInterface $form_state) {
      if( $form_state->get('num_urls') - 1 >= 0){
        $form_state->set('num_urls', $form_state->get('num_urls') - 1);
      }
      $form_state->setRebuild();
    }

    // Remove URL form AJAX callback
    public function removeUrlAjaxCallback(array &$form, FormStateInterface $form_state) {
      return $form['display_settings']['urls'];
    }








  // Implement submitForm() to save the submitted values.
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('drupal_gpt.settings');
    $config->set('openai_key', $form_state->getValue('openai_key'));
    $config->set('openai_model', $form_state->getValue('openai_model'));
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

    $config->save();


    parent::submitForm($form, $form_state);
  }
}
