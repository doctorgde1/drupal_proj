<?php

namespace Drupal\currency_exchange_rates\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\currency_exchange_rates\Service\CurrencyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Currency exchange rates" block.
 *
 * @Block(
 * id = "currency_block",
 * admin_label = @Translation("Currency block"),
 * category = @Translation("Currency"),
 * )
 */
class CurrencyBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Stores CurrencyService object.
   *
   * @var object
   */
  protected $currencyApi;

  /**
   * Stores configs from CurrencySettingsForm.
   *
   * @var object
   */
  protected $configs;

  /**
   * Constructs a new CurrencyBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\currency_exchange_rates\Service\CurrencyService $currency_api
   *   Currency service to work with api.
   * @param Drupal\Core\Config\ConfigFactory $configs
   *   Configs from CurrencySettingsForm.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrencyService $currency_api, ConfigFactory $configs) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currencyApi = $currency_api;
    $this->configs = $configs->get('currency_exchange_rates.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): CurrencyBlock {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('currency_service'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    try {
      $url = $this->configs->get('openexchangerates_api_url');
      $chosen_currencies = array_values($this->configs->get('chosen_currencies'));
      $chosen_currencies = $this->currencyApi->trimArrayZeroes($chosen_currencies);
      $params = ["symbols" => $chosen_currencies];
      $data = $this->currencyApi->getData($url, $params);
    }
    catch (\Exception $e) {
      $data = [];
    }
    return [
      '#theme' => 'currency_exchange_rates_template',
      '#data' => $data,
    ];
  }

}
