<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Event;
use App\Tests\TestUtils\DbEnabledWebTestCase;

class EventsControllerTest extends DbEnabledWebTestCase
{
    public function testPageLoads(): void
    {
        $client = static::createClient();

        $client->request('GET', '/events.html');

        static::assertEquals(200, $client->getResponse()->getStatusCode());
        static::assertSelectorTextContains('p', 'All times are UTC');
    }

    /**
     * @dataProvider eventDescriptionDataProvider
     */
    public function testEventDescription(Event $event, string $expectedHtml): void
    {
        $client = static::createClient();
        $this->persistAndFlush($event);

        $client->request('GET', '/events.html');

        $actualHtml = $client->getCrawler()->filter('#events-list p')->html();

        self::assertEqualsIgnoringWhitespace($expectedHtml, $actualHtml);
    }

    /** @noinspection HtmlUnknownTarget */
    public function eventDescriptionDataProvider(): array
    {
        return [
            [
                (new Event())
                    ->setArtisanName('Artisan name 1')
                    ->setCheckedUrls('https://getfursu.it/page1.html')
                    ->setType(Event::TYPE_CS_UPDATED)
                    ->setNowOpenFor("Commissions\nPre-mades")
                    ->setNoLongerOpenFor('Artistic liberty')
                    ->setTrackingIssues(true), '
                <strong>Artisan name 1</strong> commissions status changed.
                No longer open for: Artistic liberty.
                <strong>Now open for: Commissions, Pre-mades.</strong>
                Encountered apparent difficulties during status analysis.
                Checked contents of:
                <a href="https://getfursu.it/page1.html" target="_blank">getfursu.it/page1.html</a>.',
            ],
            [
                (new Event())
                    ->setArtisanName('Artisan name 2')
                    ->setCheckedUrls("https://getfursu.it/page2.html\nhttps://another.page/")
                    ->setType(Event::TYPE_CS_UPDATED)
                    ->setNowOpenFor('')
                    ->setNoLongerOpenFor('Pancakes')
                    ->setTrackingIssues(false), '
                <strong>Artisan name 2</strong> commissions status changed.
                No longer open for: Pancakes.
                Checked contents of:
                <a href="https://getfursu.it/page2.html" target="_blank">getfursu.it/page2.html</a>,
                <a href="https://another.page/" target="_blank">another.page</a>.',
            ],
            [
                (new Event())
                    ->setArtisanName('One more artisan')
                    ->setCheckedUrls('http://just-one-website/doc.php')
                    ->setType(Event::TYPE_CS_UPDATED)
                    ->setNowOpenFor("Carrots\nApples")
                    ->setNoLongerOpenFor('')
                    ->setTrackingIssues(true), '
                <strong>One more artisan</strong> commissions status changed.
                <strong>Now open for: Carrots, Apples.</strong>
                Encountered apparent difficulties during status analysis.
                Checked contents of:
                <a href="http://just-one-website/doc.php" target="_blank">just-one-website/doc.php</a>.',
            ],
        ];
    }
}
