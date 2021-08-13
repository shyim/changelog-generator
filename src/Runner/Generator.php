<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner;

use ChangelogGeneratorPlugin\Changelog\Changelog;
use ChangelogGeneratorPlugin\Changelog\ChangelogBuilder;
use ChangelogGeneratorPlugin\Configuration\Configuration;
use Symfony\Component\Console\Helper\ProgressBar;

class Generator
{
    private ChangelogBuilder $changelogBuilder;
    private Configuration $configuration;

    private string $repositoryPath;

    public function __construct(
        string $repositoryPath,
        ChangelogBuilder $changelogBuilder,
        Configuration $configuration
    ) {
        $this->repositoryPath = $repositoryPath;
        $this->changelogBuilder = $changelogBuilder;
        $this->configuration = $configuration;
    }

    public function run(ProgressBar $bar): ?Changelog
    {
        $errors = [];

        /** @var Runner[] $runners */
        $runners = $this->configuration->getActivePlaybook()->runners;

        if (!$runners) {
            throw new \RuntimeException('Playbook ' . $this->configuration->activePlaybookName . ' has no runners configured, aborting !');
        }

        $files = $this->getModifiedFiles();

        if (!$files) {
            throw new \RuntimeException('No changes in range of commits, aborting !');
        }

        $bar->setMaxSteps(\sizeof($files));
        $bar->start();

        foreach ($files as $file) {
            foreach ($runners as $runner) {
                if ($runner->canProcess($file)) {
                    try {
                        $runner->process($file);
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'file' => $file->path,
                            'runner' => \get_class($runner),
                            'message' => $e->getMessage()
                        ];

                        continue;
                    }
                }
            }

            $bar->advance();
        }

        $sections = $runner->getSections();

        if (!$sections) {
            throw new \RuntimeException('Generator has detected no changes, aborting !');
        }

        foreach ($sections as $key => $section) {
            \ksort($sections[$key]);
        }

        return $this->changelogBuilder->buildChangelog($sections, $errors);
    }

    /**
     * @return FileState[]
     */
    private function getModifiedFiles(): array
    {
        $files = [];

        \exec(
            \escapeshellcmd(
                \sprintf(
                    "git -C %s diff %s..%s --name-status",
                    $this->repositoryPath,
                    $this->configuration->fromDiff,
                    $this->configuration->toDiff
                )
            ),
            $diffs
        );

        if (!$diffs) {
            return [];
        }

        foreach (\array_unique($diffs) as $diff) {
            if (!$diff) {
                continue;
            }

            [$status, $file] = \explode("\t", $diff, 2);
            $extension = \pathinfo($file, PATHINFO_EXTENSION);

            $namespaces = \array_map('strtolower', \explode(\DIRECTORY_SEPARATOR, $file));

            foreach ($this->configuration->excludes as $exclude) {
                if (\strpos($file, $exclude)) {
                    continue;
                }
            }

            // skip tests
            if (\in_array('test', $namespaces)) {
                continue;
            }

            // unknown git state modifier
            if (!\in_array($status, State::STATES))  {
                continue;
            }

            $before = [];
            $after = [];

            if ($status !== State::ADDED) {
                \exec(
                    \escapeshellcmd(
                        \sprintf(
                            "git -C %s show %s:%s",
                            $this->repositoryPath,
                            $this->configuration->fromDiff,
                            $file
                        )
                    ),
                    $before
                );
            }

            if ($status !== State::DELETED) {
                \exec(
                    \escapeshellcmd(
                        \sprintf(
                            "git -C %s show %s:%s",
                            $this->repositoryPath,
                            $this->configuration->toDiff,
                            $file
                        )
                    ),
                    $after
                );
            }

            $fileState = new FileState($status, $file, $extension, $before, $after);
            $files[] = $fileState;
        }

        return $files;
    }
}
