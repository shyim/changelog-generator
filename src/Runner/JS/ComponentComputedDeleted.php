<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\JS;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;
use Peast\Syntax\Node\Identifier;
use Peast\Syntax\Node\ObjectExpression;
use Peast\Syntax\Node\SpreadElement;

class ComponentComputedDeleted extends JSRunner
{
    public function canProcess(FileState $fileState): bool
    {
        return $fileState->extension === 'js' && $fileState->state === State::MODIFIED;
    }

    public function process(FileState $fileState): void
    {
        $astAfter = $this->parseSource($fileState->after);

        if ($this->isComponent($astAfter)) {

            $astBefore = $this->parseSource($fileState->before);

            $computedBefore = $astBefore->query('Property > Identifier[name="computed"] + ObjectExpression');
            $computedAfter = $astAfter->query('Property > Identifier[name="computed"] + ObjectExpression');

            $befores = [];
            $afters = [];

            /** @var ObjectExpression $computed */
            foreach ($computedBefore as $computed) {
                foreach ($computed->getProperties() as $property) {

                    // skip unpacking "...()" thingys
                    if ($property instanceof SpreadElement) {
                        continue;
                    }

                    /** @var Identifier $identifier */
                    $identifier = $property->getKey();
                    $befores[] = $identifier->getName();
                }
            }

            /** @var ObjectExpression $computed */
            foreach ($computedAfter as $computed) {
                foreach ($computed->getProperties() as $property) {

                    // skip unpacking "...()" thingys
                    if ($property instanceof SpreadElement) {
                        continue;
                    }

                    /** @var Identifier $identifier */
                    $identifier = $property->getKey();
                    $afters[] = $identifier->getName();
                }
            }

            foreach ($befores as $before) {
                if (!\in_array($before, $afters)) {
                    $this->addSection(
                        \sprintf(
                            "Removed computed property `%s` in `%s`",
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
        return 'js_component_computed_deleted';
    }
}
