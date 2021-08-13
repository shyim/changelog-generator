<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\JS;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;

class ComponentAdded extends JSRunner
{
    public function canProcess(FileState $fileState): bool
    {
        return $fileState->extension === 'js' && $fileState->state === State::ADDED;
    }

    public function process(FileState $fileState): void
    {
        $ast = $this->parseSource($fileState->after);

        if ($this->isComponent($ast)) {
            $this->addSection(
                \sprintf("Added component `%s`", $this->getComponentName($ast)),
                $fileState
            );
        }
    }

    public function getSubject(): string
    {
        return 'js_component_added';
    }
}
