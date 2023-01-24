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
      $container->get('currency_service'),
    );
  }

  /**
   * Fetch openexchangerates API by using url from settings.
   */
  public function fetchApi(string $url): mixed {
    try {
      $response = $this->httpClient->request('GET', $url);

      $content = $response->getBody()->getContents();
      $api_data = json_decode($content, TRUE);

      return $api_data;
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
    if (!array_key_exists($key, $data)) {
      $this->logger->get('currency_exchange_rates')->error("Invalid API url. Could not find '$key' in data.");
      throw new \Exception("Invalid API url. Could not find '$key' in data.", 0);
    }
  }

  /**
   * Checks if data matches the expected structure.
   */
  public function matchStruct(array $data, string $regex, string $error_message): void {
    if (preg_grep($regex, $data, PREG_GREP_INVERT) == []) {
      $this->logger->get('currency_exchange_rates')->error("Invalid API url. Could not find '$error_message' in data.");
      throw new \Exception("Invalid API url. Could not find '$error_message' in data.", 0);
    }
  }

  /**
   * Get currencies from api.
   */
  public function getData(string $url = "", array $params = []): array {
    if ($url == "") {
      $url = $this->configs->get('openexchangerates_api_url');
    }

    if ($url != $this->configs->get('openexchangerates_api_url')) {
      // $url = $this->addParamToUrl($url, $params);
      $api_data = $this->fetchApi($url);

      $this->findKey($api_data, "timestamp");
      $this->findKey($api_data, "rates");
      $this->matchStruct($api_data['rates'], "/^[A-Z]{3}$/", 'currencies');

      $this->currencyDatabase->queryDeleteFromTable('date', date('Y-m-d'));
      $this->currencyDatabase->queryInsertCurrencies($api_data['rates'], $api_data['timestamp']);
    }

    $this->findKey($params, "symbols");
    $database_data = $this->currencyDatabase->queryCurrenciesByDate(date('Y-m-d'), $params["symbols"]);

    if (empty($database_data) && !empty($params["symbols"])) {
      $api_data = $this->fetchApi($url);
      $this->currencyDatabase->queryInsertCurrencies($api_data['rates'], $api_data['timestamp']);
    }

    return $database_data;
  }

  /**
   * Get currencies catalog.
   */
  public function getCurrenciesCatalog(string $url = ""): array {
    if ($url == "") {
      $url = $this->configs->get('currencies_catalog_url');
    }

    $api_data = $this->fetchApi($url);

    $this->matchStruct($api_data, "/^[A-Z]{3}$/", 'currencies');

    return $api_data;
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
