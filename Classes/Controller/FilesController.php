<?php

declare(strict_types=1);

namespace Pixxio\PixxioExtension\Controller;

use Pixxio\PixxioExtension\Domain\Model\LicenceRelease;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Helper\Table;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Core\Database\Connection;


class FilesController extends ActionController
{

    private $metadataMapping = [
        'location_city' => 'City',
        'location_country' => 'Country',
        'location_region' => 'Region',
        'copyright' => 'CopyrightNotice',
        'creator_tool' => 'Model',
        'source' => 'Source',
        'color_space' => 'ColorSpace',
        'publisher' => 'Publisher'
    ];

    protected $extensionConfiguration;
    private $applikationKey = 'ghx8F66X3ix4AJ0VmS0DE8sx7';
    private $accessToken = '';

    /** @var RequestFactory */
    private $requestFactory;

    public function __construct()
    {
        $this->extensionConfiguration = \Pixxio\PixxioExtension\Utility\ConfigurationUtility::getExtensionConfiguration();
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
    }

    public function hasExt($key)
    {
        return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($key);
    }

    public function getJSONRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        if (false === strpos($request->getHeaderLine('Content-Type'), 'application/json')) {
            return $request;
        }
        // TODO: Implement broken json handling
        return $request->withParsedBody(json_decode($request->getBody()->getContents()));
    }

    public function selectedFilesAction(ServerRequestInterface $request, ResponseInterface $response = null): ResponseInterface
    {
        // get files
        $files = $this->getJSONRequest($request)->getParsedBody()->files;

        // for TYPO3 10.x
        if (!is_object($response)) {
            $response = new JsonResponse();
        }

        // pull files from pixx.io
        if ($files) {
            $importedFiles = $this->pullFiles($files);
            $response->getBody()->write(json_encode(['files' => $importedFiles]));
        }
        return $response;
    }

    private function throwError($message, $num)
    {
        throw new \RuntimeException(
            $message,
            \TYPO3_PIXXIO_EXT_NUM + $num
        );
    }

    private function uploadPath()
    {
        try {
            $storage = $this->getStorage();

            $storageBasePath = $storage->getConfiguration()['basePath'];

            // correct beginning and trailing slashes
            if (substr($storageBasePath, -1) != '/') {
                $storageBasePath = $storageBasePath . '/';
            }
            if (substr($storageBasePath, 0, 1) == '/') {
                $storageBasePath = substr($storageBasePath, 1);
            }

            if ($this->extensionConfiguration['subfolder']) {
                $storageBasePath .= $this->extensionConfiguration['subfolder'];
            }

            if (substr($storageBasePath, -1) != '/') {
                $storageBasePath = $storageBasePath . '/';
            }

            return GeneralUtility::getFileAbsFileName($storageBasePath);
        } catch (\Exception $error) {
            $this->throwError($error->getMessage(), $error->getCode());
        }
    }

    private function getResponseFields()
    {
        $responseFields = [
            'metadataFields',
            'id',
            'subject',
            'description',
            'originalFileURL',
            'previewFileURL',
            'licenseReleases',
        ];

        if ($this->hasExt('filemetadata')) {
            $responseFields = array_merge($responseFields, [
                'location',
                'keywords',
                'rating',
                'createDate',
                'modifyDate',
                'colorspace',
                'creator'
            ]);
        }

        return $responseFields;
    }

    private function getProxySettings(&$additionalFields)
    {
        if ($this->extensionConfiguration['use_proxy'] && filter_var($this->extensionConfiguration['proxy_connection'], FILTER_VALIDATE_URL)) {
            $proxy = [];
            $proxy[strpos($this->extensionConfiguration['proxy_connection'], 'https') === 0 ? 'https' : 'http'] = $this->extensionConfiguration['proxy_connection'];
            $additionalFields['proxy'] = $proxy;
        }
    }

    private function pixxioFiles($fileIds)
    {
        if (count($fileIds) === 0) {
            return [];
        }

        try {
            $additionalOptions = [
                'headers' => [
                    'Cache-Control' => 'no-cache',
                    'Authorization' => 'Key ' . $this->accessToken
                ],
                'allow_redirects' => false,
            ];

            $this->getProxySettings($additionalOptions);

            $maxSyncItems = $this->getMaxSyncItems();

            $response = $this->requestFactory->request($this->extensionConfiguration['url'] . '/gobackend/files?' . http_build_query([
                'pageSize' => $maxSyncItems,
                'page' => 1,
                'responseFields' => json_encode($this->getResponseFields()),
                'licenseReleasesResponseFields' => json_encode(['id', 'name', 'license', 'showWarningMessage']),
                'filter' => json_encode([
                    'filterType' => 'files',
                    'fileIDs' => $fileIds
                ])
            ]), 'GET', $additionalOptions);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents());
                return $data->success ? $data->files : [];
            }

            return [];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $responseBody = $response->getBody()->getContents();

                $responseData = json_decode($responseBody);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->throwError('Invalid JSON in error response: ' . json_last_error_msg(), 1);
                }
                if ($responseData && isset($responseData->errormessage)) {
                    $errorMessage = $responseData->errormessage;
                    $this->throwError($errorMessage, 1);
                }
            }

            $this->throwError($e->getMessage(), 1);
        } catch (\Exception $error) {
            $this->throwError($error->getMessage(), 1);
        }
    }

    private function getMaxSyncItems()
    {
        $limit = isset($this->extensionConfiguration['limit'])
            ? (int)$this->extensionConfiguration['limit']
            : 20;

        if ($limit < 1) {
            $limit = 20;
        } elseif ($limit > 500) {
            $limit = 500;
        }

        return $limit;
    }

    private function pixxioFile($fileId)
    {
        $additionalOptions = [
            'headers' => [
                'Cache-Control' => 'no-cache',
                'Authorization' => 'Key ' . $this->accessToken
            ],
            'allow_redirects' => false
        ];

        $this->getProxySettings($additionalOptions);

        $response = $this->requestFactory->request($this->extensionConfiguration['url'] . '/gobackend/files/' . $fileId . '?' . http_build_query([
            'responseFields' => json_encode($this->getResponseFields())
        ]), 'GET', $additionalOptions);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody()->getContents());
            return $data->success ? $data->file : false;
        }

        return null;
    }

    private function pixxioAuth()
    {
        if ($this->extensionConfiguration['url'] == "") {
            $this->throwError('Authentication to pixx.io failed. Please check pixx.io URL in your extension configuration', 9);
            return false;
        }
        if ($this->extensionConfiguration['token_refresh'] == "") {
            $this->throwError('Authentication to pixx.io failed. Please check pixx.io refresh token in your extension configuration', 10);
            return false;
        }

        $additionalOptions = [
            'headers' => ['Cache-Control' => 'no-cache'],
            'allow_redirects' => false,
            'form_params' => [
                'applicationKey' => $this->applikationKey,
                'refreshToken' => $this->extensionConfiguration['token_refresh'],
            ]
        ];

        $this->getProxySettings($additionalOptions);

        $response = $this->requestFactory->request($this->extensionConfiguration['url'] . '/gobackend/accessToken', 'POST', $additionalOptions);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody()->getContents());
            return $data->success ? $data->accessToken : false;
        }

        $this->throwError('Authentication to pixx.io failed. Please check your configuration and your given refresh token.', 2);
        return false;
    }

    private function getMetadataField($file, $name)
    {
        $value = '';
        foreach ($file->metadataFields as $metadata) {
            if ($metadata->name === $name) {
                $value = $metadata->value;
                break;
            }
        }
        return $value;
    }

    private function pixxioCheckExistence($fileIds)
    {
        try {
            $additionalOptions = [
                'headers' => [
                    'Cache-Control' => 'no-cache',
                    'Authorization' => 'Key ' . $this->accessToken
                ],
                'allow_redirects' => false
            ];

            $this->getProxySettings($additionalOptions);

            $response = $this->requestFactory->request($this->extensionConfiguration['url'] . '/gobackend/files/existence?' . http_build_query([
                'ids' => json_encode($fileIds),
                'responseFields' => json_encode([
                    'id',
                    'isMainVersion',
                    'mainVersion'
                ])
            ]), 'GET', $additionalOptions);

            $temp = [];
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents());
                if ($data->success) {
                    $foundIds = [];
                    foreach ($data->files as $f) {
                        $isMainVersion = isset($f->isMainVersion) ? $f->isMainVersion : null;

                        if ($isMainVersion === true) {
                            $temp[] = [
                                'oldId' => $f->id,
                                'newId' => $f->id
                            ];
                            $foundIds[] = $f->id;
                        } elseif ($isMainVersion === false) {
                            $temp[] = [
                                'oldId' => $f->id,
                                'newId' => $f->mainVersion
                            ];
                            $foundIds[] = $f->id;
                        } else {
                            // The file was found but we cannot determine if it's the main version
                            // We keep the file (don't mark for deletion)
                            $temp[] = [
                                'oldId' => $f->id,
                                'newId' => $f->id
                            ];
                            $foundIds[] = $f->id;
                        }
                    }

                    // Only add IDs that were not present in the response at all (e.g. error case)
                    foreach ($fileIds as $id) {
                        // In case of doubt, keep the file
                        if (!in_array($id, $foundIds, true)) {
                            $temp[] = [
                                'oldId' => $id,
                                'newId' => null
                            ];
                        }
                    }
                }
            }
            return $temp;
        } catch (\Exception $error) {
            $this->throwError($error->getMessage(), 3);
        }
    }

    public function syncAction($io): bool
    {
        // check if extension configuration is set to update/delete media by sync command
        if (!(
            $this->extensionConfiguration['delete'] ||
            $this->extensionConfiguration['update'] ||
            $this->extensionConfiguration['update_metadata']
        )) {
            $io->writeln('Please update extension configuration to enable update/update_metadata/deletion of media by sync command');
            return true;
        }

        $metadata = GeneralUtility::makeInstance(MetaDataRepository::class);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->getRestrictions()->removeAll();

        $maxSyncItems = $this->getMaxSyncItems();

        $files = $queryBuilder
            ->select('*')
            ->from('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->gt('pixxio_file_id', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
            )
            ->orderBy('pixxio_last_sync_stamp', 'ASC')
            ->setMaxResults($maxSyncItems)
            ->leftJoin(
                'sys_file_metadata',
                'sys_file',
                'f',
                $queryBuilder->expr()->eq(
                    'sys_file_metadata.file',
                    $queryBuilder->quoteIdentifier('f.uid')
                )
            );

        if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() < 11) {
            $files = $files->execute()->fetchAll();
        } else {
            $files = $files->executeQuery()->fetchAllAssociative();
        }

        $io->writeln('Got files from database');

        $fileIds = array_map(function ($file) {
            return $file['pixxio_file_id'];
        }, $files);

        $io->writeln('Mapped files from database to pixx.io IDs');

        if (empty($fileIds)) {
            $io->writeln('No pixx.io files found');
            return true;
        }

        $io->writeln('Authenticate to pixx.io');
        $this->accessToken = $this->pixxioAuth();
        $io->writeln('Authenticated');
        
        $io->writeln('Check existence and version of ' . count($fileIds) . ' files on pixx.io');
        $io->writeln('');

        // Display files as table
        $tableRows = [];
        foreach ($files as $file) {
            $tableRows[] = [
                $file['uid'],
                $file['pixxio_file_id'],
                $file['identifier']
            ];
        }
        
        $table = new Table($io);
        $table
            ->setHeaders(['TYPO3 UID', 'pixx.io ID', 'Identifier'])
            ->setRows($tableRows);
        $table->render();

        $io->writeln('');

        $pixxioDiff = $this->pixxioCheckExistence($fileIds);

        if (!is_array($pixxioDiff)) {
            $this->throwError('Something went wrong during the check of existing pixx.io assets', 6);
        }

        $pixxioIdsToDelete = array_map(function ($ids) {
            return $ids['oldId'];
        }, array_filter($pixxioDiff, function ($diff) {
            return !$diff['newId'];
        }));

        $pixxioIdsToUpdate = array_map(function ($ids) {
            return $ids['oldId'];
        }, array_filter($pixxioDiff, function ($diff) {
            return $diff['newId'] !== null && $diff['newId'] !== $diff['oldId'];
        }));

        // do the sync
        //check if file exists and update their versions
        // delete files that aren't existing in pixx.io
        $io->writeln('Files to delete: ' . count($pixxioIdsToDelete));
        $io->writeln('Files with a new version: ' . count($pixxioIdsToUpdate));

        foreach ($files as $index => $file) {
            // delete files
            if (in_array($file['pixxio_file_id'], $pixxioIdsToDelete)) {
                if ($this->extensionConfiguration['delete']) {
                    $io->writeln('File deleted: ' . $file['identifier']);
                    $storage = $this->getStorage();
                    $storage->deleteFile($storage->getFileByIdentifier($file['identifier']));
                    unset($files[$index]);
                    foreach ($fileIds as $key => $id) {
                        if ($id === $file['pixxio_file_id']) {
                            unset($fileIds[$key]);
                            break;
                        }
                    }
                    $fileIds = array_values($fileIds);
                } else {
                    $io->writeln('File which should be deleted, but extension configuration is set to not delete files: ' . $file['pixxio_file_id']);
                }
            }

            // update to new version
            if (in_array($file['pixxio_file_id'], $pixxioIdsToUpdate)) {
                if ($this->extensionConfiguration['update']) {
                    $newId = 0;
                    foreach ($pixxioDiff as $diff) {
                        if ($diff['oldId'] === $file['pixxio_file_id']) {
                            $newId = $diff['newId'];
                            break;
                        }
                    }
                    if ($newId) {
                        $pixxioFile = $this->pixxioFile($newId);
                        $absFileIdentifier = $this->saveFile($file['name'], $pixxioFile->originalFileURL, (bool)$file['pixxio_is_direct_link']);
                        $storage = $this->getStorage();
                        $storage->replaceFile($storage->getFileByIdentifier($file['identifier']), $absFileIdentifier);
                        $io->writeln('File updated: ' . $file['identifier']);
                        foreach ($fileIds as $key => $id) {
                            if ($id === $file['pixxio_file_id']) {
                                $fileIds[$key] = $newId;
                                break;
                            }
                        }

                        $files[$index]['pixxio_file_id'] = $newId;
                    }
                } else {
                    $io->writeln('File which should be updated, but extension configuration is set to not update files: ' . $file['pixxio_file_id']);
                }
            }
        }

        if ($this->extensionConfiguration['update_metadata']) {
            $files = array_values($files);

            $fileIdsWithoutDeletedFiles = array_values(array_filter($fileIds, function ($id) use ($pixxioIdsToDelete) {
                return !in_array($id, $pixxioIdsToDelete);
            }));

            $io->writeln('Start to sync metadata: ' . join(', ', $fileIdsWithoutDeletedFiles));
            $pixxioFiles = $this->pixxioFiles($fileIdsWithoutDeletedFiles);

            foreach ($files as $file) {
                // set meta data
                $pixxioFile = array_values(array_filter($pixxioFiles, function ($pFile) use ($file) {
                    return $pFile->id === $file['pixxio_file_id'];
                }));

                if (!$pixxioFile || !$pixxioFile[0]) {
                    // have to delete file?!
                    continue;
                }

                $pixxioFile = $pixxioFile[0];

                $additionalFields = array(
                    'title' => $pixxioFile->subject,
                    'description' => $pixxioFile->description,
                    'alternative' => $this->getMetadataField($pixxioFile, $this->extensionConfiguration['alt_text'] ?: 'Alt Text (Accessibility)'),
                    'pixxio_file_id' => $pixxioFile->id,
                    'pixxio_last_sync_stamp' => time()
                );

                if ($this->hasExt('filemetadata')) {
                    $additionalFields = array_merge($additionalFields, $this->getMetadataWithFilemetadataExt($pixxioFile));
                }

                $additionalFields['tx_pixxioextension_licensereleases'] = $this->licensereleasesSync($pixxioFile, $file);

                $io->writeln('Update metadata for ' . $pixxioFile->id);
                $metadata->update($file['uid'], $additionalFields);
            }
        }

        return true;
    }

    protected function licensereleasesSync($pixxioFile, array $file): string
    {
        $licenseReleaseUids = [];

        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tx_pixxioextension_domain_model_licenserelease');

        // Pixxio HAS license releases
        if (!empty($pixxioFile->licenseReleases)) {
            // 1) Existing relations from TYPO3 (ordered list of UIDs as stored in metadata)
            $existingLicenseReleaseUids = [];
            if (!empty($file['tx_pixxioextension_licensereleases'])) {
                $existingLicenseReleaseUids = GeneralUtility::intExplode(
                    ',',
                    (string)$file['tx_pixxioextension_licensereleases'],
                    true
                );
            }

            $index = 0;
            // 2) For each Pixxio license, use the UID at the same position if it exists; otherwise insert a new record
            foreach ($pixxioFile->licenseReleases as $licenseRelease) {
                $insertData = [];

                if (isset($licenseRelease->licenseRelease)) {
                    if (isset($licenseRelease->licenseRelease->license->provider)) {
                        $insertData['license_provider'] = $licenseRelease->licenseRelease->license->provider;
                    }

                    if (isset($licenseRelease->licenseRelease->name)) {
                        $insertData['name'] = $licenseRelease->licenseRelease->name;
                    }

                    if (isset($licenseRelease->licenseRelease->showWarningMessage)) {
                        $insertData['show_warning_message'] = (bool)$licenseRelease->licenseRelease->showWarningMessage;
                    }

                    if (isset($licenseRelease->licenseRelease->warningMessage)) {
                        $insertData['warning_message'] = $licenseRelease->licenseRelease->warningMessage;
                    }
                }

                if (isset($licenseRelease->expires)) {
                    $insertData['expires'] = $licenseRelease->expires;
                }

                // existing UID at this position? -> update
                if (!empty($existingLicenseReleaseUids[$index])) {
                    $uid = (int)$existingLicenseReleaseUids[$index];

                    $connection->update(
                        'tx_pixxioextension_domain_model_licenserelease',
                        $insertData,
                        ['uid' => $uid]
                    );
                } else {
                    // no existing UID -> insert new
                    $connection->insert('tx_pixxioextension_domain_model_licenserelease', $insertData);
                    $uid = (int)$connection->lastInsertId('tx_pixxioextension_domain_model_licenserelease');
                }

                $licenseReleaseUids[] = $uid;
                $index++;
            }

            // 3) Delete leftover old licenses if there are more existing UIDs than new Pixxio licenses
            if (!empty($existingLicenseReleaseUids) && \count($existingLicenseReleaseUids) > \count($licenseReleaseUids)) {
                $uidsToDelete = \array_slice(
                    $existingLicenseReleaseUids,
                    \count($licenseReleaseUids)
                );

                if (!empty($uidsToDelete)) {
                    $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_pixxioextension_domain_model_licenserelease');
                    $queryBuilder
                        ->delete('tx_pixxioextension_domain_model_licenserelease')
                        ->where(
                            $queryBuilder->expr()->in(
                                'uid',
                                $queryBuilder->createNamedParameter($uidsToDelete, Connection::PARAM_INT_ARRAY)
                            )
                        )
                        ->executeStatement();
                }
            }

            // 4) Return the new ordered UID list for metadata field
            return implode(',', $licenseReleaseUids);
        }

        // Pixxio has NO licenseReleases anymore – remove all previous ones
        if (!empty($file['tx_pixxioextension_licensereleases'])) {
            $existingLicenseReleaseUids = GeneralUtility::intExplode(
                ',',
                (string)$file['tx_pixxioextension_licensereleases'],
                true
            );

            if (!empty($existingLicenseReleaseUids)) {
                $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_pixxioextension_domain_model_licenserelease');
                $queryBuilder
                    ->delete('tx_pixxioextension_domain_model_licenserelease')
                    ->where(
                        $queryBuilder->expr()->in(
                            'uid',
                            $queryBuilder->createNamedParameter($existingLicenseReleaseUids, Connection::PARAM_INT_ARRAY)
                        )
                    )
                    ->executeStatement();
            }
        }

        return '';
    }

    private function getMetadataWithFilemetadataExt($pixxioFile)
    {
        $temp = [];

        foreach (array_keys($this->metadataMapping) as $key) {
            foreach (array_values((array)$pixxioFile->metadataFields) as $metadataField) {

                if ($metadataField->name === $this->metadataMapping[$key]) {
                    if (is_array($metadataField->value)) {
                        // Check if array contains stdClass objects
                        if (!empty($metadataField->value) && is_object($metadataField->value[0]) && $metadataField->value[0] instanceof \stdClass) {
                            // Extract names from array of stdClass objects
                            $names = array_map(function($item) {
                                return $item->name ?? '';
                            }, $metadataField->value);
                            $temp[$key] = join(',', $names) ?: '';
                        } else {
                            // Simple array of scalar values
                            $temp[$key] = join(',', $metadataField->value) ?: '';
                        }
                    } elseif (is_object($metadataField->value) && $metadataField->value instanceof \stdClass) {
                        // Handle single stdClass object (e.g., dropdown values with id and name)
                        $temp[$key] = $metadataField->value->name ?? '';
                    } else {
                        $temp[$key] = $metadataField->value ?: '';
                    }

                    break;
                }
            }
        }

        $temp['unit'] = 'px';

        if (isset($pixxioFile->keywords)) {
            $temp['keywords'] = join(', ', $pixxioFile->keywords);
        }

        if (isset($pixxioFile->location->latitude) && isset($pixxioFile->location->longitude)) {
            $temp['latitude'] = $pixxioFile->location->latitude;
            $temp['longitude'] = $pixxioFile->location->longitude;
        }

        if (isset($pixxioFile->createDate)) {
            $temp['content_creation_date'] = strtotime($pixxioFile->createDate);
        }

        if (isset($pixxioFile->modifyDate)) {
            $temp['content_modification_date'] = strtotime($pixxioFile->modifyDate);
        }

        if (isset($pixxioFile->colorspace)) {
            $temp['color_space'] = $pixxioFile->colorspace;
        }

        if (isset($pixxioFile->creator)) {
            $temp['creator'] = $pixxioFile->creator;
        }

        if (isset($pixxioFile->subject)) {
            $temp['download_name'] = $pixxioFile->subject;
        }

        if (isset($pixxioFile->rating)) {
            $temp['ranking'] = $pixxioFile->rating;
        }

        if (isset($pixxioFile->description)) {
            $temp['caption'] = $pixxioFile->description;
        }

        return $temp;
    }

    private function getStorage()
    {
        $storageUid = (int)$this->extensionConfiguration['filestorage_id'];
        if (!($storageUid > 0)) {
            $storageUid = 1;
        }

        $resourceFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
        return $resourceFactory->getStorageObject($storageUid);
    }

    private function saveFile($filename, $url, $isDirectLink = false)
    {
        $absFileIdentifier = $this->uploadPath() . $filename;

        // Check for executable extensions before attempting to save
        if ($this->isExecutableExtension($filename)) {
            $this->throwError(
                'Wrong upload file extension. Is not allowed to use php,js,exe,doc,xls,sh: "' . $filename,
                5
            );
        }

        try {
            $options = [
                'sink' => $absFileIdentifier,
                'timeout' => 300,
                'allow_redirects' => true
            ];

            // Apply proxy settings from extension configuration
            $this->getProxySettings($options);

            $response = $this->requestFactory->request($url, 'GET', $options);

            if ($response->getStatusCode() !== 200) {
                $this->throwError(
                    'Failed to download file from URL: "' . $url . '". HTTP Status: ' . $response->getStatusCode(),
                    8
                );
            }

            if ($isDirectLink) {
                $this->resizeImageToMaxWidth($absFileIdentifier, 250);
            }

            return $absFileIdentifier;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
            $this->throwError(
                'Failed to download file from "' . $url . '". HTTP Status: ' . $statusCode . '. Error: ' . $e->getMessage(),
                7
            );
        } catch (\Exception $e) {
            $this->throwError(
                'Failed to save file "' . $filename . '". Error: ' . $e->getMessage(),
                9
            );
        }
    }

    private function resizeImageToMaxWidth(string $filePath, int $maxWidth): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        [$width, $height, $type] = @getimagesize($filePath);
        if (!$width || !$height) {
            // Not an image, or unreadable
            return;
        }

        // Only shrink images that are wider than the max width
        if ($width <= $maxWidth) {
            return;
        }

        $ratio      = $height / $width;
        $newWidth   = $maxWidth;
        $newHeight  = (int)round($maxWidth * $ratio);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $src = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $src = imagecreatefromgif($filePath);
                break;
            default:
                // Unsupported format – do nothing
                return;
        }

        if (!$src) {
            return;
        }

        $dst = imagecreatetruecolor($newWidth, $newHeight);

        // Handle transparency for PNG/GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagecolortransparent(
                $dst,
                imagecolorallocatealpha($dst, 0, 0, 0, 127)
            );
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled(
            $dst,
            $src,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        // Overwrite the original file
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($dst, $filePath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($dst, $filePath);
                break;
            case IMAGETYPE_GIF:
                imagegif($dst, $filePath);
                break;
        }

        imagedestroy($src);
        imagedestroy($dst);
    }


    private function pullFiles($files)
    {
        $importedFiles = [];
        foreach ($files as $key => $file) {
            // set upload filename and upload folder
            $originalFilename = $this->getNonUtf8Filename($file->fileName ?: '');
            $filename = $this->generateUniqueFilename($originalFilename, $file);

            // upload file
            if (isset($file->directLink) && $file->directLink != '' && !$this->saveFile($filename, $file->directLink, true)) {
                $this->throwError('Copying file "' . $filename . '" to path "' . '" failed.', 4);
            } else if (!isset($file->directLink) && !$this->saveFile($filename, $file->downloadURL)) {
                $this->throwError('Copying file "' . $filename . '" to path "' . '" failed.', 4);
            }

            $importedFile = $this->getStorage()->getFile($this->extensionConfiguration['subfolder'] . '/' . $filename);

            if ($importedFile) {
                // import file to FAL
                $importedFileUid = $importedFile->getUid();
                $importedFiles[] = $importedFileUid;

                $link = '';
                if (isset($file->mediaspaceURL)) {
                    $link = $file->mediaspaceURL;
                } else if (isset($file->downloadURL)) {
                    $link = $file->downloadURL;
                } else if (isset($file->directLink)) {
                    // I can not use the direct link URL as Mediaspace URL because its the URL of the CDN
                }

                $mediaspaceUrl = '';
                if (isset($link) && $link != '') {
                    $parsedUrl = parse_url($link);
                    if (is_array($parsedUrl) && isset($parsedUrl['scheme'], $parsedUrl['host'])) {
                        $mediaspaceUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                    }
                }

                $downloadFormat = '';
                if (isset($file->downloadFormat)) {
                    $downloadFormat = $file->downloadFormat;
                } else if (isset($file->directLinkFormat)) {
                    $downloadFormat = $file->directLinkFormat;
                }

                // set meta data
                $additionalFields = array(
                    'title' => $file->subject,
                    'description' => $file->description,
                    'pixxio_file_id' => $file->id,
                    'pixxio_mediaspace' => $mediaspaceUrl,
                    'pixxio_last_sync_stamp' => time(),
                    'pixxio_downloadformat' => $downloadFormat
                );

                $licenseReleaseUids = [];
                if (isset($file->licenseReleases)) {

                    $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                    $connection = $connectionPool->getConnectionForTable('tx_pixxioextension_domain_model_licenserelease');

                    foreach ($file->licenseReleases as $licenseRelease) {

                        $insertData = [];
                        if (isset($licenseRelease->licenseRelease)) {
                            if (
                                isset($licenseRelease->licenseRelease->license)
                                && isset($licenseRelease->licenseRelease->license->provider)
                            ) {
                                $insertData['license_provider'] = $licenseRelease->licenseRelease->license->provider;
                            }

                            if (isset($licenseRelease->licenseRelease->name)) {
                                $insertData['name'] = $licenseRelease->licenseRelease->name;
                            }

                            if (isset($licenseRelease->licenseRelease->showWarningMessage)) {
                                $insertData['show_warning_message'] = (bool) $licenseRelease->licenseRelease->showWarningMessage;
                            }

                            if (isset($licenseRelease->licenseRelease->warningMessage)) {
                                $insertData['warning_message'] = $licenseRelease->licenseRelease->warningMessage;
                            }
                        }

                        if (isset($licenseRelease->expires)) {
                            $insertData['expires'] = $licenseRelease->expires;
                        }

                        $connection->insert('tx_pixxioextension_domain_model_licenserelease', $insertData);

                        $licenseReleaseUids[] = $connection->lastInsertId('tx_pixxioextension_domain_model_licenserelease');
                    }
                }

                $additionalFields['tx_pixxioextension_licensereleases'] = implode(',', $licenseReleaseUids);

                $hasDirectLink = isset($file->directLink) && $file->directLink != '';
                $additionalFields['pixxio_is_direct_link'] = $hasDirectLink ? 1 : 0;
                $additionalFields['pixxio_direct_link'] = $hasDirectLink ? $file->directLink : '';

                if (isset($this->extensionConfiguration['alt_text']) && isset($file->metadata->{$this->extensionConfiguration['alt_text']})) {
                    $additionalFields['alternative'] = $file->metadata->{$this->extensionConfiguration['alt_text']};
                }

                if ($this->hasExt('filemetadata')) {
                    $additionalFields = array_merge($additionalFields, $this->getMetadataWithFilemetadataExt($file));
                }

                $metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);
                $metaDataRepository->update($importedFileUid, $additionalFields);
            }
        }
        return $importedFiles;
    }

    protected function isExecutableExtension($filename)
    {
        $notSupportedImages = array(
            'php',
            'js',
            'cgi',
            'exe',
            'doc',
            'xls',
            'sh'
        );
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $notSupportedImages)) {
            return true;
        } else {
            return false;
        }
    }

    protected function getNonUtf8Filename($filename)
    {
        $filename = mb_strtolower($filename, 'UTF-8');
        $filename = str_replace(
            array('ä', 'ö', 'ü', 'ß', ' - ', ' + ', '_', ' / ', '/'),
            array('ae', 'oe', 'ue', 'ss', '-', '-', '-', '-', '-'),
            $filename
        );
        $filename = str_replace(' ', '-', $filename);
        $filename = preg_replace('/[^a-z0-9\._-]/isU', '', $filename);
        $filename = trim($filename);
        return $filename;
    }

    protected function isImageExtension($filename)
    {
        $supportedImages = array(
            'gif',
            'jpg',
            'jpeg',
            'png'
        );
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $supportedImages)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Filenames should be unique per unique file in pixxio (that's why to use pixxio id)
     *
     * @param string $filename The original filename to check for uniqueness.
     * @return string The unique filename.
     */
    protected function generateUniqueFilename($originalFilename): string
    {
        $uploadPath = $this->uploadPath();
        $counter = 1;

        $candidateFilename = $originalFilename;

        $pathInfo = pathinfo($originalFilename);
        $basename = $pathInfo['filename'];
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

        // Keep checking and incrementing until we find a unique filename
        while (file_exists($uploadPath . $candidateFilename)) {
            $candidateFilename = $basename . '_' . $counter . $extension;
            $counter++;
        }

        return $candidateFilename;
    }
}
