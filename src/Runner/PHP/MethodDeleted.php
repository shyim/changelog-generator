<?php

declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\PHP;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\FileStateCollection;
use ChangelogGeneratorPlugin\Runner\State;
use PhpParser\Node\Stmt\ClassMethod;

class MethodDeleted extends PHPRunner
{
    public function process(FileStateCollection $collection): array
    {
        $sections = [];

        /** @var FileState[] $files */
        $files = $collection->filter(function(FileState $fileState) {
            return $fileState->extension === 'php' && $fileState->state === State::MODIFIED;
        });

        foreach ($files as $file) {
            $before = $this->parser->parse(\implode(\PHP_EOL, $file->before));
            $after = $this->parser->parse(\implode(\PHP_EOL, $file->after));

            $beforeMethods = $this->findMethods($before);
            $afterMethods = $this->findMethods($after);

            foreach ($beforeMethods as $name => $beforeMethod) {
                if ($name === '__construct') {
                    continue;
                }

                if (!isset($afterMethods[$name])) {
                    $sections[] = \sprintf('* Removed method `%s:%s`', $afterMethods, $name);
                }
            }
        }

        return $sections;
    }

    /**
     * @return ClassMethod[]
     */
    private function findMethods(array $stmts): array
    {
        /** @var ClassMethod[] $methods */
        $methods = $this->finder->findInstanceOf($stmts, ClassMethod::class);
        $formattedMethods = [];

        foreach ($methods as $method) {
            if ($method->isPrivate()) {
                continue;
            }

            $formattedMethods[(string) $method->name] = $method;
        }

        return $formattedMethods;
    }
}
