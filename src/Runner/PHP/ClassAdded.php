<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\PHP;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;

class ClassAdded extends PHPRunner
{
    public function canProcess(FileState $fileState): bool
    {
        return $fileState->extension === 'php' && $fileState->state === State::ADDED;
    }

    public function process(FileState $fileState): void
    {
        $afterStmt = $this->parser->parse(\implode(PHP_EOL, $fileState->after));

        $fqcn = $this->getClassFQCN($afterStmt);

        if ($fqcn) {
            $this->addSection(
                \sprintf(
                    'Added class `%s`', $fqcn
                ),
                $fileState
            );
        } else {
            $this->addSection(
                \sprintf(
                    'Added file `%s`', $fileState->path
                ),
                $fileState
            );
        }
    }

    public function getSubject(): string
    {
        return 'php_class_added';
    }
}
