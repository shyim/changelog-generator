<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\NPM;

use ChangelogGeneratorPlugin\Runner\FileState;

class PackageRemoved extends NPMRunner
{
    public function process(FileState $fileState): void
    {
        [$beforePackages, $afterPackages] = $this->getDependencies($fileState);

        foreach ($beforePackages as $beforePackage => $beforeVersion) {
            if (!\array_key_exists($beforePackage, $afterPackages)) {

                $this->addSection(
                    \sprintf(
                        "Dropped npm package `%s`.",
                        $beforePackage
                    ),
                    $fileState
                );
            }
        }
    }

    public function getSubject(): string
    {
        return 'npm_package_removed';
    }
}
