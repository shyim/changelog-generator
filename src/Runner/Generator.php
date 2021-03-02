<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner;

use PhpParser\Parser;
use PhpParser\ParserFactory;

class Generator
{
    /**
     * @var RunnerInterface[]
     */
    private array $runners;

    private string $repositoryPath;
    private Parser $parser;

    public function __construct(iterable $runners, string $repositoryPath)
    {
        $this->repositoryPath = $repositoryPath;
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->runners = [];

        foreach ($runners as $runner) {
            if (!$runner instanceof RunnerInterface) {
                continue;
            }

            $this->runners[] = $runner;
        }
    }

    public function run(): string
    {
        $files = $this->getModifiedFiles();

        if (!$files) {
            return '';
        }

        foreach ($this->runners as $runner) {
            $runner->process($files);
        }

        return '';
    }

    private function getModifiedFiles(): FileStateCollection
    {
        $files = new FileStateCollection();

        \exec(\sprintf('git -C %s log HEAD^..HEAD --pretty=format: --name-status', $this->repositoryPath), $diffs);

        if (!$diffs) {
            return $files;
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
            $files->add($fileState);
        }

        return $files;
    }
}
