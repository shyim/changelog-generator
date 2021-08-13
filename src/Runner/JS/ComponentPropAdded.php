<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\JS;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;
use Peast\Syntax\Node\Identifier;
use Peast\Syntax\Node\ObjectExpression;
use Peast\Syntax\Node\SpreadElement;

class ComponentPropAdded extends JSRunner
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

            $propsBefore = $astBefore->query('Property > Identifier[name="props"] + ObjectExpression');
            $propsAfter = $astAfter->query('Property > Identifier[name="props"] + ObjectExpression');

            $befores = [];
            $afters = [];

            /** @var ObjectExpression $prop */
            foreach ($propsBefore as $prop) {
                foreach ($prop->getProperties() as $property) {

                    // skip unpacking "...()" thingys
                    if ($property instanceof SpreadElement) {
                        continue;
                    }

                    /** @var Identifier $identifier */
                    $identifier = $property->getKey();
                    $befores[] = $identifier->getName();
                }
            }

            /** @var ObjectExpression $prop */
            foreach ($propsAfter as $prop) {
                foreach ($prop->getProperties() as $property) {

                    // skip unpacking "...()" thingys
                    if ($property instanceof SpreadElement) {
                        continue;
                    }

                    /** @var Identifier $identifier */
                    $identifier = $property->getKey();
                    $afters[] = $identifier->getName();
                }
            }

            foreach ($afters as $after) {
                if (!\in_array($after, $befores)) {
                    $this->addSection(
                        \sprintf(
                            "Added prop `%s` in `%s`",
                            $after,
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
        return 'js_component_prop_added';
    }
}
