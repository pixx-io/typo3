<?php

declare(strict_types=1);

namespace Pixxio\PixxioExtension\Domain\Repository;

use Pixxio\PixxioExtension\Domain\Model\LicenseRelease;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<LicenseRelease>
 */
class LicenseReleaseRepository extends Repository
{
    private const TABLE = 'tx_pixxioextension_domain_model_licenserelease';

    /**
     * @param array<int, int> $uids
     * @return array<string, LicenseRelease>
     */
    public function findByUidsIndexedByPixxioId(array $uids): array
    {
        if ($uids === []) {
            return [];
        }

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching($query->in('uid', $uids));

        $map = [];
        /** @var LicenseRelease $obj */
        foreach ($query->execute() as $obj) {
            if ($obj->getPixxioId() !== '') {
                $map[$obj->getPixxioId()] = $obj;
            }
        }
        return $map;
    }

    /**
     * @param array<int, int> $uids
     */
    public function deleteByUids(array $uids): void
    {
        if ($uids === []) {
            return;
        }

        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $qb->delete(self::TABLE)
            ->where($qb->expr()->in('uid', $qb->createNamedParameter($uids, Connection::PARAM_INT_ARRAY)))
            ->executeStatement();
    }
}
