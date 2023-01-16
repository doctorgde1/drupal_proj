<?php

namespace Drupal\currency_exchange_rates\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CurrencyBlockConfig provides config form.
 *
 * By path: /admin/config/currency-block-form.
 *
 * Form has field to input api url.
 *
 * And checkbox to chose currencies for api query.
 */
class CurrencySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'currency_exchange_rates_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'currency_exchange_rates.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('currency_exchange_rates.settings');
    $form['openexchangerates_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Openexchangerates API url'),
      '#description' => $this->t('Enter your openexchangerates API url.') . ' ' . '<a href="https://docs.openexchangerates.org">' . $this->t('Site Link') . '</a>',
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('openexchangerates_api_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config('currency_exchange_rates.settings')
      ->set('openexchangerates_api_url', $form_state->getValue('openexchangerates_api_key'))
      ->save();
  }

}
