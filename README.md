# Drupal Data Explorer Module

A custom Drupal module that visualizes and analyzes the database structure. It maps relationships between entity tables, field tables, and revisions in an interactive graph view. The tool lets developers search where specific data is stored and preview joined results directly in the admin UI.

## Features

### 🔍 Schema Explorer
Auto-scans Drupal tables (node__, taxonomy__, users__, etc.) and shows entity–field relationships.

### 🕸️ Visual Relationship Graph
Interactive graph view showing relationships between base tables and their field tables (like node_field_data → node__body, etc.).

### 🧾 Smart Query Builder
GUI that lets you pick an entity (e.g. Node → Article), choose fields, and auto-build SQL queries with JOINs.

### 💾 Data Exporter
Export joined results as CSV/JSON (useful for content migration or debugging).

### 🧠 Search by Field or Value
Search "where is this string stored?" → the tool scans tables to find possible locations (like finding the field where "Welcome to Inwi" is saved).

### ⚙️ Drupal Integration Mode
Works as a Drupal admin module (accessible at `/admin/tools/data-explorer`).

## Installation

1. Copy the `data_explorer` module to your Drupal installation:
   ```
   modules/custom/data_explorer/
   ```

2. Enable the module:
   - Via Drush: `drush en data_explorer`
   - Via Admin UI: Go to Extend (`/admin/modules`), find "Data Explorer" and enable it

3. Grant permissions:
   - Go to People → Permissions (`/admin/people/permissions`)
   - Grant "Access Data Explorer" permission to appropriate roles

## Usage

### Accessing the Tool

Navigate to `/admin/tools/data-explorer` in your Drupal site.

### Schema Explorer Tab

- Automatically displays all Drupal tables organized by entity type
- Shows table types (base, data, field_data, revision, etc.)
- Lists all columns for each table with their data types

### Relationship Graph Tab

- Visualizes relationships between:
  - Base tables and data tables
  - Entity tables and field tables
  - Revision tables and revision data tables
- Filter by entity type to focus on specific entities

### Query Builder Tab

1. Select an entity type (e.g., Node, User, Taxonomy Term)
2. Optionally select a bundle (e.g., Article, Page)
3. Choose fields to include in the query
4. Click "Build & Execute Query" to see results
5. Export results as CSV or JSON

### Search Tab

**Search by Value:**
- Enter a search term (e.g., "Welcome to Inwi")
- The tool searches across all string columns in all tables
- Results show which tables contain the value and matching rows

**Search by Field Name:**
- Enter a field or table name
- Results show matching tables and columns

## API Endpoints

The module provides JSON API endpoints for programmatic access:

- `GET /admin/tools/data-explorer/api/schema` - Get full database schema
- `GET /admin/tools/data-explorer/api/relationships?entity_type=node&bundle=article` - Get relationships
- `GET /admin/tools/data-explorer/api/search?term=search_term&type=value` - Search database
- `POST /admin/tools/data-explorer/api/query` - Execute a query (body: `{"query": "SELECT ..."}`)

## Security

- Only users with "Access Data Explorer" permission can use the tool
- Query execution only allows SELECT queries
- Dangerous SQL keywords (DROP, DELETE, UPDATE, etc.) are blocked
- All queries are executed through Drupal's database abstraction layer

## Requirements

- Drupal 9, 10, or 11
- PHP 7.4 or higher

## Development

### File Structure

```
data_explorer/
├── data_explorer.info.yml          # Module definition
├── data_explorer.module            # Module hooks
├── data_explorer.routing.yml       # Routes
├── data_explorer.services.yml      # Service definitions
├── data_explorer.libraries.yml      # Asset libraries
├── src/
│   ├── Controller/
│   │   ├── DataExplorerController.php      # Main page controller
│   │   └── DataExplorerApiController.php   # API endpoints
│   └── Service/
│       ├── SchemaExplorerService.php       # Schema scanning
│       ├── RelationshipMapperService.php   # Relationship mapping
│       ├── QueryBuilderService.php         # Query building
│       ├── DataExporterService.php         # Data export
│       └── SearchService.php               # Search functionality
├── css/
│   └── data-explorer.css           # Styles
└── js/
    └── data-explorer.js            # Frontend JavaScript
```

### Extending the Module

To add a custom visualization library (e.g., D3.js or vis.js) for the relationship graph:

1. Add the library to `data_explorer.libraries.yml`
2. Update `js/data-explorer.js` in the `renderRelationshipGraph()` function
3. Enhance the graph visualization with interactive features

## Troubleshooting

**Module not appearing in Extend list:**
- Ensure the module is in `modules/custom/data_explorer/`
- Clear Drupal cache: `drush cr`

**Permission denied:**
- Grant "Access Data Explorer" permission to your user role

**No tables showing:**
- Ensure you have entities in your Drupal site
- Check database connection is working

**Query errors:**
- Only SELECT queries are allowed
- Check that table names are correct
- Ensure proper JOIN syntax

## License

This is a custom module. Modify as needed for your project.

## Support

For issues or questions, check the Drupal logs at `/admin/reports/dblog`.

