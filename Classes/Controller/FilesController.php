<?php

declare(strict_types=1);

namespace Pixxio\PixxioExtension\Controller;

use Pixxio\PixxioExtension\Utility\ConfigurationUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FilesController
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
        $this->extensionConfiguration = ConfigurationUtility::getExtensionConfiguration();
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
    }

    public function hasExt($key)
    {
        return ExtensionManagementUtility::isLoaded($key);
    }

    public function getJSONRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        if (!str_contains($request->getHeaderLine('Content-Type'), 'application/json')) {
            return $request;
        }
        // TODO: Implement broken json handling
        return $request->withParsedBody(json_decode($request->getBody()->getContents()));
    }

    public function selectedFilesAction(
        ServerRequestInterface $request,
        ?ResponseInterface $response = null
    ): ResponseInterface {
        // get files
        $files = $this->getJSONRequest($request)->getParsedBody()->files;

        // pull files from pixx.io
        if ($files) {
            $importedFiles = $this->pullFiles($files);
            return new JsonResponse(['files' => $importedFiles]);
        }
        return $response;
    }

    private function throwError($message, $num): never
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
            if (!str_ends_with((string) $storageBasePath, '/')) {
                $storageBasePath = $storageBasePath . '/';
            }
            if (str_starts_with((string) $storageBasePath, '/')) {
                $storageBasePath = substr((string) $storageBasePath, 1);
            }

            if ($this->extensionConfiguration['subfolder']) {
                $storageBasePath .= $this->extensionConfiguration['subfolder'];
            }

            if (!str_ends_with((string) $storageBasePath, '/')) {
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
            'previewFileURL'
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
        if ($this->extensionConfiguration['use_proxy'] && filter_var($this->extensionConfiguration['proxy_connection'],
                FILTER_VALIDATE_URL)) {
            $proxy = [];
            $proxy[str_starts_with((string) $this->extensionConfiguration['proxy_connection'], 'https') ? 'https' : 'http'] = $this->extensionConfiguration['proxy_connection'];
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

            $response = $this->requestFactory->request($this->extensionConfiguration['url'] . '/gobackend/files?' . http_build_query([
                    'pageSize' => $this->extensionConfiguration['limit'] > 500 ? 500 : (int)$this->extensionConfiguration['limit'],
                    'page' => 1,
                    'responseFields' => json_encode($this->getResponseFields()),
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
        } catch (\Exception $error) {
            $this->throwError($error->getMessage(), 1);
        }
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
        }
        if ($this->extensionConfiguration['token_refresh'] == "") {
            $this->throwError('Authentication to pixx.io failed. Please check pixx.io refresh token in your extension configuration', 10);
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

        $response = $this->requestFactory->request($this->extensionConfiguration['url'] . '/gobackend/accessToken',
            'POST', $additionalOptions);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody()->getContents());
            return $data->success ? $data->accessToken : false;
        }

        $this->throwError('Authentication to pixx.io failed. Please check your configuration and your given refresh token.',
            2);
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
                    foreach ($data->files as $f) {
                        if ($f->isMainVersion) {
                            $temp[] = [
                                'oldId' => $f->id,
                                'newId' => $f->id
                            ];
                        } else {
                            $temp[] = [
                                'oldId' => $f->id,
                                'newId' => $f->mainVersion
                            ];
                        }
                    }

                    foreach ($fileIds as $id) {
                        if (!array_filter($temp, function ($t) use ($id) {
                            return $t['oldId'] == $id;
                        })) {
                            $temp[] = [
                                'oldId' => $id,
                                'newId' => null
                            ];
                        };
                    }
                }
            }
            return $temp;
        } catch (\Exception $error) {
            $this->throwError($error->getMessage(), 3);
        }
    }

    private function getTypo3FileByPixxioId($id)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(RootLevelRestriction::class));
        $fileMetaData = $queryBuilder
            ->select('*')
            ->from('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->eq('pixxio_file_id', (int)$id),
            )
            ->orderBy('pixxio_last_sync_stamp')
            ->setMaxResults(1);

        $fileMetaData = $fileMetaData->executeQuery()->fetchAssociative();

        if ($fileMetaData) {
            $file = $queryBuilder
                ->select('*')
                ->from('sys_file')
                ->where(
                    $queryBuilder->expr()->eq('uid', (int)$fileMetaData['file']),
                )
                ->orderBy('pixxio_last_sync_stamp')
                ->setMaxResults(1);

            $file = $file->executeQuery()->fetchAssociative();
        }

        if ($fileMetaData && $file) {
            return [
                'file' => $file,
                'metadata' => $fileMetaData
            ];
        }

        return null;
    }

    public function syncAction($io): bool
    {
        // check if extension configuration is set to update/delete media by sync command
        if (!($this->extensionConfiguration['delete'] || $this->extensionConfiguration['update'])) {
            $io->writeln('Please update extension configuration to enable update/deletion of media by sync command');
            return true;
        }

        $metadata = GeneralUtility::makeInstance(MetaDataRepository::class);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(RootLevelRestriction::class));

        $files = $queryBuilder
            ->select('*')
            ->from('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->gt('pixxio_file_id', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('pixxio_last_sync_stamp')
            ->setMaxResults(10)
            ->leftJoin('sys_file_metadata', 'sys_file', 'f', $queryBuilder->expr()->eq(
                'sys_file_metadata.file',
                $queryBuilder->quoteIdentifier('f.uid')
            )
            );

        $files = $files->executeQuery()->fetchAllAssociative();

        $io->writeln('Got files from database');

        $fileIds = array_map(function ($file) {
            return $file['pixxio_file_id'];
        }, $files);

        $io->writeln('Mapped files from database to pixx.io IDs');

        if (empty($fileIds)) {
            $io->writeln('no pixx.io files found');
            return false;
        }

        $io->writeln('Authenticate to pixx.io');
        $this->accessToken = $this->pixxioAuth();
        $io->writeln('Authenticated');

        $io->writeln('Check Existence and Version on pixx.io');
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
        $io->writeln('Files to delete:' . count($pixxioIdsToDelete));
        $io->writeln('Files to update:' . count($pixxioIdsToUpdate));

        foreach ($files as $index => $file) {
            // delete files
            if (in_array($file['pixxio_file_id'], $pixxioIdsToDelete)) {
                if ($this->extensionConfiguration['delete']) {
                    $io->writeln('File to deleted:' . $file['identifier']);
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
                        $absFileIdentifier = $this->saveFile($file['name'], $pixxioFile->originalFileURL);
                        $storage = $this->getStorage();
                        $storage->replaceFile($storage->getFileByIdentifier($file['identifier']), $absFileIdentifier);
                        $io->writeln('File to updated:' . $file['identifier']);
                        foreach ($fileIds as $key => $id) {
                            if ($id === $file['pixxio_file_id']) {
                                $fileIds[$key] = $newId;
                                break;
                            }
                        }

                        $files[$index]['pixxio_file_id'] = $newId;
                    }
                } else {
                    $io->writeln('File which should be updated, but extension configuration is set to not update files: ' . $file['identifier']);
                }
            }
        }

        $files = array_values($files);

        $fileIdsWithoutDeletedFiles = array_values(array_filter($fileIds, function ($id) use ($pixxioIdsToDelete) {
            return !in_array($id, $pixxioIdsToDelete);
        }));

        $io->writeln('start to sync: ' . json_encode($fileIdsWithoutDeletedFiles));
        $pixxioFiles = $this->pixxioFiles($fileIdsWithoutDeletedFiles);

        $io->writeln('Start Syncing metadata');
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

            $additionalFields = [
                'title' => $pixxioFile->subject,
                'description' => $pixxioFile->description,
                'alternative' => $this->getMetadataField($pixxioFile,
                    $this->extensionConfiguration['alt_text'] ?: 'Alt Text (Accessibility)'),
                'pixxio_file_id' => $pixxioFile->id,
                //'pixxio_mediaspace' => $pixxioFile->originalFileURL,
                'pixxio_last_sync_stamp' => time()
            ];

            if ($this->hasExt('filemetadata')) {
                $additionalFields = array_merge($additionalFields, $this->getMetadataWithFilemetadataExt($pixxioFile));
            }
            $io->writeln('Metadata update for ' . $file['identifier']);
            $metadata->update($file['uid'], $additionalFields);
        }
        return true;
    }

    private function getMetadataWithFilemetadataExt($pixxioFile)
    {
        $temp = [];

        foreach (array_keys($this->metadataMapping) as $key) {
            foreach (array_values((array)$pixxioFile->metadataFields) as $metadataField) {

                if ($metadataField->name === $this->metadataMapping[$key]) {
                    if (is_array($metadataField->value)) {
                        $temp[$key] = join(',', $metadataField->value) ?: '';
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

        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        return $resourceFactory->getStorageObject($storageUid);
    }

    private function saveFile($filename, $url)
    {
        $uploaded = false;
        $absFileIdentifier = $this->uploadPath() . $filename;

        if (file_exists($absFileIdentifier) || is_file($absFileIdentifier)) {
            $uploaded = true;
        } else {
            if ($this->isExecutableExtension($filename)) {
                $this->throwError('Wrong upload file extension. Is not allowed to use php,js,js,exe,doc,xls,sh: "' . $filename,
                    5);
            }
        }

        if (ini_get('allow_url_fopen')) {
            $uploaded = file_put_contents($absFileIdentifier, file_get_contents($url));
        } else {
            $ch = curl_init($url);
            $fp = fopen($absFileIdentifier, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);

            $uploaded = true;
            if (!curl_exec($ch)) {
                $this->throwError('CURL Error while transferring file.', 7);
                $uploaded = false;
            }

            curl_close($ch);
            fclose($fp);
        }

        return $uploaded ? $absFileIdentifier : false;
    }

    private function pullFiles($files)
    {
        $importedFiles = [];
        foreach ($files as $key => $file) {
            // set upload filename and upload folder
            $filename = $this->getNonUtf8Filename($file->fileName ?: '');

            // upload file
            if (!$this->saveFile($filename, $file->downloadURL)) {
                //if (!$this->saveFile($filename, $file->url)) {
                $this->throwError('Copying file "' . $filename . '" to path "' . '" failed.', 4);
            } else {
                $importedFile = $this->getStorage()->getFile($this->extensionConfiguration['subfolder'] . '/' . $filename);
                if ($importedFile) {

                    // import file to FAL
                    $importedFileUid = $importedFile->getUid();
                    $importedFiles[] = $importedFileUid;

                    // set meta data
                    $additionalFields = [
                        'title' => $file->subject,
                        'description' => $file->description,
                        'pixxio_file_id' => $file->id,
                        'pixxio_mediaspace' => $file->downloadURL,
                        'pixxio_last_sync_stamp' => time(),
                        'pixxio_downloadformat' => $file->downloadFormat
                    ];

                    if (isset($this->extensionConfiguration['alt_text']) && isset($file->metadata->{$this->extensionConfiguration['alt_text']})) {
                        $additionalFields['alternative'] = $file->metadata->{$this->extensionConfiguration['alt_text']};
                    }

                    $metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);
                    $metaDataRepository->update($importedFileUid, $additionalFields);
                }

            }
        }
        return $importedFiles;
    }

    protected function isExecutableExtension($filename)
    {
        $notSupportedImages = [
            'php',
            'js',
            'cgi',
            'exe',
            'doc',
            'xls',
            'sh'
        ];
        $ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
        if (in_array($ext, $notSupportedImages)) {
            return true;
        } else {
            return false;
        }
    }

    protected function getNonUtf8Filename($filename)
    {
        $filename = mb_strtolower((string) $filename, 'UTF-8');
        $filename = str_replace(
            ['ä', 'ö', 'ü', 'ß', ' - ', ' + ', '_', ' / ', '/'],
            ['ae', 'oe', 'ue', 'ss', '-', '-', '-', '-', '-'],
            $filename);
        $filename = str_replace(' ', '-', $filename);
        $filename = preg_replace('/[^a-z0-9\._-]/isU', '', $filename);
        $filename = trim((string) $filename);
        return $filename;
    }
}