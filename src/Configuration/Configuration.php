<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Configuration;

class Configuration
{
    /** @var ConfigurationPlaybook[] */
    public array $playbooks;

    /** @var array<string> */
    public array $excludes;

    public string $activePlaybookName = 'shopware';
    public string $fromDiff = 'HEAD^';
    public string $toDiff = 'HEAD';

    public function __construct(array $playbooks)
    {
        $this->playbooks = $playbooks;
    }

    public function getActivePlaybook(): ConfigurationPlaybook
    {
        return $this->playbooks[$this->activePlaybookName];
    }
}
