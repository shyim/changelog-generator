<?php

declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\PHP;

use ChangelogGeneratorPlugin\Runner\RunnerInterface;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;

abstract class PHPRunner implements RunnerInterface
{
    protected Parser $parser;
    protected NodeFinder $finder;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->finder = new NodeFinder();
    }
}
