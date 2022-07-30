<?php

declare(strict_types=1);

namespace App\Tasks\Miniatures;

use App\Utils\ArrayReader;
use App\Utils\Json;
use App\Utils\Web\HttpClient\GentleHttpClient;

class FurtrackMiniatures extends AbstractMiniatures
{
    public function __construct(
        GentleHttpClient $httpClient,
    ) {
        parent::__construct($httpClient);
    }

    public function getMiniatureUrl(string $photoUrl): string
    {
        $pictureId = $this->getPictureId($photoUrl);

        $response = $this->httpClient->get("https://ultra.furtrack.com/view/post/$pictureId");

        $postData = Json::decode($response->getContent(true));
        $accessor = new ArrayReader($postData);

        $postStub = $accessor->getNonEmptyString('[post][postStub]');
        $metaFiletype = $accessor->getNonEmptyString('[post][metaFiletype]');

        return "https://orca.furtrack.com/gallery/thumb/$postStub.$metaFiletype";
    }

    protected function getRegexp(): string
    {
        return '^https://www.furtrack.com/p/(?<picture_id>\d+)$';
    }
}
