# pixx.io TYPO3 Extension

The pixx.io Typo3 Extension allows pixx.io users to select the assets directly from their mediaspace.

## Key Features:
- Select your assets from you pixx.io Mediaspace
- Sync your assets and metadata from pixx.io Mediaspace
- Includes Proxy support
- Works with the popular core extension: typo3/filemetadata


## Installation:
The installation of the extension is straight forward. Type `composer req pixxio/pixxio-extension` for installation. After the successful installation go to Maintenance -> Analyze Database and apply the changes that a related to the pixxio_extension. 

## Configuration:
To get the extension complete expierence, you have to do some settings first. Go to Settings > Extension configuration and select pixxio_extension.
You have four configuration categories Basic, Metadata, Sync and Proxy:

### Basic
For Sync Actions it is necessary to set the URL of your mediaspace and refresh token (The refresh token is accessible in pixx.io under the Settings -> User -> Edit a User and go to App Connections).

The File Storage ID is an optional setting. You can choose a Storage ID, where you would like to upload and store the pixx.io assets. You can also define a subfolder if you wish.

### Metadata
It's possible to sync the alt text. Therefore you have to define the name of the metadata, which you would like to synchronize.

### Sync
In Sync you can define behaviors that should be done during a running sync. 

**Delete:**
If a file is deleted in pixx.io, it will be deleted in typo3 as well if the flag is set. If not it will decouple the file from pixx.io.

**Update:**
If you use the version feature of pixx.io you be able to update the main version of a file. If the flag ist set the sync will replace files that aren't the main version to their new main version.

**Limit:**
You can define a limit from 1 to 50. This limit defines the amount of files that should be checked through a single sync run.


### Proxy Settings:
You be able to run the pixx.io connection via a proxy. Therefore you have to set in the extension configuration under the tab "Proxy" the flag “use_proxy” and add a valid connection string to the “proxy_connection”.
The proxy URL can have this schema http(s)://username:password@host:port . It’s not necessary to add a username or a password, but you should add the host and port for the connection.


## Works with

### filemetadata

If you are using the core extension filemetadata it will sync more metadata to typo3 from pixx.io. The mapping of the metadata is defined like this:

typo3 -> pixx.io

- Description -> description
- Ranking -> rating
- Keywords -> keywords
- Alternative Text -> (What you have defined in your configuration default is "Alt Text (Accessibility)")
- Caption -> description
- Download Name -> subject
- Creator -> creator
- Creator Tool -> Model
- Publisher -> Publisher
- Source -> Source
- Copyright -> CopyrightNotice
- Country -> Country
- Region -> Region
- City -> City
- GPS Latitude -> location.latitude 
- GPS Longitude -> location.longitude
- Content Creation Date -> createDate
- Content Modification Date -> modifyDate
- Color Space -> colorspace
- Unit -> Pixels