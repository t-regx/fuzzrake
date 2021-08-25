<?php

declare(strict_types=1);

namespace App\Utils\Data\Validator;

use App\DataDefinitions\Field;
use App\Utils\Species\Species;
use App\Utils\StringList;

class SpeciesListValidator implements ValidatorInterface
{
    public function __construct(
        private Species $speciesService,
    ) {
    }

    public function isValid(Field $field, $subject): bool
    {
        foreach (StringList::unpack($subject) as $specie) {
            if (!in_array($specie, $this->speciesService->getValidNames())) {
                return false;
            }
        }

        return true;
    }
}
