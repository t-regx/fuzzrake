<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Artisan;
use App\Entity\Event;
use App\Repository\ArtisanRepository;
use App\Utils\DateTime\DateTimeUtils;
use App\Utils\Tracking\AnalysisResult;
use App\Utils\Tracking\CommissionsStatusParser;
use App\Utils\Tracking\NullMatch;
use App\Utils\Tracking\Status;
use App\Utils\Tracking\TrackerException;
use App\Utils\Web\HttpClientException;
use App\Utils\Web\Url;
use Doctrine\Common\Persistence\ObjectManager;
use InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommissionStatusUpdateService
{
    /**
     * @var ArtisanRepository
     */
    private $artisanRepository;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var WebpageSnapshotManager
     */
    private $snapshots;

    /**
     * @var StyleInterface
     */
    private $io;

    /**
     * @var CommissionsStatusParser
     */
    private $parser;

    public function __construct(ObjectManager $objectManager, WebpageSnapshotManager $snapshots)
    {
        $this->objectManager = $objectManager;
        $this->artisanRepository = $objectManager->getRepository(Artisan::class);
        $this->snapshots = $snapshots;
        $this->parser = new CommissionsStatusParser();
    }

    public function updateAll(SymfonyStyle $style, bool $refresh, bool $dryRun)
    {
        if ($refresh) {
            $this->snapshots->clearCache();
        }

        $this->setIo($style);
        $urls = $this->getCstUrls($this->getTrackedArtisans());

        $this->snapshots->prefetchUrls($urls, $this->io);

        foreach ($urls as $url) {
            $this->performUpdate($url);
        }

        if (!$dryRun) {
            $this->objectManager->flush();
        }
    }

    private function performUpdate(Url $url): void
    {
        $artisan = $url->getArtisan();

        try {
            $webpageSnapshot = $this->snapshots->get($url);
            $datetimeRetrieved = $webpageSnapshot->getRetrievedAt();
            $analysisResult = $this->parser->analyseStatus($webpageSnapshot);
        } catch (TrackerException | InvalidArgumentException | HttpClientException $exception) { // FIXME: actual failure would result in "NONE MATCHES" interpretation
            $datetimeRetrieved = DateTimeUtils::getNowUtc();
            $analysisResult = new AnalysisResult(NullMatch::get(), NullMatch::get());
        }

        $this->reportStatusChange($artisan, $analysisResult);
        $artisan->getCommissionsStatus()->setStatus($analysisResult->getStatus())->setLastChecked($datetimeRetrieved);
    }

    private function canAutoUpdate(Artisan $artisan): bool
    {
        return !empty($artisan->getCstUrl());
    }

    private function reportStatusChange(Artisan $artisan, AnalysisResult $analysisResult): void
    {
        if ($artisan->getCommissionsStatus()->getStatus() !== $analysisResult->getStatus()) {
            $oldStatusText = Status::text($artisan->getCommissionsStatus()->getStatus());
            $newStatusText = Status::text($analysisResult->getStatus());

            $this->io->caution("{$artisan->getName()} ( {$artisan->getCstUrl()} ): {$analysisResult->explanation()}, $oldStatusText ---> $newStatusText");
        } elseif ($analysisResult->hasFailed()) {
            $this->io->note("{$artisan->getName()} ( {$artisan->getCstUrl()} ): {$analysisResult->explanation()}");
        } else {
            return;
        }

        if ($analysisResult->openMatched()) {
            $this->io->text("Matched OPEN ({$analysisResult->getOpenRegexpId()}): ".
                "<context>{$analysisResult->getOpenStrContext()->getBefore()}</>".
                "<open>{$analysisResult->getOpenStrContext()->getSubject()}</>".
                "<context>{$analysisResult->getOpenStrContext()->getAfter()}</>");
        }

        if ($analysisResult->closedMatched()) {
            $this->io->text("Matched CLOSED ({$analysisResult->getClosedRegexpId()}): ".
                "<context>{$analysisResult->getClosedStrContext()->getBefore()}</>".
                "<closed>{$analysisResult->getClosedStrContext()->getSubject()}</>".
                "<context>{$analysisResult->getClosedStrContext()->getAfter()}</>");
        }

        if ($artisan->getCommissionsStatus()->getStatus() !== $analysisResult->getStatus()) {
            $this->objectManager->persist(new Event($artisan->getCstUrl(), $artisan->getName(),
                $artisan->getCommissionsStatus()->getStatus(), $analysisResult));
        }
    }

    /**
     * @return Artisan[]
     */
    private function getTrackedArtisans(): array
    {
        return array_filter($this->artisanRepository->findAll(), function (Artisan $artisan): bool {
            return $this->canAutoUpdate($artisan);
        });
    }

    /**
     * @param Artisan[]
     *
     * @return Url[]
     */
    private function getCstUrls(array $artisans): array
    {
        return array_map(function (Artisan $artisan): Url {
            return new Url($artisan->getCstUrl(), $artisan);
        }, $artisans);
    }

    private function setIo(SymfonyStyle $style): void
    {
        $this->io = $style;
        $this->io->getFormatter()->setStyle('open', new OutputFormatterStyle('green'));
        $this->io->getFormatter()->setStyle('closed', new OutputFormatterStyle('red'));
        $this->io->getFormatter()->setStyle('context', new OutputFormatterStyle('blue'));
    }
}
