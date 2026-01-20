# Changelog

## 3.4.0

- Feature: Update Plugin SDK to latest version
- Feature: Improve logging
- Feature: New setting to sync only metadata (`update_metadata`)
- Feature: Modernize download handling
- Fix: Sync command now exits successfully when no pixx.io files in the system
- Fix: Use configured limit for sync action
- Fix: Missing files are deleted if activated in the settings
- Docs: Add documentation for sync command


## 3.3.0

- [v13.4] Add setting to configure the `allowed_download_formats` in the media selection

## 3.2.0

- [v13.4] Sync metadata when importing images without the need of the scheduler
- [v13.4] Fix file name collision. When a file was added with the same file name, the import was skipped.
- [v13.4] Add a strict isMainVersion check when synching files

## 3.1.0

- [v13.4] Add support for auto login in the media selection modal

## 3.0.x

- [v13.4] Add TYPO3 v13.4 support
