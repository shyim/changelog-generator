<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\JS;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;
use Peast\Syntax\Node\Identifier;
use Peast\Syntax\Node\ObjectExpression;
use Peast\Syntax\Node\SpreadElement;

class ComponentMethodAdded extends JSRunner
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

            $methodsBefore = $astBefore->query('Property > Identifier[name="methods"] + ObjectExpression');
            $methodsAfter = $astAfter->query('Property > Identifier[name="methods"] + ObjectExpression');

            $befores = [];
            $afters = [];

            /** @var ObjectExpression $method */
            foreach ($methodsBefore as $method) {
                foreach ($method->getProperties() as $property) {

                    // skip unpacking "...()" thingys
                    if ($property instanceof SpreadElement) {
                        continue;
                    }

                    /** @var Identifier $identifier */
                    $identifier = $property->getKey();
                    $befores[] = $identifier->getName();
                }
            }

            /** @var ObjectExpression $method */
            foreach ($methodsAfter as $method) {
                foreach ($method->getProperties() as $property) {

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
                            "Added method `%s` in `%s`",
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
        return 'js_component_method_added';
    }
}
