<?php

namespace Pixxio\PixxioExtension\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationUtility
{
    const EXTENSION = 'pixxio_extension';

    public static function getExtensionConfiguration(): array
    {
        $extensionConfiguration = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get('pixxio_extension');

        if (array_key_exists('auto_login', $extensionConfiguration)) {
            $extensionConfiguration['auto_login'] = filter_var(
                $extensionConfiguration['auto_login'],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) ?? false;
        }

        if (isset($extensionConfiguration['url'])) {
            $extensionConfiguration['url'] = static::getCompleteUrl($extensionConfiguration['url']);
        }

        return $extensionConfiguration;
    }

    public static function getConfigurationForDatabaseRow(array $databaseRow): array
    {
        $extensionConfiguration = static::getExtensionConfiguration();
        $pageId = (int)($databaseRow['pid'] ?? 0);
        if ($pageId <= 0) {
            return $extensionConfiguration;
        }

        try {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
        } catch (SiteNotFoundException) {
            return $extensionConfiguration;
        }

        $siteSettings = $site->getSettings();
        $extensionConfiguration = static::applySiteSettingOverrides($extensionConfiguration, $siteSettings);

        if (isset($extensionConfiguration['url'])) {
            $extensionConfiguration['url'] = static::getCompleteUrl((string)$extensionConfiguration['url']);
        }

        return $extensionConfiguration;
    }

    public static function getConfigurationForMediaspace(string $mediaspaceUrl): array
    {
        $extensionConfiguration = static::getExtensionConfiguration();
        $mediaspaceHost = static::extractHost($mediaspaceUrl);
        if ($mediaspaceHost === '') {
            return $extensionConfiguration;
        }

        foreach (GeneralUtility::makeInstance(SiteFinder::class)->getAllSites() as $site) {
            $siteSettings = $site->getSettings();
            if (!$siteSettings->has('pixxio.url')) {
                continue;
            }

            $siteHost = static::extractHost((string)$siteSettings->get('pixxio.url'));
            if ($siteHost === '' || strtolower($siteHost) !== strtolower($mediaspaceHost)) {
                continue;
            }

            $extensionConfiguration = static::applySiteSettingOverrides($extensionConfiguration, $siteSettings);

            if (isset($extensionConfiguration['url'])) {
                $extensionConfiguration['url'] = static::getCompleteUrl((string)$extensionConfiguration['url']);
            }

            return $extensionConfiguration;
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

    private static function extractHost(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $parsedUrl = parse_url($url);
        if (!is_array($parsedUrl) || !isset($parsedUrl['host'])) {
            $parsedUrl = parse_url('https://' . ltrim($url, '/'));
        }

        return is_array($parsedUrl) && isset($parsedUrl['host']) ? (string)$parsedUrl['host'] : '';
    }

    private static function applySiteSettingOverrides(array $extensionConfiguration, SiteSettings $siteSettings): array
    {
        foreach ($extensionConfiguration as $configurationKey => $currentValue) {
            $siteSettingKey = static::findSiteSettingKey($siteSettings, $configurationKey);
            if ($siteSettingKey === null) {
                continue;
            }

            $siteValue = $siteSettings->get($siteSettingKey);

            if (is_string($currentValue)) {
                $normalizedValue = trim((string)$siteValue);
                if ($normalizedValue === '') {
                    continue;
                }
                $extensionConfiguration[$configurationKey] = $normalizedValue;
                continue;
            }

            if (is_bool($currentValue)) {
                $extensionConfiguration[$configurationKey] = (bool)$siteValue;
                continue;
            }

            if (is_int($currentValue)) {
                $extensionConfiguration[$configurationKey] = (int)$siteValue;
                continue;
            }

            if (is_float($currentValue)) {
                $extensionConfiguration[$configurationKey] = (float)$siteValue;
                continue;
            }

            $extensionConfiguration[$configurationKey] = $siteValue;
        }

        return $extensionConfiguration;
    }

    private static function findSiteSettingKey(SiteSettings $siteSettings, string $configurationKey): ?string
    {
        $candidate = 'pixxio.' . $configurationKey;

        return $siteSettings->has($candidate) ? $candidate : null;
    }
}
