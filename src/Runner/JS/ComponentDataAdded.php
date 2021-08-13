<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\JS;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;
use Peast\Syntax\Node\Identifier;

class ComponentDataAdded extends JSRunner
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

            foreach ($afters as $after) {
                if (!\in_array($after, $befores)) {
                    $this->addSection(
                        \sprintf(
                            "Added component data `%s` in `%s`",
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
        return 'js_component_data_added';
    }
}
