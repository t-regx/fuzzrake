<?php

declare(strict_types=1);

namespace App\Utils\Filters;

use App\Utils\Traits\UtilityClass;

final class SpecialItems
{
    use UtilityClass;

    public static function newUnknown(int $initialValue = 0): SpecialItem
    {
        $result = new SpecialItem('_u', '?', 'Unknown', 'fas fa-question-circle'); // grep-special-value-unknown
        $result->incCount($initialValue);

        return $result;
    }

    public static function newOther(): SpecialItem
    {
        return new SpecialItem('_o', '*', 'Other', 'fas fa-asterisk'); // grep-special-value-other
    }

    public static function newTrackingIssues(int $initialValue): SpecialItem
    {
        $result = new SpecialItem('_ti', '!', 'Tracking issues', 'fa fa-exclamation-triangle'); // grep-special-value-tracking-issues
        $result->incCount($initialValue);

        return $result;
    }

    public static function newNotTracked(int $initialValue): SpecialItem
    {
        $result = new SpecialItem('_nt', '-', 'Not tracked', 'fas fa-question-circle'); // grep-special-value-not-tracked
        $result->incCount($initialValue);

        return $result;
    }
}
