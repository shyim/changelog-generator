<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Changelog;

class Changelog
{
    public string $title;
    public string $issue;
    public ?string $author = null;
    public ?string $authorEmail = null;
    public ?string $authorGithub = null;

    public array $sections;
    public array $errors = [];

    public string $fileName;
    public string $fileContent;
}
