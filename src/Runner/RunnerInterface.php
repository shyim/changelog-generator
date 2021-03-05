<?php

declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner;

interface RunnerInterface
{
    public function process(FileState $fileState): void;
    public function canProcess(FileState $fileState): bool;
    public function getSubject(): string;
}
