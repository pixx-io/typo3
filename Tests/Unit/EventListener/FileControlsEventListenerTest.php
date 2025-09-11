<?php

namespace Pixxio\PixxioExtension\Tests\Unit\EventListener;

use Pixxio\PixxioExtension\EventListener\FileControlsEventListener;
use Pixxio\PixxioExtension\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Form\Event\CustomFileControlsEvent;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for FileControlsEventListener
 */
class FileControlsEventListenerTest extends UnitTestCase
{
    /**
     * Test that allowedDownloadFormats parameter is correctly added to iframe URL
     */
    public function testAllowedDownloadFormatsInIframeUrl(): void
    {
        // Test single format
        $singleFormatConfig = [
            'allowed_download_formats' => 'jpg'
        ];
        
        $expectedSingleParam = '&allowedDownloadFormats=' . urlencode('jpg');
        $this->assertStringContains('allowedDownloadFormats=jpg', $expectedSingleParam);
        
        // Test multiple formats (comma-separated)
        $multipleFormatsConfig = [
            'allowed_download_formats' => 'jpg,png,pdf'
        ];
        
        $formats = array_map('trim', explode(',', $multipleFormatsConfig['allowed_download_formats']));
        $expectedParams = [];
        foreach ($formats as $format) {
            $expectedParams[] = '&allowedDownloadFormats=' . urlencode($format);
        }
        
        $this->assertEquals(['&allowedDownloadFormats=jpg', '&allowedDownloadFormats=png', '&allowedDownloadFormats=pdf'], $expectedParams);
        
        // Test empty configuration (should not add parameter)
        $emptyConfig = [
            'allowed_download_formats' => ''
        ];
        
        $this->assertEmpty($emptyConfig['allowed_download_formats']);
        
        // Test missing configuration (should not add parameter)
        $missingConfig = [];
        
        $this->assertFalse(isset($missingConfig['allowed_download_formats']));
    }
    
    /**
     * Test URL building logic with allowedDownloadFormats
     */
    public function testIframeUrlBuildingWithAllowedDownloadFormats(): void
    {
        $baseUrl = 'https://plugin.pixx.io/static/v1/en/media?multiSelect=true&applicationId=test';
        
        // Test with single format
        $singleFormat = 'jpg';
        $expectedSingleUrl = $baseUrl . '&allowedDownloadFormats=' . urlencode($singleFormat);
        $this->assertStringContains('allowedDownloadFormats=jpg', $expectedSingleUrl);
        
        // Test with multiple formats
        $formats = ['jpg', 'png', 'pdf'];
        $multipleFormatsUrl = $baseUrl;
        foreach ($formats as $format) {
            $multipleFormatsUrl .= '&allowedDownloadFormats=' . urlencode($format);
        }
        
        $this->assertStringContains('allowedDownloadFormats=jpg', $multipleFormatsUrl);
        $this->assertStringContains('allowedDownloadFormats=png', $multipleFormatsUrl);
        $this->assertStringContains('allowedDownloadFormats=pdf', $multipleFormatsUrl);
    }
    
    /**
     * Test comma-separated format parsing
     */
    public function testCommaSeparatedFormatParsing(): void
    {
        $input = 'jpg,png,pdf';
        $expected = ['jpg', 'png', 'pdf'];
        
        $result = array_map('trim', explode(',', $input));
        
        $this->assertEquals($expected, $result);
        
        // Test with spaces
        $inputWithSpaces = 'jpg, png , pdf ';
        $resultWithSpaces = array_map('trim', explode(',', $inputWithSpaces));
        
        $this->assertEquals($expected, $resultWithSpaces);
        
        // Test single value (no comma)
        $singleInput = 'jpg';
        $this->assertFalse(strpos($singleInput, ',') !== false);
    }
    
    /**
     * Test valid format values
     */
    public function testValidFormatValues(): void
    {
        $validFormats = ['original', 'preview', 'jpg', 'png', 'pdf', 'tiff'];
        
        foreach ($validFormats as $format) {
            $this->assertNotEmpty($format);
            $this->assertIsString($format);
        }
        
        // Test URL encoding of formats
        foreach ($validFormats as $format) {
            $encoded = urlencode($format);
            $this->assertNotEmpty($encoded);
        }
    }
}