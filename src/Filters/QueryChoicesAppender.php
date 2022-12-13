<?php

declare(strict_types=1);

namespace App\Filters;

use App\DataDefinitions\Fields\Field;
use App\Repository\ArtisanRepository;
use App\Utils\StrUtils;
use Doctrine\ORM\QueryBuilder;

class QueryChoicesAppender
{
    public function __construct(
        private readonly Choices $choices,
        private readonly ArtisanRepository $repository,
    ) {
    }

    private function applyCountries(QueryBuilder $builder): void
    {
        if ([] !== $this->choices->countries) {
            $builder->andWhere('a.country IN (:countries)')->setParameter('countries', $this->choices->countries);
        }
    }

    private function applyStates(QueryBuilder $builder): void
    {
        if ([] !== $this->choices->states) {
            $builder->andWhere('a.state IN (:states)')->setParameter('states', $this->choices->states);
        }
    }

    public function applyChoices(QueryBuilder $builder): void
    {
        $this->applyCountries($builder);
        $this->applyStates($builder);
        $this->applyWantsSfw($builder);
        $this->applyIsWorksWithMinors($builder);
    }

    private function applyWantsSfw(QueryBuilder $builder): void
    {
        if (true !== $this->choices->isAdult || false !== $this->choices->wantsSfw) {
            $builder->andWhere($builder->expr()->exists(
                $this->repository->createQueryBuilder('a3')
                    ->select('1')
                    ->join('a3.values', 'a3v1')
                    ->join('a3.values', 'a3v2')
                    ->where('a3.id = a.id')
                    ->andWhere('a3v1.fieldName = :nsfwWebsite')
                    ->andWhere('a3v1.value = :a3vFalse')
                    ->andWhere('a3v2.fieldName = :nsfwSocial')
                    ->andWhere('a3v2.value = :a3vFalse')
            ))
                ->setParameter('nsfwWebsite', Field::NSFW_WEBSITE->value)
                ->setParameter('nsfwSocial', Field::NSFW_SOCIAL->value)
                ->setParameter('a3vFalse', StrUtils::asStr(false));
        }
    }

    private function applyIsWorksWithMinors(QueryBuilder $builder): void
    {
        if (true !== $this->choices->isAdult) {
            $builder->andWhere($builder->expr()->exists(
                $this->repository->createQueryBuilder('a2')
                    ->select('1')
                    ->join('a2.values', 'a2v')
                    ->where('a2.id = a.id')
                    ->andWhere('a2v.fieldName = :wwmFieldName')
                    ->andWhere('a2v.value = :a2vTrue')
            ))
                ->setParameter('wwmFieldName', Field::WORKS_WITH_MINORS->value)
                ->setParameter('a2vTrue', StrUtils::asStr(true));
        }
    }
}
