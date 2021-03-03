<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner;

use PhpParser\Parser;
use PhpParser\ParserFactory;

class Generator
{
    /**
     * @var Runner[]
     */
    private array $runners;

    private string $repositoryPath;
    private string $changelogPath;
    private Parser $parser;

    public function __construct(iterable $runners, string $repositoryPath, string $changelogPath)
    {
        $this->repositoryPath = $repositoryPath;
        $this->changelogPath = $changelogPath;
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->runners = [];

        foreach ($runners as $runner) {
            if (!$runner instanceof RunnerInterface) {
                continue;
            }

            $this->runners[] = $runner;
        }
    }

    public function run(): ?string
    {
        $files = $this->getModifiedFiles();

        if (!$this->runners || !$files) {
            return null;
        }

        foreach ($files as $file) {
            foreach ($this->runners as $runner) {
                if ($runner->canProcess($file)) {
                    $runner->process($file);
                }
            }
        }

        $sections = $runner->getSections();

        foreach ($sections as $key => $section) {
            \sort($sections[$key]);
        }

        if (!\is_writable($this->changelogPath)) {
            throw new \RuntimeException(
                \sprintf('Directory %s is not writeable', $this->changelogPath)
            );
        }

        \exec(\sprintf('git -C %s rev-parse --symbolic-full-name --abbrev-ref HEAD', $this->repositoryPath), $branch);
        [$ticket, $name] = \explode('/', $branch[0]);

        dd(\strtoupper($ticket));

        dd($sections);

        return $sections;
    }

    public function hasRunners(): bool
    {
        return \count($this->runners) > 0;
    }

    /**
     * @return FileState[]
     */
    private function getModifiedFiles(): array
    {
        $files = [];

        \exec(\sprintf('git -C %s log HEAD^..HEAD --pretty=format: --name-status', $this->repositoryPath), $diffs);

        if (!$diffs) {
            return [];
        }

        foreach (\array_unique($diffs) as $diff) {
            if (!$diff) {
                continue;
            }

            [$status, $file] = \explode("\t", $diff);
            $extension = \pathinfo($file, PATHINFO_EXTENSION);

            if (!\in_array($status, State::STATES))  {
                continue;
            }

            $before = [];
            $after = [];

            if ($status !== State::ADDED) {
                \exec(sprintf('git -C %s show HEAD^:%s', $this->repositoryPath, $file), $before);
            }

            if ($status !== State::DELETED) {
                \exec(sprintf('git -C %s show HEAD:%s', $this->repositoryPath, $file), $after);
            }

            $fileState = new FileState($status, $file, $extension, $before, $after);
            $files[] = $fileState;
        }

        return $files;
    }
}
