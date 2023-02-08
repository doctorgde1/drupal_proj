<?php

namespace Drupal\currency_exchange_rates\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;
use Drupal\currency_exchange_rates\Service\CurrencyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Stores CurrencyService object.
   *
   * @var object
   */
  protected $currencyApi;

  /**
   * Stores State object.
   *
   * @var object
   */
  protected $state;

  /**
   * Class constructor.
   *
   * @param \Drupal\currency_exchange_rates\Service\CurrencyService $currency_api
   *   Currency service to work with api.
   * @param Drupal\Core\State\State $state
   *   State object to store validity of form.
   */
  public function __construct(CurrencyService $currency_api, State $state) {
    $this->currencyApi = $currency_api;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): CurrencySettingsForm {
    return new static(
      $container->get('currency_service'),
      $container->get('state')
    );
  }

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

    $form['#prefix'] = '<div id="page_note_ajax_form">';
    $form['#suffix'] = '</div>';

    $form['openexchangerates_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Openexchangerates API url'),
      '#description' => $this->t('Enter your openexchangerates API url. <a href="@api-url"> Site link </a>', [
        '@api-url' => 'https://docs.openexchangerates.org',
      ]),
      '#required' => TRUE,
      '#default_value' => $config->get('openexchangerates_api_url'),
      '#ajax' => [
        'callback' => '::ajaxRebuild',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Verifying url ...'),
        ],
      ],
    ];

    if ($this->state->get('valid')) {
      $form['date_range'] = [
        '#type' => 'select',
        '#title' => $this->t('Date range'),
        '#description' => $this->t('Select in which date range currencies will be displayed from today.'),
        '#options' => [
          '1' => $this->t("One day"),
          '2' => $this->t("Two days"),
          '3' => $this->t("Three days"),
          '4' => $this->t("Four days"),
          '5' => $this->t("Five days"),
          '6' => $this->t("Six days"),
          '7' => $this->t("Seven days"),
        ],
        '#default_value' => $config->get('date_range'),
      ];

      try {
        $available_currencies = $this->currencyApi->getCurrenciesCatalog();

        $checked_checkboxes = $config->get('chosen_currencies') ?? [];
        $form['chosen_currencies'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Currencies to be displayed'),
          '#description' => $this->t('Choose currencies to be displayed in block'),
          '#options' => $available_currencies,
          '#default_value' => $checked_checkboxes,
        ];
      }
      catch (\Exception $e) {
        $form['currencies_catalog_url'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Available currencies catalog url'),
          '#description' => $this->t('Enter new currencies catalog url. Default : <a href="@catalog-url">https://openexchangerates.org/api/currencies.json</a>', [
            '@catalog-url' => 'https://openexchangerates.org/api/currencies.json',
          ]),
          '#required' => TRUE,
          '#default_value' => $config->get('currencies_catalog_url'),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    try {
      $url = $form_state->getValue('openexchangerates_api_url');
      $chosen_currencies = array_values($form_state->getValue('chosen_currencies') ?? []);
      $chosen_currencies = $this->currencyApi->trimArrayZeroes($chosen_currencies);
      $params = ["symbols" => $chosen_currencies];
      $range = (int) $form_state->getValue('date_range');

      $this->currencyApi->getData($url, $range, $params);

      $success_message = $this->t("Successfully connected to API");
      $form_state->addRebuildInfo('success', $success_message);
      $this->state->set('valid', TRUE);
    }
    catch (\Exception $e) {
      $error_message = $this->t('Error code: %error-code. %error-message.', [
        '%error-code' => $e->getCode(),
        '%error-message' => $e->getMessage(),
      ]);
      $form_state->setErrorByName('openexchangerates_api_url', $error_message);
      $form_state->addRebuildInfo('errors', $error_message);
      $this->state->set('valid', FALSE);
    }

    if (array_key_exists("currencies_catalog_url", $form)) {
      try {
        $this->currencyApi->getCurrenciesCatalog($form_state->getValue('currencies_catalog_url'));
      }
      catch (\Exception $e) {
        $error_message = $this->t('Error code: %error-code. %error-message.', [
          '%error-code' => $e->getCode(),
          '%error-message' => $e->getMessage(),
        ]);
        $form_state->setErrorByName('currencies_catalog_url', $error_message);
        $form_state->addRebuildInfo('errors', $error_message);
        $this->state->set('valid', FALSE);
      }
    }
  }

  /**
   * Rebuild form.
   */
  public function ajaxRebuild(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    $form_state->setRebuild(TRUE);

    $messages = $form_state->getRebuildInfo();

    $success_message = $messages['success'];
    $error_message = $messages['errors'];

    if (isset($success_message)) {
      $response->addCommand(new MessageCommand($success_message, NULL));
      $response->addCommand(new ReplaceCommand('#page_note_ajax_form', $form));

    }
    elseif (isset($error_message)) {
      $response->addCommand(new MessageCommand($error_message, NULL, ['type' => 'error']));
      $response->addCommand(new ReplaceCommand('#page_note_ajax_form', $form));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config('currency_exchange_rates.settings')
      ->set('openexchangerates_api_url', $form_state->getValue('openexchangerates_api_url'))
      ->save();

    if (array_key_exists("currencies_catalog_url", $form)) {
      $this->config('currency_exchange_rates.settings')
        ->set('currencies_catalog_url', $form_state->getValue('currencies_catalog_url'))
        ->save();
    }

    $this->config('currency_exchange_rates.settings')
      ->set('chosen_currencies', $form_state->getValue('chosen_currencies'))
      ->save();

    $this->config('currency_exchange_rates.settings')
      ->set('date_range', $form_state->getValue('date_range'))
      ->save();
  }

}
