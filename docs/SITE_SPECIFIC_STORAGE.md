# Site-Specific File Storage Configuration

## Overview

The pixx.io extension supports **site-specific file storages** in TYPO3 multi-site installations. This allows you to:
- Store pixx.io files from different sites in separate storages
- Prevent cross-site file access using TYPO3's native file permissions
- Use different pixx.io credentials per site with dedicated file locations

## Configuration

### 1. Global Configuration (Default)

In the Extension Configuration (`Admin Tools > Settings > Extension Configuration > pixxio_extension`):

```
filestorage_id = 1  # Default: fileadmin
subfolder = pixxio
```

This is used for:
- Single-site installations
- As fallback for sites without specific configuration
- Legacy files without site_identifier

### 2. Site-Specific Configuration

In your site configuration (`config/sites/<site-identifier>/settings.yaml`):

```yaml
pixxio:
  # Storage Configuration
  filestorage_id: 2                    # Storage UID where files are stored
  subfolder: 'site_specific_folder'    # Subfolder within storage
  
  # Connection Configuration
  url: 'https://your-mediaspace.pixx.io'  # Mediaspace URL
  token_refresh: 'your-refresh-token'  # Refresh token for API access
  auto_login: true                     # Auto-login in image picker
```

**Note:** Only the fields listed above are configurable per site in the Backend (Sites module). Other extension settings (sync behavior, metadata, proxy, etc.) are configured globally via Extension Configuration and apply to all sites.

### Available Site-Specific Configuration Fields

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `filestorage_id` | int | `1` | Storage UID where files are stored. Use different IDs per site for file separation. Essential for multi-site file isolation. |
| `subfolder` | string | `''` | Subfolder within the storage (e.g. `pixxio`). Files are stored in `<storage>/<subfolder>/`. |
| `url` | string | `''` | Mediaspace URL (e.g. `https://company.pixx.io` or `company.px.media`). Required for site-specific credentials. |
| `token_refresh` | string | `''` | Refresh token for API authentication. Required for sync. Different tokens = different users/permissions per site. |
| `auto_login` | bool | `false` | Automatically log in with these credentials when opening the image picker. Improves UX for editors. |

**Global Settings (Extension Configuration only):**
- Sync behavior: `update`, `update_metadata`, `delete`, `limit`
- Download options: `allowed_download_formats`, `use_cdn_links`
- Metadata mapping: `alt_text`
- System settings: `use_proxy`, `proxy_connection`

These settings apply to all sites and cannot be overridden per site in the Backend UI.

**Configuration Priority:**
1. Site-specific settings (if page belongs to a site and field is configured)
2. Global Extension Configuration (fallback)
3. Empty strings in site settings are ignored (global value used)

### 3. Create Separate Storages

**Option A: Via Backend (recommended for testing)**
1. Go to `File > Filelist`
2. Click the Storage dropdown → "Add storage"
3. Create storages like:
   - UID 2: "Site A Files" → `/fileadmin/site_a/`
   - UID 3: "Site B Files" → `/fileadmin/site_b/`

**Option B: Via Database**
```sql
-- Create storage for Site A
INSERT INTO sys_file_storage (pid, name, driver, configuration, is_default, is_browsable, is_public, is_writable, is_online)
VALUES (0, 'Site A Files', 'Local', '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="basePath">
                    <value index="vDEF">fileadmin/site_a/</value>
                </field>
                <field index="pathType">
                    <value index="vDEF">relative</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>', 0, 1, 1, 1, 1);
```

## File Permissions Setup

### Restrict Backend Users to Specific Storages

1. **Create Backend User Groups per Site:**
   - Group "Site A Editors"
   - Group "Site B Editors"

2. **Configure File Mounts:**
   ```
   Group: Site A Editors
   File Mounts: → Add "Site A Files" storage (UID 2)
   
   Group: Site B Editors
   File Mounts: → Add "Site B Files" storage (UID 3)
   ```

3. **Assign Users to Groups:**
   - Site A editors can only see Storage 2
   - Site B editors can only see Storage 3
   - Files from other sites are invisible in the file browser

## Complete Example: Two-Site Setup

### Site A Configuration

**`config/sites/site_a/settings.yaml`:**
```yaml
pixxio:
  filestorage_id: 2  # Storage "Site A Files"
  subfolder: 'pixxio'
  url: 'https://company-a.pixx.io'
  token_refresh: 'token_for_company_a_user'
  auto_login: true
```

### Site B Configuration

**`config/sites/site_b/settings.yaml`:**
```yaml
pixxio:
  filestorage_id: 3  # Storage "Site B Files"
  subfolder: 'pixxio'
  url: 'https://company-b.pixx.io'  # Can be same mediaspace as Site A!
  token_refresh: 'token_for_company_b_user'  # Different user = different permissions
  auto_login: false
```

**Key Points:**
- Both sites can use the same mediaspace URL with different users/tokens
- Different users may have different permissions in the same mediaspace
- Each site has its own storage, preventing cross-site file access
- Sync settings (update, delete, limit) are configured globally and apply to all sites

## Common Configuration Scenarios

### Scenario 1: Minimal Override (Only Credentials)

Keep global settings for storage, only override credentials per site:

```yaml
# Global Extension Configuration: filestorage_id=1, subfolder='pixxio', update_metadata=true, limit=20

# config/sites/marketing/settings.yaml
pixxio:
  url: 'https://marketing.pixx.io'
  token_refresh: 'marketing_user_token'
  auto_login: true

# Result: Uses global storage 1 with subfolder 'pixxio', sync settings (update_metadata, limit),
#         but marketing credentials
```
```

### Scenario 2: Separate Storage per Site

Each site has its own storage and credentials:

```yaml
# config/sites/site_a/settings.yaml
pixxio:
  filestorage_id: 2
  subfolder: 'pixxio'
  url: 'https://company.pixx.io'
  token_refresh: 'site_a_token'
  auto_login: true

# config/sites/site_b/settings.yaml
pixxio:
  filestorage_id: 3
  subfolder: 'pixxio'
  url: 'https://company.pixx.io'  # Same mediaspace, different user
  token_refresh: 'site_b_token'
  auto_login: false
```

### Scenario 3: Same Mediaspace, Different Permissions

Multiple sites accessing the same mediaspace with different user accounts:

```yaml
# config/sites/public/settings.yaml
pixxio:
  filestorage_id: 2
  url: 'https://company.pixx.io'
  token_refresh: 'public_editor_token'  # User with limited permissions
  auto_login: true

# config/sites/admin/settings.yaml
pixxio:
  filestorage_id: 3
  url: 'https://company.pixx.io'  # Same mediaspace
  token_refresh: 'admin_token'  # User with full permissions
  auto_login: true

# Note: Different pixx.io users can have different folder access, approval rights, etc.
```

### Scenario 4: Shared Storage, Different Credentials

Multiple sites storing in the same TYPO3 storage but with different pixx.io credentials:

```yaml
# Global Extension Configuration: filestorage_id=1

# config/sites/dept_a/settings.yaml
pixxio:
  subfolder: 'dept_a'
  url: 'https://company.pixx.io'
  token_refresh: 'dept_a_token'

# config/sites/dept_b/settings.yaml
pixxio:
  subfolder: 'dept_b'
  url: 'https://company.pixx.io'
  token_refresh: 'dept_b_token'

# Result: Both use storage 1, but files are separated by subfolder and synced with different credentials
```

## How It Works

### Import Process

1. User clicks pixx.io button on a page in Site A
2. Extension detects site via `getSiteByPageId()`
3. Loads site-specific configuration including `filestorage_id: 2`
4. File is saved to Storage 2 (`/fileadmin/site_a/pixxio/`)
5. Metadata is tagged with `pixxio_site_identifier: "site_a"`

### Sync Process

1. Scheduler runs `pixxio:sync` command
2. Files are grouped by `pixxio_site_identifier`
3. Each group uses its site-specific configuration:
   - Site A files → Storage 2, Token A
   - Site B files → Storage 3, Token B
   - Legacy files → Global storage 1, Global token

### File Access Control

**Backend File Browser:**
- Site A editors see only Storage 2 (via File Mount permissions)
- Site B editors see only Storage 3
- Files are physically separated: `/fileadmin/site_a/` vs `/fileadmin/site_b/`

**Content Element File Picker:**
- Only files from accessible storages are shown
- Cross-site file usage is prevented by TYPO3's native permissions

## Migration from Single-Site to Multi-Site

### Existing Files (Legacy)

Files imported before enabling site-specific storages have:
- `pixxio_site_identifier = ''` (empty)
- Stored in global storage

**Behavior:**
- Legacy files continue to sync with global configuration
- They remain in the global storage
- They're accessible to all users (unless restricted by File Mounts)

### New Files

After configuring site-specific storages:
- New imports automatically use the correct storage per site
- Each file gets tagged with its site identifier
- Sync uses site-specific credentials

### Optional: Migrate Legacy Files

If you want to move existing files to site-specific storages:

```sql
-- Move files to Site A storage and update site_identifier
UPDATE sys_file_metadata 
SET pixxio_site_identifier = 'site_a'
WHERE pixxio_file_id != '' 
  AND pixxio_site_identifier = ''
  AND file IN (
    SELECT uid FROM sys_file WHERE storage = 1 AND identifier LIKE '/pixxio/%'
  );

-- Then physically move files and update sys_file.storage
```

## Troubleshooting

### Files appear in wrong storage

**Check:**
1. Site configuration has correct `filestorage_id`
2. Cache was cleared after config change (`typo3 cache:flush`)
3. File was imported AFTER setting site-specific config

### Sync fails for site-specific files

**Check:**
1. Site settings contain valid `token_refresh`
2. Storage exists and is online: `File > Filelist`
3. Storage path is writable by web server

### Users can't see files

**Check:**
1. User group has File Mount for the correct storage
2. Storage is marked as "browsable" in storage settings
3. Files physically exist in storage base path

### Site settings not applied

**Check:**
1. Settings file syntax is correct YAML (indentation matters!)
2. Cache was cleared: `typo3 cache:flush`
3. Settings are under `pixxio:` namespace (not `pixxio_extension:`)
4. Empty strings don't override (intentional behavior)
5. Check actual config in Debug mode or logs

**Debug site configuration:**
```php
// In ConfigurationUtility or FilesController, add:
\TYPO3\CMS\Core\Utility\DebugUtility::debug($extensionConfiguration);
```

### pixx.io button shows wrong credentials

**Check:**
1. Page is inside a site (check site root page ID)
2. Button's `data-pid` matches the page you're editing
3. Site settings contain `pixxio.url` and `pixxio.token_refresh`
4. Browser cache cleared (Button JS may be cached)

## Best Practices

1. **Use descriptive storage names:** "Site A - Marketing Files" instead of "Storage 2"
2. **Document token ownership:** Use comments in settings.yaml to note which pixx.io user the token belongs to
3. **Test permissions:** Create test users per group and verify file visibility
4. **Backup before migration:** If moving legacy files to site-specific storages
5. **Monitor sync logs:** Check that files are synced with correct credentials
6. **Start minimal:** Override only necessary fields per site (typically just url/token_refresh), let global config provide defaults
7. **Use subfolder:** Set `subfolder: 'pixxio'` to keep pixx.io files separate from other files in storage
8. **Version control settings:** Commit `config/sites/*/settings.yaml` to Git (but consider secrets management for tokens)
9. **Clear cache after changes:** Always run `typo3 cache:flush` after modifying site settings
10. **Test sync after setup:** Run `typo3 pixxio:sync` manually to verify configuration before enabling scheduler

## Related Documentation

- [Extension Configuration Overview](../Readme.md)
- [TYPO3 File Abstraction Layer](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Fal/Index.html)
- [TYPO3 Site Configuration](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/SiteHandling/Index.html)
