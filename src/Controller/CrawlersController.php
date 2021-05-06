<?php

declare(strict_types=1);

namespace App\Controller;

use App\ValueObject\Routing\RouteName;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CrawlersController extends AbstractController
{
    #[Route(path: '/sitemap.txt', name: RouteName::SITEMAP)]
    #[Cache(maxage: 21600, public: true)]
    public function sitemap(): Response
    {
        $urls = array_map(fn (string $route): string => $this->generateUrl($route, [], UrlGeneratorInterface::ABSOLUTE_URL), [
            'main',
            'data_updates',
            'info',
            'tracking',
            'maker_ids',
            'statistics',
            'events',
            'donate',
        ]);

        return $this->render('crawlers/sitemap.txt.twig', [
            'urls' => $urls,
        ], self::getTextResponse());
    }

    private static function getTextResponse(): Response
    {
        return new Response('', Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }
}
