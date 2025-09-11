# GitHub Copilot Instructions for TYPO3 pixx.io Extension

## Repository Overview

This repository contains a TYPO3 extension that integrates pixx.io Digital Asset Management (DAM) into TYPO3. The extension allows users to select, sync, and manage assets from their pixx.io mediaspace directly within TYPO3.

### Key Information
- **Extension Key**: `pixxio_extension`
- **Package Name**: `pixxio/pixxio-extension`
- **Current Version**: 3.2.0
- **Primary Language**: PHP
- **Framework**: TYPO3 CMS Extension

## Branch Structure

This repository maintains two main branches for different TYPO3 versions:

- **`main` branch**: TYPO3 v13 compatibility (current development)
- **`master` branch**: TYPO3 v12 compatibility (legacy support)

Always work on the appropriate branch based on the TYPO3 version being targeted.

## Architecture & Code Organization

### Directory Structure
```
Classes/
├── Command/           # CLI commands (e.g., sync command)
├── Controller/        # Backend controllers for file management
├── Domain/Model/      # Domain models and data structures
├── EventListener/     # TYPO3 event listeners
└── Utility/          # Helper utilities and configuration management

Configuration/
├── Backend/          # Backend module configuration
├── TCA/             # Table Configuration Array definitions
├── Services.yaml    # Dependency injection configuration
└── *.php           # Various TYPO3 configurations

Tests/
├── Unit/            # PHPUnit unit tests
└── Functional/      # TYPO3 functional tests

Resources/
├── Public/          # Public assets (CSS, JS, images)
└── Private/         # Private templates and resources
```

### Key Components

1. **Sync Functionality**: Core feature for synchronizing assets from pixx.io
   - `Classes/Command/SyncCommand.php` - CLI command for manual/scheduled sync
   - Scheduler integration for automated syncing

2. **File Management**: Backend interface for asset selection
   - `Classes/Controller/FilesController.php` - Main controller for file operations
   - Integration with TYPO3 file abstraction layer (FAL)

3. **Metadata Integration**: Seamless metadata mapping
   - Works with `typo3/cms-filemetadata` extension
   - Maps pixx.io metadata fields to TYPO3 equivalents

4. **Configuration Management**: Extension configuration handling
   - `Classes/Utility/ConfigurationUtility.php` - Configuration utilities
   - Extension configuration via TYPO3 admin interface

## Development Guidelines

### TYPO3 Extension Standards
- Follow TYPO3 Coding Guidelines and PSR-12 standards
- Use TYPO3 dependency injection container (Services.yaml)
- Implement proper TCA configuration for database tables
- Use TYPO3 event system for extending functionality

### Code Patterns
- **Controllers**: Extend `ActionController` for backend modules
- **Commands**: Implement `Command` interface for CLI functionality
- **Domain Models**: Use Repository/Model pattern for data access
- **Event Listeners**: Implement appropriate event listener interfaces

### Dependencies
- **Required**: `typo3/cms-core ^13.4`, `ext-curl`
- **Development**: `typo3/testing-framework 9.2.0`
- **Optional Integration**: `typo3/cms-filemetadata`

## Development Setup

### Local Development with ddev
```bash
# Follow TYPO3 ddev setup guide
# Create local_packages directory for development
mkdir ./local_packages
cd ./local_packages
git clone https://github.com/pixx-io/typo3.git pixxio_extension

# Add to composer.json repositories section:
"repositories": [
    {
        "type": "path",
        "url": "./local_packages/*"
    }
]

# Install via composer
composer require pixxio/pixxio-extension
```

### Testing
- **Framework**: TYPO3 Testing Framework 9.2.0
- **Unit Tests**: Located in `Tests/Unit/`
- **Functional Tests**: Located in `Tests/Functional/`
- **Run Tests**: Use ddev or TYPO3 testing commands

### CLI Commands
```bash
# Manual sync command
ddev typo3 pixxio:sync

# Database schema updates
ddev typo3 database:updateschema
```

## Integration Points

### pixx.io API Integration
- Uses refresh token authentication
- Supports proxy connections
- RESTful API communication for asset retrieval and metadata sync

### TYPO3 Core Integration
- **File Abstraction Layer (FAL)**: For file management
- **Scheduler**: For automated sync tasks
- **Backend Forms**: For configuration and file selection
- **Event System**: For extending and hooking into core functionality

### filemetadata Extension Integration
Maps pixx.io metadata to TYPO3 fields:
- Title/Titel → Download Name
- Description/Beschreibung → Description and Caption
- Keywords/Schlagwörter → Keywords
- Creator/Ersteller → Creator
- GPS coordinates → GPS Latitude/Longitude
- And more comprehensive metadata mapping

## Configuration

### Extension Configuration Categories
1. **Basic**: URL, refresh token, file storage settings
2. **Metadata**: Alt text and metadata field mappings
3. **Sync**: Delete, update, and limit behaviors
4. **Proxy**: Proxy connection settings

### Backend User Permissions
- Button visibility can be controlled via user settings
- `setup.override.show_pixxioUpload=0` hides selection button

## Best Practices for Contributors

### When Adding Features
- Ensure compatibility with both standalone and filemetadata integration
- Add appropriate unit and functional tests
- Follow TYPO3 extension development best practices
- Consider proxy and authentication scenarios

### When Modifying Sync Logic
- Test with various pixx.io metadata configurations
- Ensure proper error handling for network issues
- Consider performance implications for large media libraries
- Maintain backward compatibility

### Code Quality
- Use TYPO3 coding standards
- Add proper PHP type hints and return types
- Document complex business logic
- Follow dependency injection patterns

## Release Process

1. Update version in `ext_emconf.php` and `composer.json`
2. Update `CHANGELOG.md` with new features/fixes
3. Run `composer install` to update lock file
4. Create and push new version tag
5. Update on Packagist and TYPO3 Extension Repository (TER)

## Common Issues & Solutions

### File Transfer Problems
- Ensure `allow_url_fopen = On` in php.ini as fallback
- Check curl extension availability
- Verify proxy configuration if applicable

### Metadata Sync Issues
- Verify filemetadata extension is installed if using advanced metadata
- Check pixx.io metadata field names match configuration
- Ensure proper field mappings in extension configuration

This extension bridges TYPO3 and pixx.io ecosystems, so always consider both sides when making changes.