<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Changelog;

use ChangelogGeneratorPlugin\Configuration\Configuration;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ChangelogBuilder
{
    private Environment $twig;
    private Configuration $configuration;
    private string $repositoryPath;

    public function __construct(string $templatePath, string $repositoryPath, Configuration $configuration)
    {
        $loader = new FilesystemLoader($templatePath);
        $twig = new Environment($loader);
        $this->configuration = $configuration;

        $this->twig = $twig;
        $this->repositoryPath = $repositoryPath;
    }

    public function buildChangelog(array $sections, array $errors = []): Changelog
    {
        $changelog = new Changelog();

        \exec(\sprintf('git -C %s rev-parse --symbolic-full-name --abbrev-ref HEAD', $this->repositoryPath), $branch);

        if (\strpos($branch[0], '/') === false) {
            $issue = 'NEXT-<ISSUE>';
            $title = '<TITLE>';
        } else {
            [$issue, $title] = \explode('/', $branch[0]);
        }

        $changelog->fileName = \sprintf(
            '%s-%s.md',
            (new \DateTime())->format('Y-m-d'),
            $title
        );

        $issue = \strtoupper($issue);
        $title = \ucfirst(\str_replace('-', ' ', $title));

        $changelog->issue = $issue;
        $changelog->title = $title;

        \exec(\sprintf('git -C %s show -s --format="%%an" HEAD', $this->repositoryPath), $author);
        \exec(\sprintf('git -C %s show -s --format="%%ae" HEAD', $this->repositoryPath), $authorEmail);

        $changelog->author = $author[0];
        $changelog->authorEmail = $authorEmail[0];
        $changelog->sections = $this->sortSectionsByPriority($sections);
        $changelog->fileContent = $this->twig->render('changelog.md.twig', ['changelog' => $changelog]);
        $changelog->errors = $errors;

        return $changelog;
    }

    private function sortSectionsByPriority(array $sections): array
    {
        $orderedRunners = $this->configuration->getActivePlaybook()->getRunnersFlatList();

        foreach ($sections as $section => $messages) {
            \usort($sections[$section], function(Message $a, Message $b) use ($orderedRunners) {
                return $orderedRunners[$a->subject] <=> $orderedRunners[$b->subject];
            });
        }

        return $sections;
    }
}
