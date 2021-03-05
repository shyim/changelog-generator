<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\Composer;

use ChangelogGeneratorPlugin\Runner\FileState;
use Composer\Semver\Comparator;

class DependencyBumpedUp extends ComposerRunner
{
    public function process(FileState $fileState): void
    {
        [$beforeDependencies, $afterDependencies] = $this->getDependencies($fileState);

        foreach ($beforeDependencies as $beforeDependency => $beforeVersion) {
            if (\array_key_exists($beforeDependency, $afterDependencies)) {
                $afterVersion = $afterDependencies[$beforeDependency];

                if (Comparator::greaterThan($this->parseVersion($afterVersion), $this->parseVersion($beforeVersion))) {
                    $this->addSection(
                        \sprintf(
                            'Bumped up dependency `%s` from version `%s` to version `%s`',
                            $beforeDependency,
                            $beforeVersion,
                            $afterVersion
                        ),
                        $fileState
                    );
                }
            }
        }
    }

    public function getSubject(): string
    {
        return 'composer_dependency_bumped_up';
    }
}
