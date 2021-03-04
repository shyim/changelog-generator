<?php

declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\PHP;

use ChangelogGeneratorPlugin\Runner\Runner;
use PhpParser\Comment\Doc;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeDumper;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;

abstract class PHPRunner extends Runner
{
    protected Parser $parser;
    protected NodeFinder $finder;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->finder = new NodeFinder();
    }

    final protected function getClassFQCN(array $stmts): ?string
    {
        $class = $this->finder->findFirstInstanceOf($stmts, Class_::class);
        $namespace = $this->finder->findFirstInstanceOf($stmts, Namespace_::class);

        if ($class === null) {
            $class = $this->finder->findFirstInstanceOf($stmts, Interface_::class);

            if ($class === null) {
                return null;
            }
        }

        $className = (string) $class->name;

        if ($namespace === null) {
            return $className;
        }

        $namespaceName = (string) $namespace->name;

        return $namespaceName . '\\' . $className;
    }

    final protected function getNamespaceSection(array $stmts): string
    {
        /** @var Namespace_ $namespace */
        $namespace = $this->finder->findFirstInstanceOf($stmts, Namespace_::class);

        if ($namespace && $namespace->name && $parts = $namespace->name->parts) {
            $partsWithoutRoot = \array_diff($parts, ['Shopware']);
            return \array_shift($partsWithoutRoot);
        }

        return 'Other';
    }

    /**
     * @return ClassMethod[]
     */
    final protected function findMethods(array $stmts): array
    {
        /** @var ClassMethod[] $methods */
        $methods = $this->finder->findInstanceOf($stmts, ClassMethod::class);
        $formattedMethods = [];

        foreach ($methods as $name => $method) {
            if ($name === '__construct') {
                continue;
            }

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
    final protected function getParameters(array $parameters): array
    {
        $formattedParameters = [];

        foreach ($parameters as $parameter) {
            $formattedParameters[(string) $parameter->var->name] = $parameter;
        }

        return $formattedParameters;
    }

    final protected function getReturnType(?object $name): ?string
    {
        if ($name === null) {
            return null;
        }

        if ($name instanceof NullableType) {
            return '?' . (string) $name->type;
        }

        return (string) $name;
    }

    final protected function getParameterType(Param $param): string
    {
        if ($param->type instanceof NullableType) {
            return '?' . (string) $param->type->type;
        }

        return (string) $param->type;
    }

    final protected function getDeprecations(Stmt $stmt): array {
        $regex = '/^\s*\*\s*@(?:(feature-)?deprecated)\s*(?:\([A-z0-9\_\:\-\s]*\))?\s*tag:v(?<version>(?:\d\.?){2,4})\s*\-*\s*(?<message>.*)$/m';

        if ($stmt->getDocComment()) {
            #dd($stmt->getDocComment());
        }

        return [];
    }
}
