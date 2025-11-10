<?php

namespace Drupal\data_explorer\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Service for searching fields and values.
 */
class SearchService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a SearchService object.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Search for a value across all tables.
   */
  public function searchByValue($search_term, $limit = 50) {
    $results = [];
    $schema = $this->database->schema();
    
    // Get all tables.
    $tables = $this->database->schema()->findTables('%');
    
    foreach ($tables as $table_name) {
      // Skip system tables.
      if (strpos($table_name, 'cache_') === 0 || 
          strpos($table_name, 'sessions') === 0 ||
          strpos($table_name, 'watchdog') === 0) {
        continue;
      }

      if (!$schema->tableExists($table_name)) {
        continue;
      }

      try {
        $table_schema = $schema->getTableIntrospection($table_name);
        $columns = $table_schema->getColumns();
        
        // Build OR conditions for all string columns.
        $query = $this->database->select($table_name, 't');
        $query->fields('t');
        
        $conditions = $query->orConditionGroup();
        foreach ($columns as $column_name => $column) {
          $type = $column->getType();
          // Search in string-like columns.
          if (in_array($type, ['varchar', 'text', 'char', 'blob'])) {
            $conditions->condition($column_name, '%' . $this->database->escapeLike($search_term) . '%', 'LIKE');
          }
        }
        
        $query->condition($conditions);
        $query->range(0, $limit);
        
        $matches = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        
        if (!empty($matches)) {
          $results[] = [
            'table' => $table_name,
            'matches' => $matches,
            'count' => count($matches),
          ];
        }
      }
      catch (\Exception $e) {
        // Skip tables that cause errors.
        continue;
      }
    }

    return $results;
  }

  /**
   * Search for tables/fields by name.
   */
  public function searchByFieldName($search_term) {
    $results = [];
    $schema = $this->database->schema();
    
    // Get all tables.
    $tables = $this->database->schema()->findTables('%');
    
    foreach ($tables as $table_name) {
      // Check if table name matches.
      if (stripos($table_name, $search_term) !== FALSE) {
        $results[] = [
          'type' => 'table',
          'name' => $table_name,
          'columns' => $this->getTableColumns($table_name),
        ];
        continue;
      }

      // Check columns in table.
      if (!$schema->tableExists($table_name)) {
        continue;
      }

      try {
        $columns = [];
        if (method_exists($schema, 'getTableIntrospection')) {
          $table_schema = $schema->getTableIntrospection($table_name);
          $columns = $table_schema->getColumns();
        }
        else {
          // Fallback: Query information_schema or use DESCRIBE.
          $connection = $this->database;
          $driver = $connection->driver();
          if ($driver === 'mysql') {
            $result = $connection->query("DESCRIBE {" . $table_name . "}")->fetchAll();
            foreach ($result as $row) {
              $field_name = $row->Field;
              $field_type = $row->Type;
              $columns[$field_name] = (object) [
                'getType' => function() use ($field_type) { return $field_type; }
              ];
            }
          }
        }
        
        $matching_columns = [];
        foreach ($columns as $column_name => $column) {
          if (stripos($column_name, $search_term) !== FALSE) {
            $type = method_exists($column, 'getType') ? $column->getType() : 'unknown';
            $matching_columns[$column_name] = [
              'name' => $column_name,
              'type' => $type,
            ];
          }
        }
        
        if (!empty($matching_columns)) {
          $results[] = [
            'type' => 'columns',
            'table' => $table_name,
            'columns' => $matching_columns,
          ];
        }
      }
      catch (\Exception $e) {
        continue;
      }
    }

    return $results;
  }

  /**
   * Get columns for a table.
   */
  protected function getTableColumns($table_name) {
    try {
      $columns = [];
      $schema = $this->database->schema();
      if ($schema->tableExists($table_name)) {
        $table_schema = $schema->getTableIntrospection($table_name);
        foreach ($table_schema->getColumns() as $column_name => $column) {
          $columns[$column_name] = [
            'name' => $column_name,
            'type' => $column->getType(),
          ];
        }
      }
      return $columns;
    }
    catch (\Exception $e) {
      return [];
    }
  }

}

