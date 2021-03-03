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

        $this->addSection(
            \sprintf(
                'Added class `%s`', $this->getClassFQCN($afterStmt)
            ),
            $this->getNamespaceSection($afterStmt)
        );
    }
}
