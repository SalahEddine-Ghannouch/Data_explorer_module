<?php

namespace Drupal\data_explorer\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Service for building and executing queries.
 */
class QueryBuilderService {

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
   * Constructs a QueryBuilderService object.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Build a query from entity type, bundle, and fields.
   */
  public function buildQuery($entity_type, $bundle = NULL, array $fields = []) {
    $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
    $base_table = $entity_type_definition->getBaseTable();
    $data_table = $entity_type_definition->getDataTable();
    
    $query = $this->database->select($base_table, 'base');
    
    // Join data table if it exists.
    if ($data_table) {
      $id_key = $entity_type_definition->getKey('id');
      $query->join($data_table, 'data', "base.{$id_key} = data.{$id_key}");
      
      // Filter by bundle if provided.
      if ($bundle && $entity_type_definition->hasKey('bundle')) {
        $bundle_key = $entity_type_definition->getKey('bundle');
        $query->condition("data.{$bundle_key}", $bundle);
      }
    }

    // Add base fields.
    $query->fields('base');
    if ($data_table) {
      $query->fields('data');
    }

    // Add field tables.
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $table_mapping = $storage->getTableMapping();
    
    foreach ($fields as $field_name) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
      
      if (isset($field_definitions[$field_name])) {
        $field_definition = $field_definitions[$field_name];
        $field_storage = $field_definition->getFieldStorageDefinition();
        
        if ($table_mapping->requiresDedicatedTableStorage($field_storage)) {
          $field_table = $table_mapping->getDedicatedDataTableName($field_storage);
          $entity_id_key = $entity_type_definition->getKey('id');
          
          $alias = 'field_' . str_replace('-', '_', $field_name);
          $query->leftJoin($field_table, $alias, "base.{$entity_id_key} = {$alias}.entity_id");
          
          // Add all columns from field table.
          $schema = $this->database->schema();
          if ($schema->tableExists($field_table)) {
            // Try getTableIntrospection, fallback to direct query.
            if (method_exists($schema, 'getTableIntrospection')) {
              $table_schema = $schema->getTableIntrospection($field_table);
              foreach ($table_schema->getColumns() as $column_name => $column) {
                $query->addField($alias, $column_name, "{$field_name}_{$column_name}");
              }
            }
            else {
              // Fallback: Add common field columns.
              $query->addField($alias, 'entity_id', "{$field_name}_entity_id");
              $query->addField($alias, 'delta', "{$field_name}_delta");
              $query->addField($alias, 'bundle', "{$field_name}_bundle");
              // Add value columns (field-specific).
              $query->addField($alias, $field_name . '_value', "{$field_name}_value");
            }
          }
        }
      }
    }

    return $query;
  }

  /**
   * Execute a raw SQL query.
   */
  public function executeQuery($sql) {
    // Security: Only allow SELECT queries.
    $sql = trim($sql);
    if (stripos($sql, 'SELECT') !== 0) {
      throw new \InvalidArgumentException('Only SELECT queries are allowed');
    }

    // Remove dangerous keywords.
    $dangerous = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'TRUNCATE'];
    foreach ($dangerous as $keyword) {
      if (stripos($sql, $keyword) !== FALSE) {
        throw new \InvalidArgumentException("Query contains forbidden keyword: {$keyword}");
      }
    }

    try {
      // Replace {table} placeholders with actual table names.
      // Note: This is a simplified approach. For production, use proper Drupal query builder.
      $results = $this->database->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
      return $results;
    }
    catch (\Exception $e) {
      throw new \RuntimeException('Query execution failed: ' . $e->getMessage());
    }
  }

  /**
   * Get available fields for an entity type and bundle.
   */
  public function getAvailableFields($entity_type, $bundle = NULL) {
    $fields = [];
    
    $field_definitions = $bundle 
      ? $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle)
      : $this->entityFieldManager->getFieldStorageDefinitions($entity_type);
    
    foreach ($field_definitions as $field_name => $field_definition) {
      $fields[$field_name] = [
        'name' => $field_name,
        'label' => $field_definition->getLabel(),
        'type' => $field_definition->getType(),
        'is_base_field' => $field_definition->isBaseField(),
      ];
    }
    
    return $fields;
  }

}

