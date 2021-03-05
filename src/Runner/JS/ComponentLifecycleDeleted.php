<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\JS;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;
use Peast\Syntax\Node\Identifier;

class ComponentLifecycleDeleted extends JSRunner
{
    private const LIFECYCLE_HOOKS = [
        'beforeCreate',
        'created',
        'beforeMount',
        'mounted',
        'beforeUpdate',
        'updated',
        'activated',
        'deactivated',
        'beforeUnmount',
        'unmounted'
    ];

    public function canProcess(FileState $fileState): bool
    {
        return $fileState->extension === 'js' && $fileState->state === State::MODIFIED;
    }

    public function process(FileState $fileState): void
    {
        $astAfter = $this->parseSource($fileState->after);

        if ($this->isComponent($astAfter)) {

            $astBefore = $this->parseSource($fileState->before);

            $query = \sprintf(
                'Property > %s',
                \implode(', ',
                    \array_map(function($hook) {
                        return \sprintf('Identifier[name="%s"]', $hook);
                    }, self::LIFECYCLE_HOOKS)
                )
            );

            $lifeCyclesBefore = $astBefore->query($query);
            $lifeCyclesAfter = $astAfter->query($query);

            $befores = [];
            $afters = [];

            foreach ($lifeCyclesBefore as $lifeCycle) {
                if ($lifeCycle instanceof Identifier)  {
                    $befores[] = $lifeCycle->getName();
                }
            }

            foreach ($lifeCyclesAfter as $lifeCycle) {
                if ($lifeCycle instanceof Identifier)  {
                    $afters[] = $lifeCycle->getName();
                }
            }

            foreach ($befores as $before) {
                if (!\in_array($before, $afters)) {
                    $this->addSection(
                        \sprintf(
                            "Removed lifecycle hook `%s` from component `%s`",
                            $before,
                            $fileState->path
                        ),
                        $fileState
                    );
                }
            }
        }
    }

    public function getSubject(): string
    {
        return 'js_component_lifecycle_deleted';
    }
}
