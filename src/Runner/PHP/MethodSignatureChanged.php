<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\PHP;

use ChangelogGeneratorPlugin\Changelog\Change;
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

            $preReturnType = $this->getReturnType($preMethod->returnType);
            $afterReturnType = $this->getReturnType($afterMethod->returnType);

            if ($preReturnType !== $afterReturnType) {
                if (!$preReturnType) {
                    $this->addSection(
                        \sprintf(
                            'Added return type `%s` for method `%s::%s`',
                            $afterReturnType,
                            $this->getClassFQCN($afterStmts),
                            $name
                        ),
                        $this->getNamespaceSection($afterStmts),
                        Change::ADDED
                    );
                } elseif (!$afterReturnType) {
                    $this->addSection(
                        \sprintf(
                            'Removed return type `%s` for method `%s::%s`',
                            $preReturnType,
                            $this->getClassFQCN($afterStmts),
                            $name
                        ),
                        $this->getNamespaceSection($afterStmts),
                        Change::REMOVED
                    );
                } else {
                    $this->addSection(
                        \sprintf(
                            'Changed return type for method `%s::%s` from `%s` to `%s`',
                            $this->getClassFQCN($afterStmts),
                            $name,
                            $this->getReturnType($preMethod->returnType),
                            $this->getReturnType($afterMethod->returnType)
                        ),
                        $this->getNamespaceSection($afterStmts),
                        Change::MODIFIED
                    );
                }
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
                        $this->getNamespaceSection($afterStmts),
                        Change::REMOVED
                    );

                    // parameter was removed, no need to do further checks
                    continue;
                }

                $afterParam = $afterParams[$parameterName];

                if ($this->getParameterType($preParam) !== $this->getParameterType($afterParam)) {
                    if (!$this->getParameterType($preParam)) {
                        $this->addSection(
                            \sprintf(
                                'Added type `%s` to parameter `$%s` of method `%s::%s`',
                                $this->getParameterType($afterParam),
                                $parameterName,
                                $this->getClassFQCN($afterStmts),
                                $name
                            ),
                            $this->getNamespaceSection($afterStmts),
                            Change::ADDED
                        );
                    } elseif (!$this->getParameterType($afterParam)) {
                        $this->addSection(
                            \sprintf(
                                'Removed type `%s` from parameter `$%s` of method `%s::%s`',
                                $this->getParameterType($preParam),
                                $parameterName,
                                $this->getClassFQCN($afterStmts),
                                $name
                            ),
                            $this->getNamespaceSection($afterStmts),
                            Change::REMOVED
                        );
                    } else {
                        $this->addSection(
                            \sprintf(
                                'Changed parameter `$%s` type from `%s` to `%s` of method `%s::%s`',
                                $parameterName,
                                $this->getParameterType($preParam),
                                $this->getParameterType($afterParam),
                                $this->getClassFQCN($afterStmts),
                                $name
                            ),
                            $this->getNamespaceSection($afterStmts),
                            Change::MODIFIED
                        );
                    }
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
                        $this->getNamespaceSection($afterStmts),
                        Change::ADDED
                    );
                } else {
                    $this->addSection(
                        \sprintf(
                            'Added new parameter `$%s` to method `%s::%s`',
                            $parameterName,
                            $this->getClassFQCN($afterStmts),
                            $name
                        ),
                        $this->getNamespaceSection($afterStmts),
                        Change::ADDED
                    );
                }
            }
        }
    }
}
