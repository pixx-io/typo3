<?php

declare(strict_types=1);

namespace Pixxio\PixxioExtension\Domain\Repository;

use Pixxio\PixxioExtension\Domain\Model\LicenseRelease;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

class LicenseReleaseRepository extends Repository
{
    private const TABLE = 'tx_pixxioextension_domain_model_licenserelease';

    /**
     * @return array<string, LicenseRelease>
     */
    public function findByUids(array $uids): array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->in('uid', $uids));

        $map = [];
        foreach ($query->execute() as $obj) {
            if ($obj->getPixxioId() !== '') {
                $map[$obj->getPixxioId()] = $obj;
            }
        }
        return $map;
    }

    public function deleteByUids(array $uids): void
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $qb->delete(self::TABLE)
            ->where($qb->expr()->in('uid', $qb->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)))
            ->executeStatement();
    }
}
