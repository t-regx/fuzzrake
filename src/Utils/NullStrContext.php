<?php

declare(strict_types=1);

namespace App\Utils;

class NullStrContext implements StrContextInterface
{
    use NullObjectTrait;

    public function getBefore(): string
    {
        return '';
    }

    public function getSubject(): string
    {
        return '';
    }

    public function getAfter(): string
    {
        return '';
    }

    public function asString(): string
    {
        return self::STR_REPRESENTATION_SEPARATOR.self::STR_REPRESENTATION_SEPARATOR;
    }
}
