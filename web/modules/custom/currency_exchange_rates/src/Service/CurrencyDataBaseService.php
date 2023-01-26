<?php

namespace Drupal\currency_exchange_rates\Service;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for work with database.
 */
class CurrencyDataBaseService {

  /**
   * Write logs in drupal admin.
   *
   * @var object
   */
  private $logger;

  /**
   * Stores the database connection object for work with database.
   *
   * @var object
   */
  private $database;

  /**
   * Constructs new CurrencyDataBaseService object.
   */
  public function __construct(LoggerChannelFactoryInterface $logger, Connection $database) {
    $this->logger = $logger;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): CurrencyDataBaseService {
    return new static(
      $container->get('currency_database'),
    );
  }

  /**
   * Gets currencies from database.
   */
  public function queryCurrenciesByDate(string $date, array $symbols = []): array {
    try {
      $date = new \DateTime($date);
      $query = $this->database->query('SELECT * FROM {daily_currencies} WHERE date = :date AND symbol IN (:symbol[])', [
        ':date' => $date->format('Y-m-d'),
        ':symbol[]' => $symbols != [] ? $symbols : [NULL],
      ], ['fetch' => \PDO::FETCH_ASSOC]);

      $data = [];
      while ($row = $query->fetchAssoc()) {
        if (!isset($data['timestamp'])) {
          $data['timestamp'] = $date->getTimestamp();
        }
        $data['rates'][$row['symbol']] = (float) $row['rate'];
      }
      return $data;
    }
    catch (\Exception $e) {
      $this->logger->get('currency_exchange_rates')->error($e->getMessage());
      throw $e;
    }
  }

  /**
   * Insert currencies into database.
   */
  public function queryInsertCurrencies(array $rates, int $timestamp): void {
    $query = $this->database->insert('daily_currencies')->fields([
      'symbol',
      'rate',
      'date',
    ]);
    foreach ($rates as $symbol => $rate) {
      $query->values([
        'symbol' => $symbol,
        'rate' => $rate,
        'date' => date('Y-m-d', $timestamp),
      ]);
    }
    $query->execute();
  }

  /**
   * Delete rows from table.
   */
  public function queryDeleteFromTable(string $column, mixed $value): void {
    $this->database->delete('daily_currencies')
      ->condition($column, $value)
      ->execute();
  }

  /**
   * Get currencies by date range from database.
   */
  public function queryCurrenciesByDateRange(int $range, array $symbols = []): array {
    $data = [];
    for ($i = 0; $i < $range; $i++) {
      $day = date_create(date('Y-m-d'));
      date_add($day, date_interval_create_from_date_string("-$i days"));
      $day = $day->format('Y-m-d');

      $result = $this->queryCurrenciesByDate($day, $symbols);
      if (!empty($result)) {
        $data[$day] = $result;
      }
    }
    $data = array_reverse($data);
    return $data;
  }

}
