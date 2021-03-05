<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\JS;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;
use Peast\Syntax\Node\CallExpression;
use Peast\Syntax\Node\StringLiteral;

class ComponentMixinAdded extends JSRunner
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

            $mixinsBefore = $astBefore->query('Property > Identifier[name="mixins"] + ArrayExpression');
            $mixinsAfter = $astAfter->query('Property > Identifier[name="mixins"] + ArrayExpression');

            $befores = [];
            $afters = [];

            /** @var CallExpression $mixinsCall */
            foreach ($mixinsBefore->find('CallExpression') as $mixinsCall) {

                /** @var StringLiteral $mixin */
                $mixin = $mixinsCall->getArguments()[0];
                $befores[] = $mixin->getValue();
            }

            /** @var CallExpression $mixinsCall */
            foreach ($mixinsAfter->find('CallExpression') as $mixinsCall) {

                /** @var StringLiteral $mixin */
                $mixin = $mixinsCall->getArguments()[0];
                $afters[] = $mixin->getValue();
            }

            foreach ($afters as $after) {
                if (!\in_array($after, $befores)) {
                    $this->addSection(
                        \sprintf(
                            "Added mixin `%s` in `%s`",
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
        return 'js_component_mixin_added';
    }
}
