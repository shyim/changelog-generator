<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\PHP;

use ChangelogGeneratorPlugin\Changelog\Change;
use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;

class ClassDeleted extends PHPRunner
{
    public function canProcess(FileState $fileState): bool
    {
        return $fileState->extension === 'php' && $fileState->state === State::DELETED;
    }

    public function process(FileState $fileState): void
    {
        $beforeStmt = $this->parser->parse(\implode(PHP_EOL, $fileState->before));

        $this->addSection(
            \sprintf(
                'Removed class `%s`', $this->getClassFQCN($beforeStmt)
            ),
            $this->getNamespaceSection($beforeStmt),
            Change::REMOVED
        );
    }
}
