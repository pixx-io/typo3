<?php
namespace Pixxio\PixxioExtension\Condition;

class ShowCropIfNotPixxioDirectLink
{
    public function evaluate(array $record): bool
    {
        $fileUid = (int)(@$record['record']['uid_local'][0]['uid'] ?? 0);

        if ($fileUid === 0) {
            return true;
        }

        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Database\ConnectionPool::class
        )->getQueryBuilderForTable('sys_file_metadata');

        $result = $queryBuilder
            ->select('pixxio_is_direct_link')
            ->from('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        return empty($result['pixxio_is_direct_link']);
    }
}
