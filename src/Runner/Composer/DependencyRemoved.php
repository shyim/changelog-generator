<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\Composer;

use ChangelogGeneratorPlugin\Runner\FileState;

class DependencyRemoved extends ComposerRunner
{
    public function process(FileState $fileState): void
    {
        [$beforeDependencies, $afterDependencies] = $this->getDependencies($fileState);

        foreach ($beforeDependencies as $beforeDependency => $beforeVersion) {
            if (!\array_key_exists($beforeDependency, $afterDependencies)) {
                $this->addSection(
                    \sprintf('Dropped dependency `%s`', $beforeDependency),
                    $fileState
                );
            }
        }
    }

    public function getSubject(): string
    {
        return 'composer_dependency_removed';
    }
}
