<?php

use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

$container = require __DIR__ . '/boot/boot.php';

class ChangelogGenerator {
    private string $platformRepo;

    public function __construct()
    {
        global $container;
        $this->platformRepo = dirname($container->getParameter('kernel.root_dir'), 3) . '/platform';

        $sections = [];

        $this->generateAddedClassList($sections);
        $this->generateModifiedPhpChanges($sections);
        $this->generateDeletedClassList($sections);


        exec(sprintf('git -C %s branch --show-current', $this->platformRepo), $branch);
        [$ticket, $name] = explode('/', $branch[0]);

        echo sprintf('---
title: %s
issue: %s
---
', implode(' ', array_map('ucwords', explode('-', $name))), strtoupper($ticket));

        echo PHP_EOL;
        ksort($sections);

        foreach ($sections as $section => $contents) {
            echo '# ' . $section . PHP_EOL . PHP_EOL;

            foreach ($contents as $content) {
                echo $content . PHP_EOL;
            }

            echo PHP_EOL;
        }
    }

    public function generateModifiedPhpChanges(array &$sections): void
    {
        exec(sprintf('git -C %s log HEAD^..HEAD --pretty=format: --name-only --diff-filter=M', $this->platformRepo), $modifiedFiles);

        foreach (array_unique($modifiedFiles) as $modifiedFile) {
            if (strpos($modifiedFile, 'src') !== 0) {
                continue;
            }

            $section = explode('/', $modifiedFile)[1];

            if (!str_ends_with($modifiedFile, '.php')) {
                continue;
            }

            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
            $nodeFinder = new NodeFinder();

            // Before
            exec(sprintf('git -C %s show HEAD^:%s', $this->platformRepo, $modifiedFile), $content);
            $preStmts = $parser->parse(implode(PHP_EOL, $content));

            /** @var Class_ $preClass */
            $preClass = $nodeFinder->findFirstInstanceOf($preStmts, Class_::class);
            $preClassName = $this->getClassFQCN($preStmts);
            $content = null;

            // After
            exec(sprintf('git -C %s show HEAD:%s', $this->platformRepo, $modifiedFile), $content);
            $afterStmts = $parser->parse(implode(PHP_EOL, $content));

            /** @var Class_ $afterClass */
            $afterClass = $nodeFinder->findFirstInstanceOf($preStmts, Class_::class);
            $afterClassName = $this->getClassFQCN($afterStmts);
            $content = null;

            if ($preClass === null || $afterClass === null) {
                continue;
            }

            // Class Name changed
            if ($preClassName !== $afterClassName) {
                $sections[$section][] = sprintf('* Renamed class from `%s` to `%s`', $preClassName, $afterClassName);
            }

            // Check methods
            $preClassMethods = $this->findMethods($preStmts);
            $afterClassMethods = $this->findMethods($afterStmts);

            foreach ($preClassMethods as $name => $oldMethod) {
                if ($name === '__construct') {
                    continue;
                }

                if (!isset($afterClassMethods[$name])) {
                    $sections[$section][] = sprintf('* Removed method `%s:%s`', $afterClassName, $name);
                    continue;
                }

                $newMethod = $afterClassMethods[$name];

                if ($this->getReturnType($oldMethod->returnType) !== $this->getReturnType($oldMethod->returnType)) {
                    $sections[$section][] = sprintf(
                        '* Changed return value from method `%s:%s` from `%s` to `%s`',
                        $afterClassName,
                        $name,
                        $this->getReturnType($oldMethod->returnType),
                        $this->getReturnType($newMethod->returnType)
                    );
                }

                $preParams = $this->prepareParameters($oldMethod->getParams());
                $afterParams = $this->prepareParameters($newMethod->getParams());

                foreach ($preParams as $parameterName => $oldParameter) {
                    if (!isset($afterParams[$parameterName])) {
                        $sections[$section][] = sprintf(
                            '* Removed parameter `$%s` from method `%s:%s`',
                            $parameterName,
                            $afterClassName,
                            $name,
                        );
                        continue;
                    }

                    $newParameter = $afterParams[$parameterName];

                    if ($this->getParameterType($oldParameter) !== $this->getParameterType($newParameter)) {
                        $sections[$section][] = sprintf(
                            '* Changed parameter `$%s` type from `%s` to `%s` of method `%s:%s`',
                            $parameterName,
                            $this->getParameterType($oldParameter),
                            $this->getParameterType($newParameter),
                            $afterClassName,
                            $name,
                        );
                    }
                }

                foreach ($afterParams as $parameterName => $newParameter) {
                    if (isset($preParams[$parameterName])) {
                        continue;
                    }

                    $type = $this->getParameterType($newParameter);

                    if ($type[0] === '?') {
                        $sections[$section][] = sprintf(
                            '* Added new optional parameter `$%s` to method `%s:%s`',
                            $parameterName,
                            $afterClassName,
                            $name,
                        );
                    } else {
                        $sections[$section][] = sprintf(
                            '* Added new parameter `$%s` to method `%s:%s`',
                            $parameterName,
                            $afterClassName,
                            $name,
                        );
                    }
                }
            }

            foreach ($afterClassMethods as $name => $method) {
                if ($name === '__construct') {
                    continue;
                }

                if (isset($preClassMethods[$name])) {
                    continue;
                }

                $sections[$section][] = sprintf('* Added new method `%s:%s`', $afterClassName, $name);
            }
        }
    }

    public function generateAddedClassList(array &$sections): void
    {
        exec(sprintf('git -C %s log HEAD^..HEAD --pretty=format: --name-only --diff-filter=A', $this->platformRepo), $addedFiles);
        $addedClassList = [];

        foreach (array_unique($addedFiles) as $addedFile) {
            $section = explode('/', $addedFile)[1];

            if (!str_ends_with($addedFile, '.php')) {
                continue;
            }

            exec(sprintf('git -C %s show HEAD:%s', $this->platformRepo, $addedFile), $content);

            try {
                $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
                $stmts = $parser->parse(implode(PHP_EOL, $content));
                if ($className = $this->getClassFQCN($stmts)) {
                    $addedClassList[$section][] = $className;
                }
            } catch (Throwable $e) {
            }
            $content = null;
        }

        if (!empty($addedClassList)) {
            foreach ($addedClassList as $section => $files) {
                if (count($files) === 1) {
                    $sections[$section][] = sprintf('* Added following new class `%s`', $files[0]);
                } else {
                    $str = '* Added following new classes:' . PHP_EOL;
                    foreach ($files as $deletedFile) {
                        $str .= '    * `' . $deletedFile . '`' . PHP_EOL;
                    }

                    $sections[$section][] = substr($str, 0, -1);
                }
            }
        }
    }

    public function generateDeletedClassList(array &$sections): void
    {
        exec(sprintf('git -C %s log HEAD^..HEAD --pretty=format: --name-only --diff-filter=D', $this->platformRepo), $deletedFiles);
        $deletedClassList = [];
        $nonPhpFiles = [];

        foreach (array_unique($deletedFiles) as $deletedFile) {
            $section = explode('/', $deletedFile);
            if (!isset($section[1])) {
                continue;
            }
            $section = $section[1];

            if (!str_ends_with($deletedFile, '.php')) {
                $nonPhpFiles[$section][] = $deletedFile;
                continue;
            }

            exec(sprintf('git -C %s show HEAD^:%s', $this->platformRepo, $deletedFile), $content);

            try {
                $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
                $stmts = $parser->parse(implode(PHP_EOL, $content));

                if ($className = $this->getClassFQCN($stmts)) {
                    $deletedClassList[$section][] = $className;
                }
            } catch (Throwable $e) {
            }
            $content = null;
        }

        if (!empty($nonPhpFiles)) {
            foreach ($nonPhpFiles as $section => $files) {
                if (count($files) === 1) {
                    $sections[$section][] = sprintf('* Deleted following file `%s`', $files[0]);
                } else {
                    $str = '* Deleted following files:' . PHP_EOL;
                    foreach ($files as $deletedFile) {
                        $str .= '    * `' . $deletedFile . '`' . PHP_EOL;
                    }

                    $sections[$section][] = substr($str, 0, -1);
                }
            }
        }

        if (!empty($deletedClassList)) {
            foreach ($deletedClassList as $section => $files) {
                if (count($files) === 1) {
                    $sections[$section][] = sprintf('* Deleted following class `%s`', $files[0]);
                } else {
                    $str = '* Deleted following classes:' . PHP_EOL;
                    foreach ($files as $deletedFile) {
                        $str .= '    * `' . $deletedFile . '`' . PHP_EOL;
                    }

                    $sections[$section][] = substr($str, 0, -1);
                }
            }
        }
    }

    private function getClassFQCN(array $stmts): ?string
    {
        $nodeFinder = new NodeFinder();
        $class = $nodeFinder->findFirstInstanceOf($stmts, Class_::class);
        $namespace = $nodeFinder->findFirstInstanceOf($stmts, \PhpParser\Node\Stmt\Namespace_::class);

        if ($class === null) {
            return null;
        }

        $className = (string) $class->name;

        if ($namespace === null) {
            return $className;
        }

        $namespaceName = (string) $namespace->name;

        return $namespaceName . '\\' . $className;
    }

    /**
     * @return ClassMethod[]
     */
    private function findMethods(array $stmts): array
    {
        /** @var ClassMethod[] $methods */
        $methods = (new NodeFinder())->findInstanceOf($stmts, ClassMethod::class);
        $formattedMethods = [];

        foreach ($methods as $method) {
            if ($method->isPrivate()) {
                continue;
            }

            $formattedMethods[(string) $method->name] = $method;
        }

        return $formattedMethods;
    }

    /**
     * @param Param[] $parameters
     */
    private function prepareParameters(array $parameters): array
    {
        $formattedParameters = [];

        foreach ($parameters as $parameter) {
            $formattedParameters[(string) $parameter->var->name] = $parameter;
        }

        return $formattedParameters;
    }

    private function getParameterType(Param $param): string
    {
        if ($param->type instanceof \PhpParser\Node\NullableType) {
            return '?' . (string) $param->type->type;
        }

        return (string) $param->type;
    }

    public function getReturnType(?object $name): ?string
    {
        if ($name === null) {
            return null;
        }

        if ($name instanceof \PhpParser\Node\NullableType) {
            return '?' . (string) $name->type;
        }

        return (string) $name;
    }
}

new ChangelogGenerator();
