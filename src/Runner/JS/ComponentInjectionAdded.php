<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\JS;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;
use Peast\Syntax\Node\ArrayExpression;
use Peast\Syntax\Node\StringLiteral;

class ComponentInjectionAdded extends JSRunner
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

            $injectBefore = $astBefore->query('Property > Identifier[name="inject"] + ArrayExpression');
            $injectAfter = $astAfter->query('Property > Identifier[name="inject"] + ArrayExpression');

            $befores = [];
            $afters = [];

            /** @var ArrayExpression $array */
            foreach ($injectBefore as $array) {

                /** @var StringLiteral $injection */
                foreach ($array->getElements() as $injection) {
                    $befores[] = $injection->getValue();
                }
            }

            /** @var ArrayExpression $array */
            foreach ($injectAfter as $array) {

                /** @var StringLiteral $injection */
                foreach ($array->getElements() as $injection) {
                    $afters[] = $injection->getValue();
                }
            }

            foreach ($afters as $after) {
                if (!\in_array($after, $befores)) {
                    $this->addSection(
                        \sprintf(
                            "Added injection `%s` in `%s`",
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
        return 'js_component_injection_added';
    }
}
