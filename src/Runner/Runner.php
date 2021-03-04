<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner;

use ChangelogGeneratorPlugin\Changelog\Change;

abstract class Runner implements RunnerInterface
{
    private static array $sections = [];

    protected function addSection(string $message, string $section = 'default', string $state = Change::OTHER): void
    {
        self::$sections[$section][$state][] = $message;
    }

    public function getSections(): array
    {
        return self::$sections;
    }
}
