<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\JS;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;

class ComponentDeleted extends JSRunner
{
    public function canProcess(FileState $fileState): bool
    {
        return $fileState->extension === 'js' && $fileState->state === State::DELETED;
    }

    public function process(FileState $fileState): void
    {
        $ast = $this->parseSource($fileState->before);

        if ($this->isComponent($ast)) {
            $this->addSection(
                \sprintf("Removed component `%s`", $this->getComponentName($ast)),
                $fileState
            );
        }
    }

    public function getSubject(): string
    {
        return 'js_component_deleted';
    }
}
