<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner;

use ChangelogGeneratorPlugin\Changelog\Message;

abstract class Runner implements RunnerInterface
{
    private static array $sections = [];

    protected function addSection(
        string $message,
        FileState $fileState
    ): void {
        self::$sections[$fileState->section][] = new Message(
            $message,
            $fileState,
            static::getSubject()
        );
    }

    public function getSections(): array
    {
        return self::$sections;
    }
}
