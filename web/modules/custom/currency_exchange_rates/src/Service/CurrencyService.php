<?php

namespace Drupal\currency_exchange_rates\Service;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Client as GuzzleHttpClient;

/**
 * Service for work with openexchangerates api.
 *
 * Api site link https://docs.openexchangerates.org.
 */
class CurrencyService {

  /**
   * Stores the HTTP client to fetch data from the api.
   *
   * @var object
   */
  private $httpClient;

  /**
   * Stores configs from CurrencySettingsForm.
   *
   * @var object
   */
  private $configs;

  /**
   * Write logs in drupal admin.
   *
   * @var object
   */
  private $logger;

  /**
   * Constructs new CurrencyService object.
   */
  public function __construct(GuzzleHttpClient $http_client, ConfigFactory $configs, LoggerChannelFactoryInterface $logger) {
    $this->httpClient = $http_client;
    $this->configs = $configs->get('currency_exchange_rates.settings');
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): CurrencyService {
    return new static(
      $container->get('currency_block'),
    );
  }

  /**
   * Fetch openexchangerates API by using url from settings.
   */
  public function fetchApi(string $url) {
    try {
      return $this->httpClient->request('GET', $url);
    }
    catch (\Exception $e) {
      $this->logger->get('currency_exchange_rates')->error($e->getMessage());
      throw $e;
    }
  }

  /**
   * Checks if the json output matches the expected structure.
   *
   * Example of the expected output:
   * ...
   *
   * "rates": {
   *  "USD": 1.31730,
   *  "EUR": 1.21730,
   *   ...
   * }
   */
  public function validateJson($data): void {
    try {
      if (!array_key_exists('rates', $data)) {
        throw new \Exception("Invalid API url. Could not find 'rates' element in json output", 0);
      }
      if (preg_grep('/^[A-Z]{3}$/', $data['rates'], PREG_GREP_INVERT) == []) {
        throw new \Exception("Invalid API url. Could not find 'currencies' in 'rates' element.", 0);
      }
    }
    catch (\Exception $e) {
      $this->logger->get('currency_exchange_rates')->error($e->getMessage());
      throw $e;
    }
  }

  /**
   * Get currencies from api.
   */
  public function getData(string $url = ""): array {
    try {
      if ($url == "") {
        $url = $this->configs->get('openexchangerates_api_url');
      }

      $response = $this->fetchApi($url);

      $content = $response->getBody()->getContents();
      $data = json_decode($content, TRUE);

      return $data;
    }
    catch (\Exception $e) {
      throw $e;
    }
  }

}
