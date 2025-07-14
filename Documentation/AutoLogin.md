# Auto Login Feature for TYPO3 v13

## Overview

The Auto Login feature allows seamless authentication for the Pixxio Plugin SDK iframe using existing sync credentials configured in the extension settings. This feature was implemented for TYPO3 v12 in PR #38 and is now available for TYPO3 v13.

## How It Works

### 1. Configuration

Administrators can enable auto-login in the extension configuration:

- **pixx.io URL** (sync.url): Your Pixxio mediaspace URL
- **User ID** (sync.user_id): User ID or email for sync access
- **Refresh Token** (sync.token_refresh): Refresh token for API access
- **Enable automatic login**: Checkbox to enable auto-login feature

### 2. Backend Implementation

When auto-login is enabled, the backend controllers add base64-encoded credentials as data attributes to the Pixxio SDK buttons:

```php
// Auto-login data attributes (from FilesControlContainer.php and InlineControlContainer.php)
if (isset($extensionConfiguration['auto_login']) && $extensionConfiguration['auto_login']) {
    $attributes['data-auto-login'] = '1';
    $attributes['data-refresh-token'] = base64_encode($extensionConfiguration['token_refresh']);
    $attributes['data-user-id'] = base64_encode($extensionConfiguration['user_id']);
    $attributes['data-mediaspace-url'] = base64_encode($extensionConfiguration['url']);
}
```

### 3. Frontend Implementation

The JavaScript SDK listens for the `onSdkReady` PostMessage from the Plugin SDK and automatically sends login credentials:

```javascript
// Auto-login flow (from ScriptSDK.js)
function handleSdkReady(messageEvent) {
    if (targetButton.getAttribute("data-auto-login") === "1") {
        const refreshToken = atob(targetButton.getAttribute("data-refresh-token"));
        const userId = atob(targetButton.getAttribute("data-user-id"));
        const mediaspaceUrl = atob(targetButton.getAttribute("data-mediaspace-url"));
        
        const loginMessage = {
            receiver: "pixxio-plugin-sdk",
            method: "login",
            parameters: [refreshToken, userId, mediaspaceUrl]
        };
        
        targetIframe.contentWindow.postMessage(loginMessage, "https://plugin.pixx.io");
    }
}
```

## TYPO3 v13 Compatibility Changes

### Version Constraints
- Updated `ext_emconf.php`: `typo3` constraint now `10.4.0-13.99.99`
- Updated `composer.json`: Added `^13.0` to TYPO3 core requirements
- Updated testing framework to support v13

### JavaScript Module Loading
- Uses modern `JavaScriptModuleInstruction::create()` pattern (already v13 compatible)
- Fixed `MessageUtility.send()` call in ScriptSDK_v11.js for v13 compatibility

## Security Considerations

- Credentials are base64-encoded for transport (not for security)
- PostMessages are only sent to verified `https://plugin.pixx.io` origin
- Feature is opt-in and requires backend administrator configuration
- Only users with TYPO3 backend access can use this feature

## Testing

The implementation includes comprehensive tests:

- **AutoLoginTest.php**: Unit tests for configuration handling and PostMessage format
- **Manual verification**: Credential encoding/decoding and message flow

## Backward Compatibility

All changes are fully backward compatible. Existing installations will continue to work unchanged, and the new auto-login feature is disabled by default.

## Usage Instructions

1. Configure your Pixxio sync credentials in the extension configuration
2. Enable the "Auto-login with the sync access data in the image picker" checkbox
3. Save the configuration
4. Users will now be automatically logged into the Pixxio interface when opening the SDK

## File Changes for v13

The following files contain the Auto Login implementation:

- `ext_conf_template.txt`: Configuration option
- `Classes/Backend/InlineControlContainer.php`: Backend data attributes for inline elements
- `Classes/Controller/FilesControlContainer.php`: Backend data attributes for file selectors
- `Resources/Public/JavaScript/ScriptSDK.js`: Frontend ES module implementation
- `Resources/Public/JavaScript/ScriptSDK_v11.js`: Frontend AMD module implementation (v13 compatible)
- `Tests/Unit/Backend/AutoLoginTest.php`: Unit tests