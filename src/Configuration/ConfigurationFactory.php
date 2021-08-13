<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Configuration;

use ChangelogGeneratorPlugin\Runner\Runner;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigurationFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function createConfiguration(string $configurationFile): Configuration
    {
        $config = Yaml::parseFile($configurationFile);
        $playbooks = [];

        foreach ($config['changelog']['playbooks'] as $name => $playbook) {
            foreach ($playbook['runners'] as $priority => $runner) {
                if ($this->container->has($runner)) {
                    $runnerService = $this->container->get($runner);

                    if ($runnerService instanceof Runner) {
                        $playbook['runners'][$priority] = $this->container->get($runner);
                        continue;
                    }
                }

                unset($playbook['runners'][$priority]);
            }

            $playbook = new ConfigurationPlaybook($name, $playbook['template'], $playbook['runners']);
            $playbooks[$name] = $playbook;
        }

        $configuration = new Configuration($playbooks);
        $configuration->excludes = $config['changelog']['excludes'];

        return $configuration;
    }
}
