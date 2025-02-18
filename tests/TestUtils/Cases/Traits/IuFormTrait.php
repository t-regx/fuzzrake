<?php

declare(strict_types=1);

namespace App\Tests\TestUtils\Cases\Traits;

use App\Utils\TestUtils\TestsBridge;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait IuFormTrait
{
    private static function skipRulesAndCaptcha(KernelBrowser $client): void
    {
        TestsBridge::setSkipSingleCaptcha();

        $client->submit($client->getCrawler()->selectButton('Agree and continue')->form());
        $client->followRedirect();
    }

    private static function skipData(KernelBrowser $client, bool $fillMandatoryData): void
    {
        $data = !$fillMandatoryData ? [] : [
                'iu_form[name]'            => 'Test name',
                'iu_form[country]'         => 'Test country',
                'iu_form[ages]'            => 'ADULTS',
                'iu_form[worksWithMinors]' => 'NO',
                'iu_form[nsfwWebsite]'     => 'NO',
                'iu_form[nsfwSocial]'      => 'NO',
                'iu_form[doesNsfw]'        => 'NO',
                'iu_form[makerId]'         => 'TESTMID',
            ];

        $form = $client->getCrawler()->selectButton('Continue')->form($data);

        self::submitValid($client, $form);
    }
}
