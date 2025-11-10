<?php

namespace Drupal\data_explorer\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Service for exploring Drupal database schema.
 */
class SchemaExplorerService {

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
   * Constructs a SchemaExplorerService object.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Get all Drupal tables and their structure.
   */
  public function getSchema() {
    $schema = [
      'tables' => [],
      'entity_types' => [],
    ];

    // Get all entity types.
    $entity_types = $this->entityTypeManager->getDefinitions();
    
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $base_table = $entity_type->getBaseTable();
      $data_table = $entity_type->getDataTable();
      $revision_table = $entity_type->getRevisionTable();
      $revision_data_table = $entity_type->getRevisionDataTable();

      $entity_info = [
        'id' => $entity_type_id,
        'label' => $entity_type->getLabel(),
        'base_table' => $base_table,
        'data_table' => $data_table,
        'revision_table' => $revision_table,
        'revision_data_table' => $revision_data_table,
        'bundles' => [],
      ];

      // Get bundles if applicable.
      if ($entity_type->hasKey('bundle')) {
        $bundle_key = $entity_type->getKey('bundle');
        $bundles = $this->entityTypeManager->getStorage($entity_type_id)->getEntityType()->getBundleEntityType();
        if ($bundles) {
          $bundle_storage = $this->entityTypeManager->getStorage($bundles);
          foreach ($bundle_storage->loadMultiple() as $bundle) {
            $entity_info['bundles'][$bundle->id()] = $bundle->label();
          }
        }
      }

      $schema['entity_types'][$entity_type_id] = $entity_info;

      // Get field tables for this entity type.
      $field_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
      
      foreach ($field_definitions as $field_name => $field_definition) {
        if ($field_definition->isBaseField()) {
          continue;
        }

        $field_storage = $field_definition->getFieldStorageDefinition();
        $table_mapping = $this->entityTypeManager->getStorage($entity_type_id)->getTableMapping();
        
        if ($table_mapping->requiresDedicatedTableStorage($field_storage)) {
          $table_name = $table_mapping->getDedicatedDataTableName($field_storage);
          $revision_table_name = $table_mapping->getDedicatedRevisionTableName($field_storage);
          
          if (!isset($schema['tables'][$table_name])) {
            $schema['tables'][$table_name] = [
              'name' => $table_name,
              'type' => 'field_data',
              'entity_type' => $entity_type_id,
              'field_name' => $field_name,
              'columns' => $this->getTableColumns($table_name),
            ];
          }
          
          if ($revision_table_name && !isset($schema['tables'][$revision_table_name])) {
            $schema['tables'][$revision_table_name] = [
              'name' => $revision_table_name,
              'type' => 'field_revision',
              'entity_type' => $entity_type_id,
              'field_name' => $field_name,
              'columns' => $this->getTableColumns($revision_table_name),
            ];
          }
        }
      }

      // Add base tables.
      if ($base_table && !isset($schema['tables'][$base_table])) {
        $schema['tables'][$base_table] = [
          'name' => $base_table,
          'type' => 'base',
          'entity_type' => $entity_type_id,
          'columns' => $this->getTableColumns($base_table),
        ];
      }

      if ($data_table && !isset($schema['tables'][$data_table])) {
        $schema['tables'][$data_table] = [
          'name' => $data_table,
          'type' => 'data',
          'entity_type' => $entity_type_id,
          'columns' => $this->getTableColumns($data_table),
        ];
      }
    }

    return $schema;
  }

  /**
   * Get columns for a table.
   */
  protected function getTableColumns($table_name) {
    try {
      $columns = [];
      $schema = $this->database->schema();
      if (!$schema->tableExists($table_name)) {
        return [];
      }

      // Try using getTableIntrospection for Drupal 9+.
      if (method_exists($schema, 'getTableIntrospection')) {
        $table_schema = $schema->getTableIntrospection($table_name);
        foreach ($table_schema->getColumns() as $column_name => $column) {
          $columns[$column_name] = [
            'name' => $column_name,
            'type' => $column->getType(),
            'not_null' => $column->getNotnull(),
          ];
        }
      }
      else {
        // Fallback: Use database-specific DESCRIBE/SHOW COLUMNS.
        $connection = $this->database;
        $driver = $connection->driver();
        
        if ($driver === 'mysql') {
          $result = $connection->query("DESCRIBE {" . $table_name . "}")->fetchAll();
          foreach ($result as $row) {
            $columns[$row->Field] = [
              'name' => $row->Field,
              'type' => $row->Type,
              'not_null' => $row->Null === 'NO',
            ];
          }
        }
        elseif ($driver === 'pgsql') {
          $result = $connection->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = :table", [
            ':table' => $table_name,
          ])->fetchAll();
          foreach ($result as $row) {
            $columns[$row->column_name] = [
              'name' => $row->column_name,
              'type' => $row->data_type,
              'not_null' => FALSE,
            ];
          }
        }
      }
      
      return $columns;
    }
    catch (\Exception $e) {
      return [];
    }
  }

}

