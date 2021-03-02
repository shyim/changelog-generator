<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\PHP;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\FileStateCollection;
use ChangelogGeneratorPlugin\Runner\State;

class ClassDeleted extends PHPRunner
{
    public function process(FileStateCollection $collection): array
    {
        $sections = [];

        /** @var FileState[] $files */
        $files = $collection->filter(function(FileState $fileState) {
            return $fileState->extension === 'php' && $fileState->state === State::DELETED;
        });

        foreach ($files as $file) {
            $sections[] = \sprintf(
                'Removed class %s', \str_replace('.' . $file->extension, '', $file->path)
            );
        }

        \sort($sections);

        return $sections;
    }

    private function wasDeprecated(): bool
    {

    }
}
