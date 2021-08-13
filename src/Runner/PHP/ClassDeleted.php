<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\PHP;

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

        $fqcn = $this->getClassFQCN($beforeStmt);

        if ($fqcn) {
            $this->addSection(
                \sprintf(
                    'Removed class `%s`', $fqcn
                ),
                $fileState
            );
        } else {
            $this->addSection(
                \sprintf(
                    'Removed file `%s`', $fileState->path
                ),
                $fileState
            );
        }
    }

    public function getSubject(): string
    {
        return 'php_class_deleted';
    }
}
