<?php

declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\PHP;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;

class MethodDeleted extends PHPRunner
{
    public function canProcess(FileState $fileState): bool
    {
        return $fileState->extension === 'php' && $fileState->state === State::MODIFIED;
    }

    public function process(FileState $fileState): void
    {
        $beforeStmt = $this->parser->parse(\implode(\PHP_EOL, $fileState->before));
        $afterStmt = $this->parser->parse(\implode(\PHP_EOL, $fileState->after));

        $beforeMethods = $this->findMethods($beforeStmt);
        $afterMethods = $this->findMethods($afterStmt);

        foreach ($beforeMethods as $name => $beforeMethod) {
            if (!isset($afterMethods[$name])) {
                $class = $this->getClassFQCN($beforeStmt);
                $this->addSection(
                    \sprintf('Removed method `%s::%s`', $class, $name),
                    $fileState
                );
            }
        }
    }

    public function getSubject(): string
    {
        return 'php_method_deleted';
    }
}
