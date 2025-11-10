<?php

namespace Drupal\data_explorer\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Service for mapping relationships between tables.
 */
class RelationshipMapperService {

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
   * Constructs a RelationshipMapperService object.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Get relationships for an entity type or all entities.
   */
  public function getRelationships($entity_type = NULL, $bundle = NULL) {
    $relationships = [
      'nodes' => [],
      'edges' => [],
    ];

    $entity_types = $entity_type ? [$entity_type => $this->entityTypeManager->getDefinition($entity_type)] : $this->entityTypeManager->getDefinitions();

    foreach ($entity_types as $entity_type_id => $entity_type_definition) {
      $base_table = $entity_type_definition->getBaseTable();
      $data_table = $entity_type_definition->getDataTable();
      $revision_table = $entity_type_definition->getRevisionTable();
      $revision_data_table = $entity_type_definition->getRevisionDataTable();

      // Add base table node.
      if ($base_table) {
        $relationships['nodes'][$base_table] = [
          'id' => $base_table,
          'label' => $base_table,
          'type' => 'base',
          'entity_type' => $entity_type_id,
        ];

        // Connect base to data table.
        if ($data_table) {
          $relationships['nodes'][$data_table] = [
            'id' => $data_table,
            'label' => $data_table,
            'type' => 'data',
            'entity_type' => $entity_type_id,
          ];

          $relationships['edges'][] = [
            'source' => $base_table,
            'target' => $data_table,
            'type' => 'base_to_data',
            'label' => 'Data',
          ];
        }

        // Connect base to revision table.
        if ($revision_table) {
          $relationships['nodes'][$revision_table] = [
            'id' => $revision_table,
            'label' => $revision_table,
            'type' => 'revision',
            'entity_type' => $entity_type_id,
          ];

          $relationships['edges'][] = [
            'source' => $base_table,
            'target' => $revision_table,
            'type' => 'base_to_revision',
            'label' => 'Revision',
          ];
        }

        // Connect revision to revision data.
        if ($revision_table && $revision_data_table) {
          $relationships['nodes'][$revision_data_table] = [
            'id' => $revision_data_table,
            'label' => $revision_data_table,
            'type' => 'revision_data',
            'entity_type' => $entity_type_id,
          ];

          $relationships['edges'][] = [
            'source' => $revision_table,
            'target' => $revision_data_table,
            'type' => 'revision_to_data',
            'label' => 'Revision Data',
          ];
        }
      }

      // Get field tables.
      $field_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
      
      foreach ($field_definitions as $field_name => $field_definition) {
        if ($field_definition->isBaseField()) {
          continue;
        }

        $field_storage = $field_definition->getFieldStorageDefinition();
        $storage = $this->entityTypeManager->getStorage($entity_type_id);
        $table_mapping = $storage->getTableMapping();
        
        if ($table_mapping->requiresDedicatedTableStorage($field_storage)) {
          $field_data_table = $table_mapping->getDedicatedDataTableName($field_storage);
          $field_revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage);

          // Add field data table.
          if ($field_data_table) {
            $relationships['nodes'][$field_data_table] = [
              'id' => $field_data_table,
              'label' => $field_data_table . ' (' . $field_name . ')',
              'type' => 'field_data',
              'entity_type' => $entity_type_id,
              'field_name' => $field_name,
            ];

            // Connect to base or data table.
            $target_table = $data_table ? $data_table : $base_table;
            if ($target_table) {
              $relationships['edges'][] = [
                'source' => $target_table,
                'target' => $field_data_table,
                'type' => 'entity_to_field',
                'label' => $field_name,
                'field_name' => $field_name,
              ];
            }
          }

          // Add field revision table.
          if ($field_revision_table) {
            $relationships['nodes'][$field_revision_table] = [
              'id' => $field_revision_table,
              'label' => $field_revision_table . ' (' . $field_name . ')',
              'type' => 'field_revision',
              'entity_type' => $entity_type_id,
              'field_name' => $field_name,
            ];

            // Connect to revision table.
            $target_table = $revision_data_table ? $revision_data_table : $revision_table;
            if ($target_table) {
              $relationships['edges'][] = [
                'source' => $target_table,
                'target' => $field_revision_table,
                'type' => 'revision_to_field',
                'label' => $field_name,
                'field_name' => $field_name,
              ];
            }
          }
        }
      }
    }

    // Convert nodes array to indexed array for frontend.
    $relationships['nodes'] = array_values($relationships['nodes']);

    return $relationships;
  }

}

