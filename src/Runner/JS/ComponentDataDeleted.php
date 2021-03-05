<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\JS;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;
use Peast\Syntax\Node\Identifier;

class ComponentDataDeleted extends JSRunner
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

            $dataBefore = $astBefore->query('Property > Identifier[name="data"] + FunctionExpression');
            $dataAfter = $astAfter->query('Property > Identifier[name="data"] + FunctionExpression');

            $befores = [];
            $afters = [];

            /** @var Identifier $property */
            foreach ($dataBefore->find('Property > Identifier') as $property) {
                $befores[] = $property->getName();
            }

            /** @var Identifier $property */
            foreach ($dataAfter->find('Property > Identifier') as $property) {
                $afters[] = $property->getName();
            }

            foreach ($befores as $before) {
                if (!\in_array($before, $afters)) {
                    $this->addSection(
                        \sprintf(
                            "Removed component data `%s` in `%s`",
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
        return 'js_component_data_deleted';
    }
}
