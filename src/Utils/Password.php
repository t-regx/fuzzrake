<?php

declare(strict_types=1);

namespace App\Utils;

use App\Utils\Artisan\SmartAccessDecorator as Artisan;
use App\Utils\Traits\UtilityClass;

final class Password
{
    use UtilityClass;

    public static function encryptOn(Artisan $artisan): void
    {
        // grep-password-algorithms
        $artisan->setPassword(password_hash(
            $artisan->getPassword(),
            PASSWORD_DEFAULT,
            ['cost' => 12],
        ));
    }
}
