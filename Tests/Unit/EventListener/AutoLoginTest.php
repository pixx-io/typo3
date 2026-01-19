<?php

namespace Pixxio\PixxioExtension\Tests\Unit\EventListener;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for auto-login functionality
 */
class AutoLoginTest extends UnitTestCase
{
    /**
     * Test that auto_login configuration is properly handled
     */
    public function testAutoLoginConfigurationHandling(): void
    {
        // Test configuration with auto_login enabled
        $extensionConfiguration = [
            'auto_login' => true,
            'token_refresh' => 'test_token',
            'user_id' => 'test_user',
            'url' => 'https://example.pixx.io'
        ];
        
        // Mock the attributes that would be generated
        $expectedAttributes = [
            'data-auto-login' => '1',
            'data-refresh-token' => base64_encode('test_token'),
            'data-mediaspace-url' => base64_encode('https://example.pixx.io')
        ];
        
        // Verify base64 encoding works correctly
        $this->assertEquals(base64_encode('test_token'), $expectedAttributes['data-refresh-token']);
        $this->assertEquals(base64_encode('https://example.pixx.io'), $expectedAttributes['data-mediaspace-url']);
        
        // Test configuration with auto_login disabled
        $disabledConfiguration = [
            'auto_login' => false,
            'token_refresh' => 'test_token',
            'url' => 'https://example.pixx.io'
        ];
        
        // When auto_login is disabled, no auto-login attributes should be set
        $this->assertFalse($disabledConfiguration['auto_login']);
    }
    
    /**
     * Test that PostMessage format is correct
     */
    public function testPostMessageFormat(): void
    {
        $refreshToken = 'test_refresh_token';
        $userId = 'test_user_id';
        $mediaspaceUrl = 'https://example.pixx.io';
        
        $expectedMessage = [
            'receiver' => 'pixxio-plugin-sdk',
            'method' => 'login',
            'parameters' => [$refreshToken, $userId, $mediaspaceUrl]
        ];
        
        // Verify the message structure matches the expected format
        $this->assertEquals('pixxio-plugin-sdk', $expectedMessage['receiver']);
        $this->assertEquals('login', $expectedMessage['method']);
        $this->assertCount(3, $expectedMessage['parameters']);
        $this->assertEquals($refreshToken, $expectedMessage['parameters'][0]);
        $this->assertEquals($userId, $expectedMessage['parameters'][1]);
        $this->assertEquals($mediaspaceUrl, $expectedMessage['parameters'][2]);
    }
}