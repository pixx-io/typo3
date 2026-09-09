# pixx.io TYPO3 Extension

[![Tests](https://github.com/pixx-io/typo3/actions/workflows/tests.yml/badge.svg)](https://github.com/pixx-io/typo3/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/pixx-io/typo3/branch/main/graph/badge.svg)](https://codecov.io/gh/pixx-io/typo3)

The pixx.io Typo3 Extension allows pixx.io users to select the assets directly from their mediaspace.

## Key Features:

- Select your assets from you pixx.io Mediaspace
- Sync your assets and metadata from pixx.io Mediaspace
- Multi-Site Support: Configure separate credentials and file storages per TYPO3 site (TYPO3 v13)
- Full TYPO3 FAL Support: Works with any FAL storage adapter (local filesystem, AWS S3, Azure, Google Cloud, etc.)
- Optional CDN Links: deliver assets directly from the pixx.io CDN instead of from your TYPO3 file storage
- Includes Proxy support
- Works with the popular core extension: typo3/filemetadata

## ⚠️ Note on versions

This extension has several main versions, which are intended for different TYPO3 versions:

| Extension version | Compatible with TYPO3 | Branch                                              | Changelog                                                                |
| ----------------- | --------------------- | --------------------------------------------------- | ------------------------------------------------------------------------ |
| 4.x               | TYPO3 v14             | [`v14`](https://github.com/pixx-io/typo3/tree/v14) | [Changelog 4.x](https://github.com/pixx-io/typo3/blob/v14/CHANGELOG.md)  |
| 3.x               | TYPO3 v13             | [`main`](https://github.com/pixx-io/typo3)          | [Changelog 3.x](https://github.com/pixx-io/typo3/blob/main/CHANGELOG.md) |
| 2.x               | TYPO3 v11 - v12.      | [`v12`](https://github.com/pixx-io/typo3/tree/v12)  | [Changelog 2.x](https://github.com/pixx-io/typo3/blob/v12/CHANGELOG.md)  |

Please use the appropriate version depending on your TYPO3 installation.

## Installation:

The installation of the extension is straight forward. Type `composer req pixxio/pixxio-extension` for installation. ext-curl is installed automatically, if not already installed.
After the successful installation go to Maintenance -> Analyze Database and apply the changes that are related to the pixxio_extension.

If there are problems with curl on your server and the files are not transferred, you can make sure that
`allow_url_fopen = On`
is set in your php.ini. With that configuration curl is not used but `file_get_contents`.

## Configuration:

To get the extension complete experience, you have to do some settings first. Go to Settings > Extension configuration and select pixxio_extension.

### Multi-Site Configuration (TYPO3 v13)

The extension supports site-specific configuration for multi-site installations. This allows you to:

- Use different pixx.io credentials (mediaspaces/users) per TYPO3 site
- Store files from different sites in separate storages
- Isolate file access using TYPO3's native file permissions

**Site-specific settings** (configured per site in TYPO3 Backend):

- `pixxio.url` - Mediaspace URL
- `pixxio.token_refresh` - API refresh token
- `pixxio.auto_login` - Auto-login in image picker
- `pixxio.filestorage_id` - Storage UID for file separation
- `pixxio.subfolder` - Subfolder within storage

All other settings (sync behavior, metadata mapping, proxy) are configured globally in Extension Configuration.

**📖 Detailed Documentation:** See [docs/SITE_SPECIFIC_STORAGE.md](./docs/SITE_SPECIFIC_STORAGE.md) for complete setup instructions, examples, and best practices.

**Example site configuration** (`config/sites/<siteIdentifier>/settings.yaml`):

```yaml
pixxio:
  url: "https://portal-a.pixx.io"
  token_refresh: "your-refresh-token"
  filestorage_id: 2
  subfolder: "pixxio"
  auto_login: true
```

If no site-specific value is set, the global extension configuration is used as fallback.

---

### Global Extension Configuration

You have four configuration categories: Basic, Metadata, Sync and Proxy:

### Basic

For Sync Actions it is necessary to set the URL of your mediaspace and refresh token (The refresh token is accessible in pixx.io under the Settings -> User -> Edit a User and go to App Connections).

**Note:** For multi-site installations, configure these values per site instead of globally (see Multi-Site Configuration above).

The File Storage ID is an optional setting. You can choose a Storage ID, where you would like to upload and store the pixx.io assets. You can also define a subfolder if you wish.

**Storage Adapters:** The extension uses TYPO3's File Abstraction Layer (FAL) API and supports any configured storage adapter:

- Local filesystem (default)
- AWS S3
- Azure Blob Storage
- Google Cloud Storage
- Or any other FAL-compatible storage driver

Simply configure your desired storage in TYPO3's File > Filelist module and reference its UID in the extension configuration.

In the `allowed_download_formats` setting you can configure in which format the images are allowed to be imported. With the `original` format, the original file will be imported without conversion. With the `preview` format, images are downscaled to Full HD size and imported as JPEG or PNG. With the formats `jpg`, `png`, `pdf` and `tiff`, images are converted to the respective format if possible.

#### CDN Links (`use_cdn_links`)

With the `use_cdn_links` flag enabled, assets are no longer delivered from your TYPO3 file storage but directly from the pixx.io CDN. **A pixx.io plan that includes CDN Links is required for this.**

The setting is global only (Extension Configuration > Basic) and cannot be overridden per site.

**What changes when the flag is enabled:**

- The image picker is opened with direct links enabled, so pixx.io creates a CDN link for every selected asset.
- On import, the CDN URL is stored in the file metadata and only a lightweight local preview image (max. 250px wide, generated via the PHP GD extension) is written to the configured file storage. The local file exists for the backend preview — it is not the file that is delivered in the frontend.
- Two read-only fields are filled in `sys_file_metadata` and shown in the file metadata form:
  - `pixxio_is_direct_link` – marks the file as a CDN file
  - `pixxio_direct_link` – the CDN URL used for delivery
- The cropping tool is hidden for such files in the file reference, because TYPO3 cannot process the image locally. TYPO3 image processing (scaling, cropping, responsive image variants) does not apply to CDN-delivered images; they are output with the dimensions of the CDN asset.

**Frontend rendering:**

The extension ships Fluid partial overrides that output `<img src="{file.properties.pixxio_direct_link}">` when a CDN link is present and fall back to the regular TYPO3 rendering otherwise:

- `Resources/Private/Partials/fluid_styled_content/Media/Rendering/Image.html` (for `fluid_styled_content`)
- `Resources/Private/Partials/bootstrap_package/Media/Gallery.html` (for `bootstrap_package`)

These partials are **not** registered automatically. Add the path you need to your TypoScript setup:

```typoscript
# fluid_styled_content
lib.contentElement.partialRootPaths.200 = EXT:pixxio_extension/Resources/Private/Partials/fluid_styled_content/

# bootstrap_package
lib.contentElement.partialRootPaths.201 = EXT:pixxio_extension/Resources/Private/Partials/bootstrap_package/
```

In your own templates you can use the metadata property directly:

```html
<f:if condition="{file.properties.pixxio_direct_link}">
    <f:then>
        <img src="{file.properties.pixxio_direct_link}" alt="{file.properties.alternative}">
    </f:then>
    <f:else>
        <f:media file="{file}" alt="{file.properties.alternative}" />
    </f:else>
</f:if>
```

**Image transformations via query parameters:**

CDN links support transformation parameters that can be appended to the URL. This is the replacement for TYPO3's local image processing, which is not available for CDN files:

| Parameter | Values                                       | Description                        |
| --------- | -------------------------------------------- | ---------------------------------- |
| `format`  | `auto`, `jpeg`, `webp`, `avif`, `png`, `gif` | Output format, `auto` negotiates the best format for the client |
| `quality` | `0` – `100`                                  | Compression quality                |
| `width`   | integer > 0                                  | Target width in pixels             |
| `height`  | integer > 0                                  | Target height in pixels            |

```html
<img src="{file.properties.pixxio_direct_link}?format=auto&quality=80&width=1200"
     alt="{file.properties.alternative}">
```

The parameters are only evaluated for the source formats JPEG, PNG, GIF, WebP and AVIF. For all other file types (e.g. PDF, TIFF, video) the asset is always delivered unchanged. They also require a CDN link — plain direct links without CDN always deliver the file as it is stored in pixx.io.

**Interaction with the sync:** Deleting files and syncing metadata work as usual. The `update` option (new main version) should stay disabled while CDN links are used: the sync replaces the local preview file, but the stored CDN URL is not refreshed, so the frontend would keep delivering the previous version.

### Metadata

It's possible to sync the alt text. Therefore you have to define the name of the metadata, which you would like to synchronize.

To meet special needs in your project you can modify the metadata before being persisted
by creating a PSR-14 event listener listening for the Pixxio\PixxioExtension\Event\MetaDataAfterPopulateEvent
event.

### Sync

See [docs/sync.md](./docs/sync.md) for detailed information about the sync process.

In Sync you can define behaviors that should be done during a running sync. **Note:** At least one of the following options must be enabled for the sync to run.

**Delete:**
If a file is deleted in pixx.io, it will also be deleted in TYPO3 when this flag is set. If this flag is disabled, files that no longer exist in pixx.io will be kept in TYPO3 (a warning will be logged).

**Update:**
If you use the version feature of pixx.io, you can automatically update files to their new main version. When this flag is set, the sync will replace files that aren't the main version with their new main version. This option is only meant to be used with CDN Links disabled (see [CDN Links](#cdn-links-use_cdn_links)).

**Update Metadata:**
When this flag is set, the sync will update metadata (title, description, alt text, keywords, etc.) from pixx.io to TYPO3 for all synchronized files. This allows you to keep metadata in sync without updating file versions.

**Limit:**
You can define a limit from 1 to 500. This limit defines the amount of files that should be checked through a single sync run.

### Proxy Settings:

You be able to run the pixx.io connection via a proxy. Therefore you have to set in the extension configuration under the tab "Proxy" the flag “use_proxy” and add a valid connection string to the “proxy_connection”.
The proxy URL can have this schema http(s)://username:password@host:port . It’s not necessary to add a username or a password, but you should add the host and port for the connection.

### Hide select button

You can hide the "Select from pixx.io" button for backend users and backend user groups. Do do so, just add a user setting:

`setup.override.show_pixxioUpload=0`

## Works with

### filemetadata

If you are using the core extension `filemetadata` we will sync more metadata from pixx.io to TYPO3. The mapping of the metadata is defined like this:

**Important for GPS sync:** In your mediaspace metadata settings, the internal field `Location (internal)` (`Ort des Motives (Intern)`) must be added/activated as an important metadata field.

#### Mapping from pixx.io to TYPO3

- `Title` / `Titel` (Type: Internal) => `Download Name`
- `Description` / `Beschreibung` (Type: Internal) => `Description` and `Caption`
- `Rating` / `Bewertung` (Type: Internal) => `Ranking `
- `Keywords` / `Schlagwörter` => `Keywords`
- `Creator` / `Ersteller` (Type: Internal) => `Creator`
- `Model` / `Model` (Type: EXIF) => `Creator Tool`
- `Publisher` / `Publisher` (Type: IPTC) => `Publisher`
- `Source` / `Quelle` (Type: IPTC) => `Source`
- `Copyright Notice` / `Copyright-Vermerk` (Type: IPTC) => `Copyright`
- `Location` / `Ort des Motives` (Type: Internal) => `GPS Latitude` und `GPS Longitude`
- `Country` (Type: Custom) => `Country`
- `Region` (Type: Custom) => `Region`
- `City` / `Stadt` (Type: IPTC) => `City`
- `Date created` / `Erstellungsdatum` (Type: Internal) => `Content Creation Date`
- `Zuletzt bearbeitet` (Type: Internal) => `Content Modification Date`
- `ModifyDate` / `Farbraum` (Type: Internal) => `Color Space`

## Documentation

- **[Site-Specific Storage Configuration](./docs/SITE_SPECIFIC_STORAGE.md)** - Complete guide for multi-site installations with separate file storages and credentials
- **[Sync Process](./docs/sync.md)** - Detailed information about the synchronization process

## Development

### Code Quality & Testing

This extension includes several tools to ensure code quality:

```bash
# Install dependencies
composer install

# Run PHP Syntax Check (using parallel-lint)
composer lint:php

# Run PHPStan Static Analysis
composer analyze:phpstan

# Run PHPUnit tests
composer test:unit
# or directly:
.Build/bin/phpunit

# Run complete CI pipeline (Lint + PHPStan + Tests)
composer ci

# Run tests with coverage
.Build/bin/phpunit --coverage-text
.Build/bin/phpunit --coverage-html coverage/
```

### PHPStan Configuration

The project uses PHPStan Level 6 for static code analysis to catch potential bugs and type errors before runtime.

Level 6 enforces:

- Type hints for all parameters and return types
- Proper array type specifications (e.g., `array<string, mixed>`)
- Generic type declarations for classes extending generic base classes
- Strict type checks including detection of always-true/false conditions
