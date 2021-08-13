<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\PHP;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;

class MethodReturnTypeRemoved extends PHPRunner
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

            if ($preReturnType && !$afterReturnType) {
                $this->addSection(
                    \sprintf(
                        'Removed return type `%s` for method `%s::%s`',
                        $preReturnType,
                        $this->getClassFQCN($afterStmts),
                        $name
                    ),
                    $fileState
                );
            }
        }
    }

    public function getSubject(): string
    {
        return 'php_method_return_type_removed';
    }
}
