<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\Twig;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\Runner;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;
use Twig\Source;

abstract class TwigRunner extends Runner
{
    protected Environment $twig;

    public function __construct(Environment $twig)
    {
        if (!$twig->hasExtension(StringLoaderExtension::class)) {
            $twig->addExtension(new StringLoaderExtension());
        }

        $this->twig = $twig;
    }

    /**
     * @return Source[]
     */
    final protected function prepareTwigSources(FileState $fileState): array
    {
        return [
            new Source(
                $this->getTemplateForLoader($fileState->before),
                \pathinfo($fileState->path, \PATHINFO_BASENAME),
                $fileState->path
            ),
            new Source(
                $this->getTemplateForLoader($fileState->after),
                \pathinfo($fileState->path, \PATHINFO_BASENAME),
                $fileState->path
            )
        ];
    }

    final protected function getTemplateForLoader(array $fileChanges): string
    {
        // replace nasty twig js shit with empty string, so parser won't break
        return \preg_replace('/{{.*}}/', '', \implode(\PHP_EOL, $fileChanges));
    }
}
