<?php

declare(strict_types=1);

namespace App\Utils\Data;

use App\DataDefinitions\Field;
use App\DataDefinitions\Fields;
use App\Entity\Artisan;
use App\Utils\StrUtils;
use InvalidArgumentException;

class FixerDifferValidator
{
    public const FIX = 1;
    public const SHOW_DIFF = 2;
    public const SHOW_ALL_FIX_CMD_FOR_CHANGED = 4;
    public const RESET_INVALID_PLUS_SHOW_FIX_CMD = 8;
    public const SHOW_FIX_CMD_FOR_INVALID = 16;
    public const USE_SET_FOR_FIX_CMD = 32;

    private Differ $differ;

    public function __construct(
        private Fixer $fixer,
        private Validator $validator,
        private Printer $printer,
    ) {
        $this->differ = new Differ($this->printer);
    }

    public function perform(ArtisanChanges $artisan, int $flags = 0, Artisan $imported = null): void
    {
        $artisan = $this->getArtisanFixWip($artisan);
        $anyDifference = $artisan->differs();

        foreach (Fields::persisted() as $field) {
            $this->printer->setCurrentContext($artisan);

            if ($flags & self::FIX) {
                $this->fixer->fix($artisan->getChanged(), $field);
            }

            if ($flags & self::SHOW_DIFF) {
                $this->differ->showDiff($field, $artisan->getSubject(), $artisan->getChanged(), $imported);
            }

            $isValid = !$field->isValidated() || $this->validator->isValid($artisan, $field);
            $resetAndShowFixCommand = $flags & self::RESET_INVALID_PLUS_SHOW_FIX_CMD && !$isValid;

            if ($anyDifference && $flags & self::SHOW_ALL_FIX_CMD_FOR_CHANGED
                || !$isValid && $flags & self::SHOW_FIX_CMD_FOR_INVALID
                || $resetAndShowFixCommand) {
                $this->printFixCommandOptionally($field, $artisan, $imported, $flags & self::USE_SET_FOR_FIX_CMD);
            }

            if ($resetAndShowFixCommand) {
                $artisan->getChanged()->set($field, $artisan->getSubject()->get($field));
            }
        }
    }

    private function printFixCommandOptionally(Field $field, ArtisanChanges $artisan, ?Artisan $imported, $useSetForFixCmd): void
    {
        if (!$this->hideFixCommandFor($field)) {
            $original = $imported ?? $artisan->getSubject();
            $originalVal = StrUtils::strSafeForCli($original->get($field));
            if (!$this->validator->isValid($artisan, $field)) {
                $originalVal = Printer::formatInvalid($originalVal);
            }

            $proposedVal = StrUtils::strSafeForCli($artisan->getChanged()->get($field)) ?: 'NEW_VALUE';

            if ($useSetForFixCmd) {
                $fixCmd = Manager::CMD_SET." {$field->name()} |$proposedVal|";
            } else {
                $fixCmd = Manager::CMD_REPLACE." {$field->name()} |$originalVal| |$proposedVal|";
            }

            $this->printer->writeln(Printer::formatFix("    $fixCmd"));
        }
    }

    private function hideFixCommandFor(Field $field): bool
    {
        return in_array($field->name(), [
            Fields::CONTACT_ALLOWED,
            Fields::CONTACT_METHOD,
            Fields::CONTACT_INFO_OBFUSCATED,
            Fields::CONTACT_ADDRESS_PLAIN,
        ]);
    }

    private function getArtisanFixWip(Artisan | ArtisanChanges $artisan): ArtisanChanges
    {
        if ($artisan instanceof Artisan) {
            $artisan = new ArtisanChanges($artisan);
        } elseif (!($artisan instanceof ArtisanChanges)) {
            throw new InvalidArgumentException();
        }

        return $artisan;
    }
}
