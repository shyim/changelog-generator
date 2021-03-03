<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\PHP;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;

class MethodSignatureChanged extends PHPRunner
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

            if ($this->getReturnType($preMethod->returnType) !== $this->getReturnType($afterMethod->returnType)) {
                $this->addSection(
                    \sprintf(
                        'Changed return value for method `%s::%s` from `%s` to `%s`',
                        $this->getClassFQCN($afterStmts),
                        $name,
                        $this->getReturnType($preMethod->returnType),
                        $this->getReturnType($afterMethod->returnType)
                    ),
                    $this->getNamespaceSection($afterStmts)
                );
            }

            $preParams = $this->getParameters($preMethod->getParams());
            $afterParams = $this->getParameters($afterMethod->getParams());

            foreach ($preParams as $parameterName => $preParam) {
                if (!isset($afterParams[$parameterName])) {
                    $this->addSection(
                        \sprintf(
                            'Removed parameter `$%s` from method `%s::%s`',
                            $parameterName,
                            $this->getClassFQCN($afterStmts),
                            $name,
                        ),
                        $this->getNamespaceSection($afterStmts)
                    );

                    // parameter was removed, no need to do further checks
                    continue;
                }

                $afterParam = $afterParams[$parameterName];

                if ($this->getParameterType($preParam) !== $this->getParameterType($afterParam)) {
                    $this->addSection(
                        \sprintf(
                            'Changed parameter `$%s` type from `%s` to `%s` of method `%s::%s`',
                            $parameterName,
                            $this->getParameterType($preParam),
                            $this->getParameterType($afterParam),
                            $this->getClassFQCN($afterStmts),
                            $name
                        ),
                        $this->getNamespaceSection($afterStmts)
                    );
                }
            }

            foreach ($afterParams as $parameterName => $afterParam) {
                if (isset($preParams[$parameterName])) {
                    continue;
                }

                $type = $this->getParameterType($afterParam);

                if ($type[0] === '?') {
                    $this->addSection(
                        \sprintf(
                            'Added new optional parameter `$%s` to method `%s::%s`',
                            $parameterName,
                            $this->getClassFQCN($afterStmts),
                            $name
                        ),
                        $this->getNamespaceSection($afterStmts)
                    );
                } else {
                    $this->addSection(
                        \sprintf(
                            'Added new parameter `$%s` to method `%s::%s`',
                            $parameterName,
                            $this->getClassFQCN($afterStmts),
                            $name
                        ),
                        $this->getNamespaceSection($afterStmts)
                    );
                }
            }
        }
    }
}
