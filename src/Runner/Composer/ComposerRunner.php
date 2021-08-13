<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\Composer;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\Runner;
use ChangelogGeneratorPlugin\Runner\State;
use Composer\Semver\Comparator;

abstract class ComposerRunner extends Runner
{
    final public function canProcess(FileState $fileState): bool
    {
        // ignore root composer.json, as we want do define dependency changes in their sections
        if ($fileState->path === 'composer.json') {
            return false;
        }

        return $fileState->extension === 'json' &&
            $fileState->state === State::MODIFIED &&
            \pathinfo($fileState->path, \PATHINFO_FILENAME) === 'composer';
    }

    final protected function getDependencies(FileState $fileState): array
    {
        $before = \json_decode(\implode(\PHP_EOL, $fileState->before), true);
        $after = \json_decode(\implode(\PHP_EOL, $fileState->after), true);

        $beforeDependencies = $before['require'];
        $afterDependencies = $after['require'];

        if (\array_key_exists('require-dev', $before)) {
            $beforeDependencies = \array_merge($before['require'], $before['require-dev']);
        }

        if (\array_key_exists('require-dev', $after)) {
            $afterDependencies = \array_merge($after['require'], $after['require-dev']);
        }

        \ksort($beforeDependencies);
        \ksort($afterDependencies);

        foreach ($beforeDependencies as $beforeDependency => $version) {
            if ($this->skipDependency($beforeDependency)) {
                unset($beforeDependencies[$beforeDependency]);
            }
        }

        foreach ($afterDependencies as $afterDependency => $version) {
            if ($this->skipDependency($afterDependency)) {
                unset($afterDependencies[$afterDependency]);
            }
        }

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

    /**
     * some edge cases for skipping 'dependencies' like php
     */
    private function skipDependency(string $dependency): bool
    {
        if (\substr($dependency, 0, 4) === 'ext-') {
            return true;
        }

        if ($dependency === 'php') {
            return true;
        }

        return false;
    }
}
