<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\PHP;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;

class ClassRenamed extends PHPRunner
{
    public function canProcess(FileState $fileState): bool
    {
        return $fileState->extension === 'php' && $fileState->state === State::RENAMED;
    }

    public function process(FileState $fileState): void
    {
        $beforeStmt = $this->parser->parse(\implode(\PHP_EOL, $fileState->before));
        $afterStmt = $this->parser->parse(\implode(\PHP_EOL, $fileState->after));

        $this->addSection(
            \sprintf(
                'Renamed class `%s` to `%s`',
                $this->getClassFQCN($beforeStmt),
                $this->getClassFQCN($afterStmt)
            ),
            $fileState
        );
    }

    public function getSubject(): string
    {
        return 'php_class_renamed';
    }
}
