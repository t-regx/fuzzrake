<?php

declare(strict_types=1);

namespace App\Tests\BrowserBasedFrontendTests;

use App\Tests\TestUtils\Cases\PantherTestCaseWithEM;
use Facebook\WebDriver\Exception\WebDriverException;

/**
 * @large
 */
class InfoPageTest extends PantherTestCaseWithEM
{
    /**
     * @throws WebDriverException
     */
    public function testRecaptchaWorksAndEmailAddressAppears(): void
    {
        $client = static::createPantherClient();
        $client->request('GET', '/index.php/info');

        // E-mail address link is not visible by default
        self::assertSelectorNotExists('#protected-contact-info a');

        // Wait until automatic captcha works
        $client->waitForVisibility('#protected-contact-info a', 5);

        // The link should now contain the e-mail address
        self::assertSelectorAttributeContains('#protected-contact-info a', 'href', 'mailto:');
    }
}
