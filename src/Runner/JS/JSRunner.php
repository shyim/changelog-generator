<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\JS;

use ChangelogGeneratorPlugin\Runner\Runner;
use Peast\Peast;
use Peast\Query;
use Peast\Syntax\Node\CallExpression;
use Peast\Syntax\Node\ExpressionStatement;
use Peast\Syntax\Node\MemberExpression;
use Peast\Syntax\Node\Node;
use Peast\Syntax\Node\Program;
use Peast\Syntax\Node\StringLiteral;

abstract class JSRunner extends Runner
{
    /**
     * @param string|array $source
     */
    final protected function parseSource($source): Program
    {
        if (\is_array($source)) {
            $source = \implode(\PHP_EOL, $source);
        }

        return Peast::latest($source, [
            'sourceType' => Peast::SOURCE_TYPE_MODULE,
            'comments' => true
        ])->parse();
    }

    final protected function isComponent(Program $ast): bool
    {
        return $this->getComponent($ast)->count() > 0;
    }

    final protected function getComponentName(Program $ast): string
    {
        $component = $this->getComponent($ast);

        /** @var ExpressionStatement $componentRegister */
        $componentRegister = $component->get(0);

        /** @var CallExpression $callExpression */
        $callExpression = $componentRegister->getExpression();

        // component name is first argument of Shopware.Component.register()
        $argument = $callExpression->getArguments()[0];

        return $argument->getValue();
    }

    private function getComponent(Program $ast): Query
    {
        return $ast
            ->query('ExpressionStatement:has(Identifier[name="Component"] + Identifier[name="register"])');
    }
}
