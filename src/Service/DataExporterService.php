<?php

namespace Drupal\data_explorer\Service;

use Drupal\Core\Database\Connection;

/**
 * Service for exporting data in various formats.
 */
class DataExporterService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a DataExporterService object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Export data as CSV.
   */
  public function exportCsv(array $data) {
    if (empty($data)) {
      return '';
    }

    $output = fopen('php://temp', 'r+');
    
    // Write headers.
    $headers = array_keys($data[0]);
    fputcsv($output, $headers);
    
    // Write data rows.
    foreach ($data as $row) {
      fputcsv($output, $row);
    }
    
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    
    return $csv;
  }

  /**
   * Export data as JSON.
   */
  public function exportJson(array $data) {
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  }

}

