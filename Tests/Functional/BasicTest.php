<?php
declare(strict_types=1);

namespace Pixxio\PixxioExtension\Tests\Functional;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 *
 * @author pixx.io <ds@pixx.io>
 */
class BasicTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/pixxio_extension',
    ];

    /**
     * Just a dummy to show that at least one test is actually executed
     *
     * @test
     */
    public function dummy(): void
    {
        $this->assertTrue(true);
    }
}
