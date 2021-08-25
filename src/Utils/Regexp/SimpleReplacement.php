<?php

declare(strict_types=1);

namespace App\Utils\Regexp;

use TRegx\CleanRegex\Pattern;

class SimpleReplacement implements ReplacementInterface
{
    private Pattern $pattern;

    public function __construct(
        string $pattern,
        string $flags,
        private string $replacement,
    ) {
        $this->pattern = pattern($pattern, $flags);
    }

    public function do(string $input): string
    {
        return $this->pattern->replace($input)->all()->withReferences($this->replacement);
    }
}
