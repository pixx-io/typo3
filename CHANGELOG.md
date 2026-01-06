# Changelog

## NEXT

- Feat: Update Plugin SDK to latest version
- Fix: Use configured limit for sync action
- Fix: Missing files are deleted if activated in the settings
- Add documentation for sync command

## 2.3.0

- Fix file name collision. When a file was added with the same file name, the import was skipped.
- Add a strict isMainVersion check when synching files
- Add configurable allowedDownloadFormats parameter

## 2.2.0

- Add setting to enable auto-login with the sync access data in the image picker.

## 2.1.2

- Improve sync and sync logs
- Update documentation for synced metdata
- Update internal plugin identifier

## 2.1.1

- Fix fatal error in SyncCommand

## 2.1.0

- Add custom user setting to hide the upload button: `setup.override.show_pixxioUpload=0`

## 2.0.4

- Fix "Select from pixx.io" button in inline relation content elements
- Fix deleting files in scheduler command
- Alt text is now correctly transferred from the metadata

## 2.0.3

- Fix bugs to support multiple image fields

## 2.0.2

- Bugfix for TYPO3 >= 12.4.3 in Controller/FilesControlContainer.php (FileExtensionFilter)
