<?php

namespace Pixxio\PixxioExtension\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility as CoreConfigurationUtility;

class ConfigurationUtility
{
    const EXTENSION = 'pixxio_extension';

    public static function getExtensionConfiguration(): array
    {
        $extensionConfiguration = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get('pixxio_extension');

        /*

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        if (class_exists(CoreConfigurationUtility::class)) {
            $configuration = $objectManager->get(CoreConfigurationUtility::class)->getCurrentConfiguration('pixxio_extension');

           
            $extensionConfiguration = [];
            foreach ($configuration as $key => $value) {
                $extensionConfiguration[$key] = $value['value'];
            }
        } else {
            $extensionConfiguration = $objectManager->get(ExtensionConfiguration::class)->get('pixxio_extension');
        }
        */

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
