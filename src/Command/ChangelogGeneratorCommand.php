<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Command;

use ChangelogGeneratorPlugin\Runner\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangelogGeneratorCommand extends Command
{
    protected static $defaultName = 'changelog:generate';

    private Generator $generator;

    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
        parent::__construct();
    }

    protected function configure()
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->generator->run();
    }
}
