<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\NPM;

use ChangelogGeneratorPlugin\Runner\FileState;
use Composer\Semver\Comparator;

class PackageDroppedDown extends NPMRunner
{
    public function process(FileState $fileState): void
    {
        [$beforePackages, $afterPackages] = $this->getDependencies($fileState);

        foreach ($beforePackages as $beforePackage => $beforeVersion) {
            if (\array_key_exists($beforePackage, $afterPackages)) {

                $afterVersion = $afterPackages[$beforePackage];

                if (Comparator::lessThan($this->parseVersion($afterVersion), $this->parseVersion($beforeVersion))) {
                    $this->addSection(
                        \sprintf(
                            "Dropped down npm package `%s` from version `%s` to version `%s`.",
                            $beforePackage,
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
        return 'npm_package_dropped_down';
    }
}
