<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Changelog;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ChangelogBuilder
{
    private Environment $twig;
    private string $repositoryPath;

    public function __construct(string $templatePath, string $repositoryPath)
    {
        $loader = new FilesystemLoader($templatePath);
        $twig = new Environment($loader);

        $this->twig = $twig;
        $this->repositoryPath = $repositoryPath;
    }

    public function buildChangelog(array $sections): Changelog
    {
        $changelog = new Changelog();

        \exec(\sprintf('git -C %s rev-parse --symbolic-full-name --abbrev-ref HEAD', $this->repositoryPath), $branch);
        [$issue, $title] = \explode('/', $branch[0]);

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

        $changelog->sections = $sections;
        $changelog->fileContent = $this->twig->render('changelog.md.twig', ['changelog' => $changelog]);

        return $changelog;
    }
}
