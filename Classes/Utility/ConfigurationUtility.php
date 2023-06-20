<?php

namespace Pixxio\PixxioExtension\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationUtility
{
    const EXTENSION = 'pixxio_extension';

    public static function getExtensionConfiguration(): array
    {
        $extensionConfiguration = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get('pixxio_extension');

        if (isset($extensionConfiguration['url'])) {
            $extensionConfiguration['url'] = static::getCompleteUrl($extensionConfiguration['url']);
        }

        return $extensionConfiguration;
    }

    public static function getCompleteUrl(string $url): string
    {
        if ($url === '') {
            return $url;
        }

        // Add https
        $urlParts = parse_url($url);
        if (empty($urlParts['scheme'])) {
            $url = 'https://' . $url;
            $urlParts = parse_url($url);
        }

        // Add leading slash if necessary
        if (!empty($urlParts['path'])) {
            $url = rtrim($url, '/') . '/';
        }

        return $url;
    }
}
