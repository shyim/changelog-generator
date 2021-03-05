<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\PHP;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;

class MethodParameterTypeAdded extends PHPRunner
{
    public function canProcess(FileState $fileState): bool
    {
        return $fileState->extension === 'php' && $fileState->state === State::MODIFIED;
    }

    public function process(FileState $fileState): void
    {
        $preStmts = $this->parser->parse(\implode(\PHP_EOL, $fileState->before));
        $afterStmts = $this->parser->parse(\implode(\PHP_EOL, $fileState->after));

        $preMethods = $this->findMethods($preStmts);
        $afterMethods = $this->findMethods($afterStmts);

        foreach ($preMethods as $name => $preMethod) {
            // skip removed methods
            if (!\array_key_exists($name, $afterMethods)) {
                continue;
            }

            $afterMethod = $afterMethods[$name];

            $preParams = $this->getParameters($preMethod->getParams());
            $afterParams = $this->getParameters($afterMethod->getParams());

            foreach ($preParams as $parameterName => $preParam) {
                if (!\array_key_exists($parameterName, $afterParams)) {
                    continue;
                }

                $afterParam = $afterParams[$parameterName];

                if (!$this->getParameterType($preParam) && $this->getParameterType($afterParam)) {
                    $this->addSection(
                        \sprintf(
                            'Added type `%s` to parameter `$%s` of method `%s::%s`',
                            $this->getParameterType($afterParam),
                            $parameterName,
                            $this->getClassFQCN($afterStmts),
                            $name
                        ),
                        $fileState
                    );
                }
            }
        }
    }

    public function getSubject(): string
    {
        return 'php_method_parameter_type_added';
    }
}
