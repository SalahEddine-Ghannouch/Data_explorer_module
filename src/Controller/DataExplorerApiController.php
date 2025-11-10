<?php

namespace Drupal\data_explorer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for Data Explorer AJAX endpoints.
 */
class DataExplorerApiController extends ControllerBase {

  /**
   * Get schema information.
   */
  public function getSchema() {
    $schema_service = \Drupal::service('data_explorer.schema_service');
    $schema = $schema_service->getSchema();
    return new JsonResponse($schema);
  }

  /**
   * Get relationship mappings.
   */
  public function getRelationships(Request $request) {
    $entity_type = $request->query->get('entity_type');
    $bundle = $request->query->get('bundle');
    
    $relationship_service = \Drupal::service('data_explorer.relationship_mapper');
    $relationships = $relationship_service->getRelationships($entity_type, $bundle);
    return new JsonResponse($relationships);
  }

  /**
   * Search for field or value.
   */
  public function search(Request $request) {
    $search_term = $request->query->get('term');
    $search_type = $request->query->get('type', 'value'); // 'value' or 'field'
    
    if (!$search_term) {
      return new JsonResponse(['error' => 'Search term required'], 400);
    }

    $search_service = \Drupal::service('data_explorer.search_service');
    
    if ($search_type === 'field') {
      $results = $search_service->searchByFieldName($search_term);
    }
    else {
      $results = $search_service->searchByValue($search_term);
    }
    
    return new JsonResponse($results);
  }

  /**
   * Execute a query.
   */
  public function executeQuery(Request $request) {
    // Support both POST and GET requests.
    if ($request->getMethod() === 'POST') {
      $query = $request->request->get('query');
    }
    else {
      $query = $request->query->get('query');
    }
    
    if (!$query) {
      return new JsonResponse(['error' => 'Query required'], 400);
    }

    $query_builder = \Drupal::service('data_explorer.query_builder');
    
    try {
      $results = $query_builder->executeQuery($query);
      return new JsonResponse([
        'success' => TRUE,
        'data' => $results,
        'count' => count($results),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $e->getMessage(),
      ], 400);
    }
  }

}

