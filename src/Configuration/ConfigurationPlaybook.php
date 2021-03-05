<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Configuration;

use ChangelogGeneratorPlugin\Runner\Runner;

class ConfigurationPlaybook
{
    public string $name;
    public string $template;

    /** @var array<int, string> */
    public array $runners;

    public function __construct(string $name, string $template, array $runners)
    {
        $this->name = $name;
        $this->template = $template;
        $this->runners = $runners;
    }

    public function getRunnersFlatList(): array
    {
        return \array_flip(\array_map(function(Runner $runner) {
            return $runner->getSubject();
        }, $this->runners));
    }
}
