/**
 * @file
 * Main JavaScript for Data Explorer module.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.dataExplorer = {
    attach: function (context, settings) {
      var $container = $('#data-explorer-container', context);
      if (!$container.length) {
        return;
      }

      // Tab switching.
      $('.tab-link', context).once('data-explorer-tabs').on('click', function (e) {
        e.preventDefault();
        var tabId = $(this).data('tab');
        
        // Update active tab link.
        $('.tab-link').removeClass('active');
        $(this).addClass('active');
        
        // Show/hide tab content.
        $('.tab-content').removeClass('active');
        $('#' + tabId + '-tab').addClass('active');
        
        // Load content for the tab if needed.
        if (tabId === 'schema') {
          loadSchemaExplorer();
        } else if (tabId === 'graph') {
          loadRelationshipGraph();
        } else if (tabId === 'query') {
          loadQueryBuilder();
        } else if (tabId === 'search') {
          loadSearchInterface();
        }
      });

      // Load initial tab.
      if ($('#schema-tab').hasClass('active')) {
        loadSchemaExplorer();
      }
    }
  };

  /**
   * Load schema explorer data.
   */
  function loadSchemaExplorer() {
    var $container = $('#schema-explorer');
    if ($container.data('loaded')) {
      return;
    }

    $container.html('<div class="loading">Loading schema...</div>');

    $.ajax({
      url: '/admin/tools/data-explorer/api/schema',
      method: 'GET',
      dataType: 'json',
      success: function (data) {
        renderSchemaExplorer(data);
        $container.data('loaded', true);
      },
      error: function (xhr, status, error) {
        $container.html('<div class="error">Error loading schema: ' + error + '</div>');
      }
    });
  }

  /**
   * Render schema explorer.
   */
  function renderSchemaExplorer(data) {
    var $container = $('#schema-explorer');
    var html = '<h2>Database Schema</h2>';
    
    html += '<div class="schema-stats">';
    html += '<p><strong>Total Tables:</strong> ' + Object.keys(data.tables).length + '</p>';
    html += '<p><strong>Entity Types:</strong> ' + Object.keys(data.entity_types).length + '</p>';
    html += '</div>';

    html += '<div class="schema-table-list">';
    
    // Group tables by entity type.
    var tablesByEntity = {};
    $.each(data.tables, function (tableName, tableData) {
      var entityType = tableData.entity_type || 'other';
      if (!tablesByEntity[entityType]) {
        tablesByEntity[entityType] = [];
      }
      tablesByEntity[entityType].push(tableData);
    });

    $.each(tablesByEntity, function (entityType, tables) {
      html += '<h3>' + (data.entity_types[entityType] ? data.entity_types[entityType].label : entityType) + '</h3>';
      
      $.each(tables, function (index, table) {
        html += '<div class="schema-table-card">';
        html += '<h4>' + table.name + '</h4>';
        html += '<span class="table-type ' + table.type + '">' + table.type + '</span>';
        
        if (table.columns && Object.keys(table.columns).length > 0) {
          html += '<div class="columns">';
          html += '<strong>Columns (' + Object.keys(table.columns).length + '):</strong>';
          html += '<ul>';
          $.each(table.columns, function (colName, colData) {
            html += '<li>' + colName + ' <span style="color: #999;">(' + colData.type + ')</span></li>';
          });
          html += '</ul>';
          html += '</div>';
        }
        
        html += '</div>';
      });
    });

    html += '</div>';
    $container.html(html);
  }

  /**
   * Load relationship graph.
   */
  function loadRelationshipGraph() {
    var $container = $('#relationship-graph');
    if ($container.data('loaded')) {
      return;
    }

    $container.html('<div class="loading">Loading relationships...</div>');

    $.ajax({
      url: '/admin/tools/data-explorer/api/relationships',
      method: 'GET',
      dataType: 'json',
      success: function (data) {
        renderRelationshipGraph(data);
        $container.data('loaded', true);
      },
      error: function (xhr, status, error) {
        $container.html('<div class="error">Error loading relationships: ' + error + '</div>');
      }
    });
  }

  /**
   * Render relationship graph (simplified version - can be enhanced with D3.js or vis.js).
   */
  function renderRelationshipGraph(data) {
    var $container = $('#relationship-graph');
    
    var html = '<div class="graph-controls">';
    html += '<label>Entity Type: <select id="entity-type-filter"><option value="">All</option></select></label>';
    html += '<button id="refresh-graph">Refresh Graph</button>';
    html += '</div>';
    
    html += '<div id="graph-canvas" style="width: 100%; height: 550px; overflow: auto; border: 1px solid #ddd; padding: 20px; background: white;">';
    
    // Simple text-based visualization (can be replaced with D3.js/vis.js)
    html += '<h3>Relationships (' + data.edges.length + ' connections)</h3>';
    html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px;">';
    
    $.each(data.nodes, function (index, node) {
      html += '<div style="border: 1px solid #ddd; padding: 10px; border-radius: 4px; background: #f9f9f9;">';
      html += '<strong>' + node.label + '</strong><br>';
      html += '<small style="color: #666;">Type: ' + node.type + '</small>';
      html += '</div>';
    });
    
    html += '</div>';
    
    html += '<h4 style="margin-top: 20px;">Connections:</h4>';
    html += '<ul>';
    $.each(data.edges, function (index, edge) {
      html += '<li>' + edge.source + ' → ' + edge.target + ' (' + edge.label + ')</li>';
    });
    html += '</ul>';
    
    html += '</div>';
    
    $container.html(html);
    
    // Note: For a proper interactive graph, integrate D3.js or vis.js here
  }

  /**
   * Load query builder.
   */
  function loadQueryBuilder() {
    var $container = $('#query-builder');
    if ($container.data('loaded')) {
      return;
    }

    var html = '<h2>Query Builder</h2>';
    html += '<div class="query-builder-form">';
    html += '<div class="form-row">';
    html += '<label>Entity Type:</label>';
    html += '<select id="query-entity-type"><option value="">Select entity type...</option></select>';
    html += '</div>';
    html += '<div class="form-row">';
    html += '<label>Bundle (optional):</label>';
    html += '<select id="query-bundle"><option value="">All bundles</option></select>';
    html += '</div>';
    html += '<div class="form-row">';
    html += '<label>Fields:</label>';
    html += '<div class="field-selector" id="field-selector">Select an entity type first</div>';
    html += '</div>';
    html += '<button id="build-query">Build & Execute Query</button>';
    html += '</div>';
    html += '<div class="query-results" id="query-results"></div>';
    
    $container.html(html);
    $container.data('loaded', true);

    // Load entity types.
    loadEntityTypes();
  }

  /**
   * Load entity types for query builder.
   */
  function loadEntityTypes() {
    $.ajax({
      url: '/admin/tools/data-explorer/api/schema',
      method: 'GET',
      dataType: 'json',
      success: function (data) {
        var $select = $('#query-entity-type');
        $.each(data.entity_types, function (entityType, info) {
          $select.append('<option value="' + entityType + '">' + info.label + ' (' + entityType + ')</option>');
        });

        // When entity type changes, load bundles and fields.
        $select.on('change', function () {
          var entityType = $(this).val();
          if (entityType) {
            loadBundlesAndFields(entityType);
          }
        });
      }
    });
  }

  /**
   * Load bundles and fields for selected entity type.
   */
  function loadBundlesAndFields(entityType) {
    $.ajax({
      url: '/admin/tools/data-explorer/api/schema',
      method: 'GET',
      dataType: 'json',
      success: function (data) {
        var entityInfo = data.entity_types[entityType];
        var $bundleSelect = $('#query-bundle');
        $bundleSelect.empty().append('<option value="">All bundles</option>');
        
        if (entityInfo.bundles) {
          $.each(entityInfo.bundles, function (bundleId, bundleLabel) {
            $bundleSelect.append('<option value="' + bundleId + '">' + bundleLabel + '</option>');
          });
        }

        // Load fields (simplified - would need API endpoint for fields)
        var $fieldSelector = $('#field-selector');
        $fieldSelector.html('<p>Field selection will be available after implementing field API endpoint.</p>');
      }
    });

    // Set up query execution.
    $('#build-query').off('click').on('click', function () {
      executeQuery();
    });
  }

  /**
   * Execute query.
   */
  function executeQuery() {
    var entityType = $('#query-entity-type').val();
    if (!entityType) {
      alert('Please select an entity type');
      return;
    }

    var bundle = $('#query-bundle').val();
    var $results = $('#query-results');
    $results.html('<div class="loading">Executing query...</div>');

    // For now, build a simple query (would need proper query builder API)
    var query = 'SELECT * FROM {' + entityType + '} LIMIT 50';
    
    $.ajax({
      url: '/admin/tools/data-explorer/api/query',
      method: 'POST',
      data: {
        query: query
      },
      dataType: 'json',
      success: function (response) {
        if (response.success) {
          renderQueryResults(response.data);
        } else {
          $results.html('<div class="error">Error: ' + response.error + '</div>');
        }
      },
      error: function (xhr, status, error) {
        $results.html('<div class="error">Error executing query: ' + error + '</div>');
      }
    });
  }

  /**
   * Render query results.
   */
  function renderQueryResults(data) {
    if (!data || data.length === 0) {
      $('#query-results').html('<p>No results found.</p>');
      return;
    }

    var html = '<h3>Results (' + data.length + ' rows)</h3>';
    html += '<div class="export-buttons">';
    html += '<button onclick="exportData(\'csv\')">Export CSV</button>';
    html += '<button onclick="exportData(\'json\')">Export JSON</button>';
    html += '</div>';
    html += '<table><thead><tr>';

    // Headers
    var headers = Object.keys(data[0]);
    $.each(headers, function (index, header) {
      html += '<th>' + header + '</th>';
    });
    html += '</tr></thead><tbody>';

    // Rows
    $.each(data, function (index, row) {
      html += '<tr>';
      $.each(headers, function (index, header) {
        var value = row[header];
        if (value === null || value === undefined) {
          value = '';
        }
        if (typeof value === 'object') {
          value = JSON.stringify(value);
        }
        html += '<td>' + Drupal.checkPlain(String(value).substring(0, 100)) + '</td>';
      });
      html += '</tr>';
    });

    html += '</tbody></table>';
    $('#query-results').html(html);
  }

  /**
   * Load search interface.
   */
  function loadSearchInterface() {
    var $container = $('#search-interface');
    if ($container.data('loaded')) {
      return;
    }

    var html = '<h2>Search Database</h2>';
    html += '<div class="search-form">';
    html += '<input type="text" id="search-term" placeholder="Enter search term...">';
    html += '<div class="search-type">';
    html += '<label><input type="radio" name="search-type" value="value" checked> Search by Value</label>';
    html += '<label><input type="radio" name="search-type" value="field"> Search by Field Name</label>';
    html += '</div>';
    html += '<button id="search-button">Search</button>';
    html += '</div>';
    html += '<div class="search-results" id="search-results"></div>';
    
    $container.html(html);
    $container.data('loaded', true);

    $('#search-button').on('click', function () {
      performSearch();
    });

    $('#search-term').on('keypress', function (e) {
      if (e.which === 13) {
        performSearch();
      }
    });
  }

  /**
   * Perform search.
   */
  function performSearch() {
    var searchTerm = $('#search-term').val();
    var searchType = $('input[name="search-type"]:checked').val();

    if (!searchTerm) {
      alert('Please enter a search term');
      return;
    }

    var $results = $('#search-results');
    $results.html('<div class="loading">Searching...</div>');

    $.ajax({
      url: '/admin/tools/data-explorer/api/search',
      method: 'GET',
      data: {
        term: searchTerm,
        type: searchType
      },
      dataType: 'json',
      success: function (data) {
        renderSearchResults(data, searchType);
      },
      error: function (xhr, status, error) {
        $results.html('<div class="error">Error searching: ' + error + '</div>');
      }
    });
  }

  /**
   * Render search results.
   */
  function renderSearchResults(data, searchType) {
    var $results = $('#search-results');
    
    if (!data || data.length === 0) {
      $results.html('<p>No results found.</p>');
      return;
    }

    var html = '<h3>Search Results (' + data.length + ' matches)</h3>';

    $.each(data, function (index, result) {
      html += '<div class="search-result-item">';
      
      if (searchType === 'value') {
        html += '<h4>Table: ' + result.table + '</h4>';
        html += '<div class="match-count">Found ' + result.count + ' matching row(s)</div>';
        
        if (result.matches && result.matches.length > 0) {
          html += '<table>';
          var headers = Object.keys(result.matches[0]);
          html += '<tr>';
          $.each(headers, function (i, h) {
            html += '<th>' + h + '</th>';
          });
          html += '</tr>';
          
          $.each(result.matches, function (i, match) {
            html += '<tr>';
            $.each(headers, function (j, h) {
              var value = match[h];
              if (value === null || value === undefined) {
                value = '';
              }
              html += '<td>' + Drupal.checkPlain(String(value).substring(0, 100)) + '</td>';
            });
            html += '</tr>';
          });
          html += '</table>';
        }
      } else {
        if (result.type === 'table') {
          html += '<h4>Table: ' + result.name + '</h4>';
        } else {
          html += '<h4>Table: ' + result.table + '</h4>';
          html += '<p>Matching columns:</p>';
          html += '<ul>';
          $.each(result.columns, function (colName, colData) {
            html += '<li>' + colName + ' (' + colData.type + ')</li>';
          });
          html += '</ul>';
        }
      }
      
      html += '</div>';
    });

    $results.html(html);
  }

  /**
   * Export data function (global for onclick).
   */
  window.exportData = function (format) {
    // This would need to store the current query results
    alert('Export functionality requires storing query state. To be implemented.');
  };

})(jQuery, Drupal, drupalSettings);

