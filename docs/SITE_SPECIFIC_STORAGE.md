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
  url: 'https://your-mediaspace.pixx.io'
  token_refresh: 'your-refresh-token'
  filestorage_id: 2  # Site-specific storage UID
  subfolder: 'site_specific_folder'
  auto_login: true
```

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
  url: 'https://company-a.pixx.io'
  token_refresh: 'token_for_company_a_user'
  filestorage_id: 2  # Storage "Site A Files"
  subfolder: 'pixxio'
  auto_login: true
  update_metadata: true
```

### Site B Configuration

**`config/sites/site_b/settings.yaml`:**
```yaml
pixxio:
  url: 'https://company-b.pixx.io'  # Can be the same mediaspace!
  token_refresh: 'token_for_company_b_user'  # Different user with different permissions
  filestorage_id: 3  # Storage "Site B Files"
  subfolder: 'pixxio'
  auto_login: true
  update_metadata: true
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

## Best Practices

1. **Use descriptive storage names:** "Site A - Marketing Files" instead of "Storage 2"
2. **Document token ownership:** Use `user_id` in site settings as reminder
3. **Test permissions:** Create test users per group and verify file visibility
4. **Backup before migration:** If moving legacy files to site-specific storages
5. **Monitor sync logs:** Check that files are synced with correct credentials

## Related Documentation

- [Site-Specific Configuration](SITE_SPECIFIC_CONFIG.md)
- [TYPO3 File Abstraction Layer](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Fal/Index.html)
- [TYPO3 Site Configuration](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/SiteHandling/Index.html)
