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
        if (!$this->generator->hasRunners()) {
            $output->writeln('<info>No runners configured</info>');

            return Command::SUCCESS;
        }

        $start = \microtime(true);
        $changelog = $this->generator->run();

        if (!$changelog) {
            $output->writeln('<error>No changelog written</error>');
            return Command::FAILURE;
        }

        $output->writeln('<success>Changelog written:</success>');
        $output->writeln($changelog->fileName);

        foreach ($changelog->sections as $section => $changes) {
            $output->writeln(\sprintf('Changes in %s:', $section));

            foreach ($changes as $change => $messages) {
                $output->writeln(\sprintf("%s:\t%d", $change, \count($messages)));
            }
        }

        $duration = \round((\microtime(true) - $start) * 1000, 2);

        $output->writeln('Took: ' . $duration . ' ms');

        return Command::SUCCESS;
    }

    private function changesCount(): int
    {

    }
}
