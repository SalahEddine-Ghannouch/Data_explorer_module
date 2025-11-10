# Installation Guide

## Quick Start

1. **Place the module:**
   ```
   Copy the entire `data_explorer` folder to:
   [your-drupal-root]/modules/custom/data_explorer/
   ```

2. **Enable the module:**
   
   **Via Drush:**
   ```bash
   drush en data_explorer
   drush cr
   ```
   
   **Via Admin UI:**
   - Go to `/admin/modules` (Extend)
   - Search for "Data Explorer"
   - Check the box and click "Install"

3. **Set permissions:**
   - Go to `/admin/people/permissions`
   - Find "Data Explorer" section
   - Grant "Access Data Explorer" to Administrator role (or your role)

4. **Access the tool:**
   - Navigate to `/admin/tools/data-explorer`
   - You should see the Data Explorer interface with tabs

## Verification

After installation, verify:

- ✅ Module appears in `/admin/modules`
- ✅ Permission "Access Data Explorer" exists in `/admin/people/permissions`
- ✅ Route `/admin/tools/data-explorer` is accessible
- ✅ No errors in `/admin/reports/dblog`

## Troubleshooting

**Clear cache if module doesn't appear:**
```bash
drush cr
```

**Check module status:**
```bash
drush pm:list --type=module --status=enabled | grep data_explorer
```

**Check for errors:**
```bash
drush watchdog:show --filter=data_explorer
```

