<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Command;

use ChangelogGeneratorPlugin\Configuration\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangelogListRunnersCommand extends Command
{
    protected static $defaultName = 'changelog:list-runners';

    private Configuration $configuration;

    public function __construct(
        Configuration $configuration
    ) {
        $this->configuration = $configuration;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'playbook',
            'p',
            InputOption::VALUE_OPTIONAL,
            'Playbook of which to show runners'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $table = new Table($output);

        $table->setHeaders(['Playbook', 'Runner', 'Priority']);
        $table->setStyle('box-double');

        $index = 0;

        foreach ($this->configuration->playbooks as $playbook) {

            $table->addRow([\strtoupper($playbook->name), '', '']);

            foreach ($playbook->getRunnersFlatList() as $runner => $priority) {
                $table->addRow(['', $runner, $priority]);
            }

            if (++$index < \sizeof($this->configuration->playbooks)) {
                $table->addRow([new TableSeparator(), new TableSeparator(), new TableSeparator()]);
            }
        }

        $table->render();
    }
}
