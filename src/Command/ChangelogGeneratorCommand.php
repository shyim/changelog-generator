<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Command;

use ChangelogGeneratorPlugin\Changelog\Changelog;
use ChangelogGeneratorPlugin\Changelog\Message;
use ChangelogGeneratorPlugin\Configuration\Configuration;
use ChangelogGeneratorPlugin\Runner\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangelogGeneratorCommand extends Command
{
    protected static $defaultName = 'changelog:generate';

    private Generator $generator;
    private Configuration $configuration;

    private string $changelogPath;

    public function __construct(
        Generator $generator,
        Configuration $configuration,
        string $changelogPath
    ) {
        $this->generator = $generator;
        $this->configuration = $configuration;
        $this->changelogPath = $changelogPath;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate changelog based on git diff')
            ->setHelp('Checks for changes between [FROM]..[TO] and generates a changelog')

            ->addUsage('--from v6.3.0.0')
            ->addUsage('--from origin/trunk --to HEAD')
            ->addUsage('--from 123456abcdef --to v6.3.5.1')
            ->addUsage('--from HEAD@{20} --playbook breaking')

            ->addOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Commit / Tag to git diff from',
                'origin/trunk'
            )

            ->addOption(
                'to',
                null,
                InputOption::VALUE_OPTIONAL,
                'Commit / Tag to git diff to',
                'HEAD'
            )

            ->addOption(
                'playbook',
                null,
                InputOption::VALUE_OPTIONAL,
                'Playbook to use | Define more in <info>src/Resources/config/changelog.yaml</info>',
                'shopware'
            )

            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Dry run outputs changes to the console only'
            )

            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite existing changelog files'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!\is_writable($this->changelogPath)) {
            $output->writeln(
                "<error>Directory " . $this->changelogPath . " is not writeable, aborting." . "</error>"
            );

            return Command::FAILURE;
        }

        if (!$this->configuration->getActivePlaybook()->runners) {
            $output->writeln('<info>No runners configured</info>');

            return Command::SUCCESS;
        }

        $this->configuration->fromDiff = (string) $input->getOption('from');
        $this->configuration->toDiff = (string) $input->getOption('to');
        $this->configuration->activePlaybookName = (string) $input->getOption('playbook');

        $output->writeln('<info>Detecting changes. This might take a while ...</info>');

        $progressBar = new ProgressBar($output);
        $progressBar->setBarCharacter('█');
        $progressBar->setProgressCharacter(' ');
        $progressBar->setEmptyBarCharacter(' ');

        $start = \microtime(true);
        $changelog = $this->generator->run($progressBar);
        $duration = \round((\microtime(true) - $start) * 1000, 4);

        $progressBar->finish();

        if (!$changelog) {
            $output->writeln('<error>No changelog written</error>');
            return Command::FAILURE;
        }

        $dryRun = (bool) $input->getOption('dry-run');

        if (!$dryRun) {
            if (\file_exists($this->changelogPath .\DIRECTORY_SEPARATOR . $changelog->fileName)) {
                if (!$input->getOption('force')) {
                    $output->writeln("<error>Existing changelog found, aborting. Use '--force' to overwrite.</error>");
                    return Command::FAILURE;
                }
            }

            \file_put_contents($this->changelogPath .\DIRECTORY_SEPARATOR . $changelog->fileName, $changelog->fileContent);
        }

        $output->writeln("");

        $this->renderTable($changelog, $output);

        if ($dryRun) {
            $output->writeln("<comment>No changelog written due to '--dry-run' option. Only outputting console result.</comment>");
        } else {
            $output->writeln("<comment>Changelog written to file.</comment>");
            $output->writeln(
                "<href=file://" . $this->changelogPath .\DIRECTORY_SEPARATOR . $changelog->fileName . ">" .
                        $this->changelogPath .\DIRECTORY_SEPARATOR . $changelog->fileName .
                        "</>"
            );
        }

        if ($changelog->errors) {
            $output->writeln("<error>There were errors during the changelog generation:</error>");

            foreach ($changelog->errors as $error) {
                $output->writeln(
                    \sprintf(
                        "<error>%s - %s: %s</error>",
                        $error['runner'],
                        $error['file'],
                        $error['message']
                    )
                );
            }
        }

        $output->writeln("<comment>Took: " . $duration . " ms</comment>");
        $output->writeln("");

        return Command::SUCCESS;
    }

    private function renderTable(Changelog $changelog, OutputInterface $output): void
    {
        $table = new Table($output);

        $table->setHeaders(['Component', 'Changelog']);
        $table->setStyle('box-double');

        $table->addRows([
            ['title', $changelog->title],
            ['issue', $changelog->issue]
        ]);

        if ($changelog->author) {
            $table->addRow(['author', $changelog->author]);
        }

        if ($changelog->authorEmail) {
            $table->addRow(['author_email', $changelog->authorEmail]);
        }

        if ($changelog->authorGithub) {
            $table->addRow(['author_github', $changelog->authorGithub]);
        }

        $table->addRow([new TableSeparator(), new TableSeparator()]);

        $lastSection = \array_search(\end($changelog->sections), $changelog->sections);
        $lastSubject = null;

        foreach ($changelog->sections as $section => $messages) {

            $table->addRow([$section]);
            $index = 0;

            /** @var Message $message */
            foreach ($messages as $message) {
                if ($lastSubject !== null && $lastSubject !== $message->subject) {
                    $table->addRow(['', '']);
                }

                $table->addRow([++$index, $message->message]);
                $lastSubject = $message->subject;
            }

            if ($section !== $lastSection) {
                $table->addRow([new TableSeparator(), new TableSeparator()]);
            }
        }

        $table->render();
    }
}
