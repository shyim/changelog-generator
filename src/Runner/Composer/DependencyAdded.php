<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\Composer;

use ChangelogGeneratorPlugin\Runner\FileState;

class DependencyAdded extends ComposerRunner
{
    public function process(FileState $fileState): void
    {
        [$beforeDependencies, $afterDependencies] = $this->getDependencies($fileState);

        foreach ($afterDependencies as $afterDependency => $afterVersion) {
            if (!\array_key_exists($afterDependency, $beforeDependencies)) {
                $this->addSection(
                    \sprintf('Added dependency `%s` with version `%s`', $afterDependency, $afterVersion),
                    $fileState
                );
            }
        }
    }

    public function getSubject(): string
    {
        return 'composer_dependency_added';
    }
}
