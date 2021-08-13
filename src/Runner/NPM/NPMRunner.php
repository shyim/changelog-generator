<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\NPM;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\Runner;
use ChangelogGeneratorPlugin\Runner\State;
use Composer\Semver\Comparator;

abstract class NPMRunner extends Runner
{
    final public function canProcess(FileState $fileState): bool
    {
        return $fileState->extension === 'json' &&
            $fileState->state === State::MODIFIED &&
            \pathinfo($fileState->path, \PATHINFO_FILENAME) === 'package';
    }

    final protected function getDependencies(FileState $fileState): array
    {
        $before = \json_decode(\implode(\PHP_EOL, $fileState->before), true);
        $after = \json_decode(\implode(\PHP_EOL, $fileState->after), true);

        $beforeDependencies = $before['dependencies'];
        $afterDependencies = $after['dependencies'];

        if (\array_key_exists('devDependencies', $before)) {
            $beforeDependencies = \array_merge($before['dependencies'], $before['devDependencies']);
        }

        if (\array_key_exists('devDependencies', $after)) {
            $afterDependencies = \array_merge($after['dependencies'], $after['devDependencies']);
        }

        \ksort($beforeDependencies);
        \ksort($afterDependencies);

        return [$beforeDependencies, $afterDependencies];
    }

    final public function parseVersion(string $version): ?string
    {
        \preg_match_all('/(?:\d\.(?:\d\.?)*)+/', $version, $versions);

        $versions = $versions[0];

        if (!$versions) {
            return null;
        }

        $maxVersion = '0.0.0';

        foreach ($versions as $version) {
            if (Comparator::greaterThan($version, $maxVersion)) {
                $maxVersion = $version;
            }
        }

        return $maxVersion;
    }

}
