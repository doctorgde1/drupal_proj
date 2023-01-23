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
   * Stores CurrencyDatabaseService object.
   *
   * @var object
   */
  private $currencyDatabase;

  /**
   * Constructs new CurrencyService object.
   */
  public function __construct(GuzzleHttpClient $http_client, ConfigFactory $configs, LoggerChannelFactoryInterface $logger, CurrencyDataBaseService $database) {
    $this->httpClient = $http_client;
    $this->configs = $configs->get('currency_exchange_rates.settings');
    $this->logger = $logger;
    $this->currencyDatabase = $database;
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
   * Checks if data has given key.
   */
  public function findKey(array $data, string $key): void {
    try {
      if (!array_key_exists($key, $data)) {
        throw new \Exception("Invalid API url. Could not find '$key' in data.", 0);
      }
    }
    catch (\Exception $e) {
      $this->logger->get('currency_exchange_rates')->error($e->getMessage());
      throw $e;
    }
  }

  /**
   * Checks if data matches the expected structure.
   */
  public function matchStruct(array $data, string $regex, string $error_message): void {
    try {
      if (preg_grep($regex, $data, PREG_GREP_INVERT) == []) {
        throw new \Exception("Invalid API url. Could not find '$error_message' in data.", 0);
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
  public function getData(string $url = "", array $params = []): array {
    try {
      if ($url == "") {
        $url = $this->configs->get('openexchangerates_api_url');
      }

      $this->findKey($params, "symbols");
      $database_data = $this->currencyDatabase->queryCurrenciesByDate(date('Y-m-d'), $params["symbols"]);
      if (empty($database_data)) {
        // $url = $this->addParamToUrl($url, $params);
        $response = $this->fetchApi($url);

        $content = $response->getBody()->getContents();
        $api_data = json_decode($content, TRUE);

        $this->findKey($api_data, "rates");
        $this->findKey($api_data, "timestamp");

        $this->currencyDatabase->queryInsertCurrencies($api_data['rates'], $api_data['timestamp']);

        $database_data = $this->currencyDatabase->queryCurrenciesByDate(date('Y-m-d'), $params["symbols"]);
      }
      return $database_data;
    }
    catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * Get currencies catalog.
   */
  public function getCurrenciesCatalog(string $url = ""): array {
    try {
      if ($url == "") {
        $url = $this->configs->get('currencies_catalog_url');
      }

      $response = $this->fetchApi($url);

      $content = $response->getBody()->getContents();

      $data = json_decode($content, TRUE);

      $this->matchStruct($data, "/^[A-Z]{3}$/", 'currencies');

      return $data;
    }
    catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * Adds params to url.
   */
  private function addParamToUrl(string $url, array $params): string {
    foreach ($params as $param_name => $param_values) {
      $url .= "&$param_name=";

      $param_values = array_unique($param_values);

      foreach ($param_values as $param_value) {
        $url .= "$param_value,";
      }
      $url = rtrim($url, ',');
    }
    return $url;
  }

  /**
   * Deletes 0-s from array.
   */
  public function trimArrayZeroes($array): array {
    $array = array_values($array);
    $array = array_unique($array);
    array_pop($array);
    return $array;
  }

}
