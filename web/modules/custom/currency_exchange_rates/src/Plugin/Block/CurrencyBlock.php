<?php

namespace Drupal\currency_exchange_rates\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a "Currency exchange rates" block.
 *
 * @Block(
 * id = "currency_block",
 * admin_label = @Translation("Currency block"),
 * category = @Translation("Currency"),
 * )
 */
class CurrencyBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $http_client = \Drupal::httpClient();
    try {
      $apiUrl = 'https://openexchangerates.org/api/latest.json/?app_id=b023cacbf2d44a7890cae4633758a986';
      $response = $http_client->request('GET', $apiUrl);
    }
    catch (RequestException $e) {
      echo $e->getMessage();
    }

    $content = $response->getBody()->getContents();
    $data = Json::decode($content);

    return [
      '#theme' => 'currency_exchange_rates_template',
      '#data' => $data,
    ];
  }

}
