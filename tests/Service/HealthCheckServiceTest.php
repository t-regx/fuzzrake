<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Repository\ArtisanVolatileDataRepository;
use App\Service\HealthCheckService;
use App\Utils\DateTime\DateTimeUtils;
use DateTime;
use DateTimeZone;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;

class HealthCheckServiceTest extends TestCase
{
    private const HC_VALUES = [
        'MEMORY_AVAILABLE_MIN_MIBS'  => 256,
        'DISK_FREE_MIN_MIBS'         => 1024,
        'DISK_USED_MAX_PERCENT'      => 90,
        'LOAD_1M_MAX'                => 0.9,
        'LOAD_5M_MAX'                => 0.5,
        'LOAD_15M_MAX'               => 0.2,
    ];

    public static function setUpBeforeClass(): void
    {
        ClockMock::register(DateTimeUtils::class);
    }

    public function testTimes(): void
    {
        ClockMock::withClockMock(true);

        $acsrMock = $this->createPartialMock(ArtisanVolatileDataRepository::class, ['getLastCsUpdateTime', 'getLastBpUpdateTime']);
        $acsrMock
            ->expects(self::exactly(2))
            ->method('getLastCsUpdateTime')
            ->willReturn(DateTime::createFromFormat('U', (string) ClockMock::time(), new DateTimeZone('UTC')));
        $acsrMock
            ->expects(self::exactly(2))
            ->method('getLastBpUpdateTime')
            ->willReturn(DateTime::createFromFormat('U', (string) ClockMock::time(), new DateTimeZone('UTC')));

        $hcSrv = new HealthCheckService($acsrMock, self::HC_VALUES);
        $data = $hcSrv->getStatus();

        static::assertEquals(DateTime::createFromFormat('U', (string) ClockMock::time(), new DateTimeZone('UTC'))->format('Y-m-d H:i:s'), $data['serverTimeUtc']);
        static::assertEquals(DateTime::createFromFormat('U', (string) ClockMock::time(), new DateTimeZone('UTC'))->format('Y-m-d H:i'), $data['lastCsUpdateUtc']);
        static::assertEquals(DateTime::createFromFormat('U', (string) ClockMock::time(), new DateTimeZone('UTC'))->format('Y-m-d H:i'), $data['lastBpUpdateUtc']);

        ClockMock::withClockMock(false);
    }

    /**
     * @dataProvider getXyzUpdatesStatusDataProvider
     *
     * @throws Exception
     */
    public function testGetXyzUpdatesStatus(string $repoMethodName, array $subsequentRepoReturnedDateTimes, string $hcServiceResultCheckedKey, array $hcSubsequentExpectedResultCheckedValue): void
    {
        $avdrMock = $this->createMock(ArtisanVolatileDataRepository::class);
        $avdrMock
            ->expects(self::exactly(count($subsequentRepoReturnedDateTimes)))
            ->method($repoMethodName)
            ->willReturn(...$subsequentRepoReturnedDateTimes)
        ;

        $hcSrv = new HealthCheckService($avdrMock, self::HC_VALUES);

        static::assertEquals(array_shift($hcSubsequentExpectedResultCheckedValue), $hcSrv->getStatus()[$hcServiceResultCheckedKey]);
        static::assertEquals(array_shift($hcSubsequentExpectedResultCheckedValue), $hcSrv->getStatus()[$hcServiceResultCheckedKey]);
    }

    /**
     * @throws Exception
     */
    public function getXyzUpdatesStatusDataProvider(): array // grep-tracking-frequency
    {
        $utc = new DateTimeZone('UTC');

        return [
            [
                'getLastCsUpdateTime',
                [
                    new DateTime('-12 hours -10 minutes', $utc),
                    new DateTime('-12 hours -20 minutes', $utc),
                ],
                'csUpdatesStatus',
                ['OK', 'WARNING'],
            ], [
                'getLastBpUpdateTime',
                [
                    new DateTime('-7 days -10 minutes', $utc),
                    new DateTime('-7 days -20 minutes', $utc),
                ],
                'bpUpdatesStatus',
                ['OK', 'WARNING'],
            ],
        ];
    }

    /**
     * @dataProvider getDiskStatusDataProvider
     */
    public function testGetDiskStatus(string $result, string $rawInput): void
    {
        static::assertEquals($result, $this->getHcService()->getDiskStatus($rawInput));
    }

    /**
     * @dataProvider getMemoryStatusDataProvider
     */
    public function testGetMemoryStatus(string $result, string $rawInput): void
    {
        static::assertEquals($result, $this->getHcService()->getMemoryStatus($rawInput));
    }

    /**
     * @dataProvider getLoadStatusDataProvider
     */
    public function testGetLoadStatus(string $result, string $cpuCountRawData, string $procLoadAvgRawData): void
    {
        static::assertEquals($result, $this->getHcService()->getLoadStatus($cpuCountRawData, $procLoadAvgRawData));
    }

    public function getDiskStatusDataProvider(): array
    {
        return [
            [
                'OK',
                <<<'END'

                26723	76%
                548	1%
                291059	84%
                
                END,
            ],
            [
                'OK',
                <<<'END'

                26723	76%
                548	1%
                291059	100%
                
                END,
            ],
            [
                'OK',
                <<<'END'

                3709	55%
                
                END,
            ],
            [
                'WARNING',
                <<<'END'

                26723	76%
                548	91%
                291059	84%
                
                END,
            ],
            [
                'WARNING',
                <<<'END'

                1009	91%
                
                END,
            ],
            [
                'WARNING',
                <<<'END'

                2009e	10%
                
                END,
            ],
            [
                'WARNING',
                <<<'END'

                2009	10%	e
                
                END,
            ],
            [
                'WARNING',
                <<<'END'

                26723	76%
                548	1%
                291059	100%
                some warning here
                
                END,
            ],
            [
                'WARNING',
                <<<'END'

                26723	76%
                548	1%

                291059	100%
                
                END,
            ],
            [ // Percent sign, but no percent value
                'WARNING',
                <<<'END'

                26723	76%
                548	%
                291059	100%
                
                END,
            ],
            [ // Tab, but no percent value
                'WARNING',
                <<<'END'

                26723	76%
                548	
                291059	100%
                
                END,
            ],
            ['WARNING', "\n"],
            ['WARNING', 'some error'],
        ];
    }

    public function getMemoryStatusDataProvider(): array
    {
        return [
            [
                'OK',
                <<<'END'

                8927
                
                END,
            ],
            [
                'WARNING',
                <<<'END'

                254
                
                END,
            ],
            ['WARNING', "\n"],
            ['WARNING', '512 e'],
        ];
    }

    public function getLoadStatusDataProvider(): array
    {
        return [
            ['OK', ' 1 ', " 0.1\t0.1\t0.1 "],
            ['WARNING', ' 1 ', " 0.1\t0.1\t0.2 "],
            ['OK', ' 2 ', " 0.1\t0.1\t0.2 "],
            ['WARNING', ' 1 ', " 0.1\t0.5\t0.1 "],
            ['OK', ' 2 ', " 0.1\t0.5\t0.1 "],
            ['WARNING', ' 1 ', " 0.9\t0.1\t0.1 "],
            ['OK', ' 2 ', " 0.9\t0.1\t0.1 "],
            ['WARNING', ' e ', " 0.1\t0.1\t0.1 "],
            ['WARNING', '  ', " 0.1\t0.1\t0.1 "],
            ['WARNING', ' 1 ', " 0.1\t0.1\t0.1\t0.1 "],
            ['WARNING', ' 1 ', ' '],
            ['WARNING', ' 1 ', " \t\t "],
            ['WARNING', ' 1 ', " 1\t1\t1\te "],
            ['WARNING', ' 1 ', " 1\t1\t1. "],
            ['WARNING', ' 1 ', "\n"],
            ['WARNING', ' 1 ', 'some error'],
            ['WARNING', "\n", " 0.1\t0.1\t0.1 "],
            ['WARNING', 'some error', " 0.1\t0.1\t0.1 "],
        ];
    }

    private function getHcService(): HealthCheckService
    {
        $acsrMock = $this->createMock(ArtisanVolatileDataRepository::class);

        return new HealthCheckService($acsrMock, self::HC_VALUES);
    }
}
