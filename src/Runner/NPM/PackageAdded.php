<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\NPM;

use ChangelogGeneratorPlugin\Runner\FileState;

class PackageAdded extends NPMRunner
{
    public function process(FileState $fileState): void
    {
        [$beforePackages, $afterPackages] = $this->getDependencies($fileState);

        foreach ($afterPackages as $afterPackage => $afterVersion) {
            if (!\array_key_exists($afterPackage, $beforePackages)) {

                $this->addSection(
                    \sprintf(
                        "Added npm package `%s` with version `%s`.",
                        $afterPackage,
                        $afterVersion
                    ),
                    $fileState
                );
            }
        }
    }

    public function getSubject(): string
    {
        return 'npm_package_added';
    }
}
