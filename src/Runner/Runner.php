<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner;

abstract class Runner implements RunnerInterface
{
    private static array $sections = [];

    protected function addSection(string $message, string $section = 'default'): void
    {
        self::$sections[$section][] = $message;
    }

    public function getSections(): array
    {
        return self::$sections;
    }
}
