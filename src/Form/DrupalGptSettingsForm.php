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

    $form['openai_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenAI API Key'),
      '#default_value' => $config->get('openai_key'),
      '#description' => $this->t('Enter your API key here.'),
    ];

    $form['openai_model'] = [
        '#type' => 'textfield',
        '#title' => $this->t('OpenAI Model for Chatbot'),
        '#default_value' => $config->get('openai_model'),
        '#description' => $this->t('Enter the model you want to use.'),
      ];

    // Add more form elements as needed.

    return parent::buildForm($form, $form_state);
  }

  // Implement submitForm() to save the submitted values.
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('drupal_gpt.settings');
    $config->set('openai_key', $form_state->getValue('openai_key'));
    $config->set('openai_model', $form_state->getValue('openai_model'));
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
