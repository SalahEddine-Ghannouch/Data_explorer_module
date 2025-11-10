<?php

namespace Drupal\data_explorer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Main controller for Data Explorer.
 */
class DataExplorerController extends ControllerBase {

  /**
   * Main page for Data Explorer.
   *
   * @return array
   *   A render array.
   */
  public function mainPage() {
    $build = [];

    // Add CSS and JS libraries.
    $build['#attached']['library'][] = 'data_explorer/main';
    $build['#attached']['drupalSettings']['dataExplorer'] = [
      'apiBase' => '/admin/tools/data-explorer/api',
      'routes' => [
        'schema' => '/admin/tools/data-explorer/api/schema',
        'relationships' => '/admin/tools/data-explorer/api/relationships',
        'search' => '/admin/tools/data-explorer/api/search',
        'query' => '/admin/tools/data-explorer/api/query',
      ],
    ];

    // Main container.
    $build['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'data-explorer-container'],
    ];

    // Tabs for different features.
    $build['container']['tabs'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['data-explorer-tabs']],
    ];

    $build['container']['tabs']['tab_list'] = [
      '#theme' => 'item_list',
      '#items' => [
        [
          '#type' => 'link',
          '#title' => $this->t('Schema Explorer'),
          '#url' => Url::fromRoute('<current>'),
          '#attributes' => ['class' => ['tab-link'], 'data-tab' => 'schema'],
        ],
        [
          '#type' => 'link',
          '#title' => $this->t('Relationship Graph'),
          '#url' => Url::fromRoute('<current>'),
          '#attributes' => ['class' => ['tab-link'], 'data-tab' => 'graph'],
        ],
        [
          '#type' => 'link',
          '#title' => $this->t('Query Builder'),
          '#url' => Url::fromRoute('<current>'),
          '#attributes' => ['class' => ['tab-link'], 'data-tab' => 'query'],
        ],
        [
          '#type' => 'link',
          '#title' => $this->t('Search'),
          '#url' => Url::fromRoute('<current>'),
          '#attributes' => ['class' => ['tab-link'], 'data-tab' => 'search'],
        ],
      ],
      '#attributes' => ['class' => ['tabs']],
    ];

    // Schema Explorer tab.
    $build['container']['schema_tab'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'schema-tab', 'class' => ['tab-content', 'active']],
      'content' => [
        '#markup' => '<div id="schema-explorer"></div>',
      ],
    ];

    // Relationship Graph tab.
    $build['container']['graph_tab'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'graph-tab', 'class' => ['tab-content']],
      'content' => [
        '#markup' => '<div id="relationship-graph"></div>',
      ],
    ];

    // Query Builder tab.
    $build['container']['query_tab'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'query-tab', 'class' => ['tab-content']],
      'content' => [
        '#markup' => '<div id="query-builder"></div>',
      ],
    ];

    // Search tab.
    $build['container']['search_tab'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'search-tab', 'class' => ['tab-content']],
      'content' => [
        '#markup' => '<div id="search-interface"></div>',
      ],
    ];

    return $build;
  }

  /**
   * Export data endpoint.
   */
  public function exportData(Request $request) {
    $format = $request->query->get('format', 'csv');
    $query = $request->query->get('query');

    if (!$query) {
      return new JsonResponse(['error' => 'No query provided'], 400);
    }

    $query_builder = \Drupal::service('data_explorer.query_builder');
    $results = $query_builder->executeQuery($query);

    $exporter = \Drupal::service('data_explorer.data_exporter');
    
    if ($format === 'json') {
      $content = $exporter->exportJson($results);
      $response = new Response($content);
      $response->headers->set('Content-Type', 'application/json');
      $response->headers->set('Content-Disposition', 'attachment; filename="data_export.json"');
      return $response;
    }
    else {
      $content = $exporter->exportCsv($results);
      $response = new Response($content);
      $response->headers->set('Content-Type', 'text/csv');
      $response->headers->set('Content-Disposition', 'attachment; filename="data_export.csv"');
      return $response;
    }
  }

}

