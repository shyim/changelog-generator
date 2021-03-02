<?php

declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner;

interface RunnerInterface
{
    public function process(FileStateCollection $collection): array;
}
