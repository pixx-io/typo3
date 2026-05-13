<?php

declare(strict_types=1);

namespace Pixxio\PixxioExtension\Condition;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ShowCropIfNotPixxioDirectLink
{
    public function evaluate(array $record): bool
    {
        $fileUid = (int)($record['record']['uid_local'][0]['uid'] ?? 0);

        if ($fileUid === 0) {
            return true;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');

        $result = $queryBuilder
            ->select('pixxio_is_direct_link')
            ->from('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($fileUid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        return empty($result['pixxio_is_direct_link']);
    }
}
