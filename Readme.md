# pixx.io TYPO3 Extension

The pixx.io Typo3 Extension allows pixx.io users to select the assets directly from their mediaspace.

## Key Features:

- Select your assets from you pixx.io Mediaspace
- Sync your assets and metadata from pixx.io Mediaspace
- Includes Proxy support
- Works with the popular core extension: typo3/filemetadata

## ⚠️ Note on versions

This extension has several main versions, which are intended for different TYPO3 versions:

| Extension version | Compatible with TYPO3 | Branch                                                   | Changelog                                                                  |
| ----------------- | --------------------- | -------------------------------------------------------- | -------------------------------------------------------------------------- |
| 3.x               | TYPO3 v13             | [`main`](https://github.com/pixx-io/typo3)               | [Changelog 3.x](https://github.com/pixx-io/typo3/blob/main/CHANGELOG.md)   |
| 2.x               | TYPO3 v11 - v12       | [`master`](https://github.com/pixx-io/typo3/tree/master) | [Changelog 2.x](https://github.com/pixx-io/typo3/blob/master/CHANGELOG.md) |

Please use the appropriate version depending on your TYPO3 installation.

## Installation:

The installation of the extension is straight forward. Type `composer req pixxio/pixxio-extension` for installation. ext-curl is installed automatically, if not already installed.
After the successful installation go to Maintenance -> Analyze Database and apply the changes that are related to the pixxio_extension.

If there are problems with curl on your server and the files are not transferred, you can make sure that
`allow_url_fopen = On`
is set in your php.ini. With that configuration curl is not used but `file_get_contents`.

## Configuration:

To get the extension complete expierence, you have to do some settings first. Go to Settings > Extension configuration and select pixxio_extension.
You have four configuration categories Basic, Metadata, Sync and Proxy:

### Basic

For Sync Actions it is necessary to set the URL of your mediaspace and refresh token (The refresh token is accessible in pixx.io under the Settings -> User -> Edit a User and go to App Connections).

The File Storage ID is an optional setting. You can choose a Storage ID, where you would like to upload and store the pixx.io assets. You can also define a subfolder if you wish.

In the `allowed_download_formats` setting you can configure in which format the images are allowed to be imported. With the `original` format, the original file will be imported without conversion. With the `preview` format, images are downscaled to Full HD size and imported as JPEG or PNG. With the formats `jpg`, `png`, `pdf` and `tiff`, images are converted to the respective format if possible.

#### WEBP support

WEBP images require `webp` to be listed in `$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']` and server-side WEBP support via PHP-GD or ImageMagick.

### Metadata

It's possible to sync the alt text. Therefore you have to define the name of the metadata, which you would like to synchronize.

### Sync

See [docs/sync.md](./docs/sync.md) for detailed information about the sync process.

In Sync you can define behaviors that should be done during a running sync. **Note:** At least one of the following options must be enabled for the sync to run.

**Delete:**
If a file is deleted in pixx.io, it will also be deleted in TYPO3 when this flag is set. If this flag is disabled, files that no longer exist in pixx.io will be kept in TYPO3 (a warning will be logged).

**Update:**
If you use the version feature of pixx.io, you can automatically update files to their new main version. When this flag is set, the sync will replace files that aren't the main version with their new main version.

**Update Metadata:**
When this flag is set, the sync will update metadata (title, description, alt text, keywords, etc.) from pixx.io to TYPO3 for all synchronized files. This allows you to keep metadata in sync without updating file versions.

Limit:
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
- `GPS` / `GPS` (Type: Internal) => `GPS Latitude` und `GPS Longitude`
- `Country` (Type: Custom) => `Country`
- `Region` (Type: Custom) => `Region`
- `City` / `Stadt` (Type: IPTC) => `City`
- `Date created` / `Erstellungsdatum` (Type: Internal) => `Content Creation Date`
- `Zuletzt bearbeitet` (Type: Internal) => `Content Modification Date`
- `ModifyDate` / `Farbraum` (Type: Internal) => `Color Space`
