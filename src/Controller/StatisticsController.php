<?php

declare(strict_types=1);

namespace App\Controller;

use App\DataDefinitions\Fields;
use App\Entity\Artisan;
use App\Repository\ArtisanCommissionsStatusRepository;
use App\Repository\ArtisanRepository;
use App\Utils\Filters\FilterData;
use App\Utils\Filters\Item;
use App\Utils\Filters\Set;
use App\Utils\Species\Species;
use App\ValueObject\Routing\RouteName;
use Doctrine\ORM\UnexpectedResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/')]
class StatisticsController extends AbstractController
{
    private const MATCH_WORDS = [
        'accessor',
        'bases?|blanks?',
        'bendable|pose?able|lickable',
        'brush',
        'change?able|detach|remove?able',
        'claws?',
        'cosplay',
        'details?',
        '(?<!g)ears?',
        'eyes?',
        'jaw|muzzle',
        '(?<![a-z])(LCD|LED|EL)(?![a-z])',
        'magnet',
        'noses?|nostril',
        'paw ?pad|pads',
        'padd',
        'part(?!ial)s?|elements?',
        'paws?',
        'plush',
        'pocket',
        'props?',
        'sleeves?',
        'sneakers|sandals|feet',
        '(?<!de)tail',
        'wings?',
    ];

    /**
     * @throws UnexpectedResultException
     */
    #[Route(path: '/statistics.html', name: RouteName::STATISTICS)]
    #[Cache(maxage: 3600, public: true)]
    public function statistics(ArtisanRepository $artisanRepository, ArtisanCommissionsStatusRepository $commissionsStatusRepository, Species $species): Response
    {
        $productionModels = $artisanRepository->getDistinctProductionModels();
        $orderTypes = $artisanRepository->getDistinctOrderTypes();
        $otherOrderTypes = $artisanRepository->getDistinctOtherOrderTypes();
        $styles = $artisanRepository->getDistinctStyles();
        $otherStyles = $artisanRepository->getDistinctOtherStyles();
        $features = $artisanRepository->getDistinctFeatures();
        $otherFeatures = $artisanRepository->getDistinctOtherFeatures();
        $countries = $artisanRepository->getDistinctCountriesToCountAssoc();
        $commissionsStats = $commissionsStatusRepository->getCommissionsStats();
        $speciesStats = $species->getStats();

        return $this->render('statistics/statistics.html.twig', [
            'countries'        => $this->prepareTableData($countries),
            'productionModels' => $this->prepareTableData($productionModels),
            'orderTypes'       => $this->prepareTableData($orderTypes),
            'otherOrderTypes'  => $this->prepareListData($otherOrderTypes->getItems()),
            'styles'           => $this->prepareTableData($styles),
            'otherStyles'      => $this->prepareListData($otherStyles->getItems()),
            'features'         => $this->prepareTableData($features),
            'otherFeatures'    => $this->prepareListData($otherFeatures->getItems()),
            'commissionsStats' => $this->prepareCommissionsStatsTableData($commissionsStats),
            'completeness'     => $this->prepareCompletenessData($artisanRepository->getActive()),
            'providedInfo'     => $this->prepareProvidedInfoData($artisanRepository->getActive()),
            'speciesStats'     => $speciesStats,
            'matchWords'       => self::MATCH_WORDS,
        ]);
    }

    private function prepareTableData(FilterData $input): array
    {
        $result = [];

        foreach ($input->getItems() as $item) {
            if (!array_key_exists($item->getCount(), $result)) {
                $result[$item->getCount()] = [];
            }

            $result[$item->getCount()][] = $item->getLabel();
        }

        foreach ($result as $item => $items) {
            $result[$item] = implode(', ', $items);
        }

        $result = array_flip($result);
        arsort($result);

        foreach ($input->getSpecialItems() as $item) {
            $result[$item->getLabel()] = $item->getCount();
        }

        return $result;
    }

    /**
     * @return Item[]
     */
    private function prepareListData(Set $items): array
    {
        $result = $items->getItems();

        uksort($result, function ($keyA, $keyB) use ($items) {
            if ($items[$keyA]->getCount() !== $items[$keyB]->getCount()) {
                return $items[$keyB]->getCount() - $items[$keyA]->getCount();
            }

            return strcmp($items[$keyA]->getLabel(), $items[$keyB]->getLabel());
        });

        return $result;
    }

    private function prepareCommissionsStatsTableData(array $commissionsStats): array
    {
        return [
            'Open for anything'              => $commissionsStats['open_for_anything'],
            'Closed for anything'            => $commissionsStats['closed_for_anything'],
            'Status successfully tracked'    => $commissionsStats['successfully_tracked'],
            'Partially successfully tracked' => $commissionsStats['partially_tracked'],
            'Tracking failed completely'     => $commissionsStats['tracking_failed'],
            'Tracking issues'                => $commissionsStats['tracking_issues'],
            'Status tracked'                 => $commissionsStats['tracked'],
            'Total'                          => $commissionsStats['total'],
        ];
    }

    /**
     * @param Artisan[] $artisans
     */
    private function prepareCompletenessData(array $artisans): array
    {
        $completeness = array_filter(array_map(fn (Artisan $artisan) => $artisan->getCompleteness(), $artisans));

        $result = [];

        $levels = ['100%' => 100, '90-99%' => 90, '80-89%' => 80, '70-79%' => 70, '60-69%' => 60, '50-59%' => 50,
                 '40-49%' => 40,  '30-39%' => 30, '20-29%' => 20, '10-19%' => 10, '0-9%' => 0, ];

        foreach ($levels as $description => $level) {
            $result[$description] = count(array_filter($completeness, fn (int $percent) => $percent >= $level));

            $completeness = array_filter($completeness, fn (int $percent) => $percent < $level);
        }

        return $result;
    }

    private function prepareProvidedInfoData(array $artisans): array
    {
        $result = [];

        foreach (Fields::inStats() as $field) {
            $result[$field->name()] = array_reduce($artisans, function (int $carry, Artisan $artisan) use ($field): int {
                if ($field->is(Fields::FORMER_MAKER_IDS)) {
                    /* Some makers were added before introduction of the maker IDs. They were assigned fake former IDs,
                     * so we can rely on Artisan::getLastMakerId() etc. Those IDs are "M000000", where the digits part
                     * is zero-padded artisan database ID. */

                    $placeholder = sprintf('M%06d', $artisan->getId());

                    if ($artisan->get(Fields::FORMER_MAKER_IDS) === $placeholder) {
                        return $carry; // Fake former maker ID - don't add to the result
                    }
                }

                return $carry + ('' !== $artisan->get($field) ? 1 : 0);
            }, 0);
        }

        arsort($result);

        return $result;
    }
}
