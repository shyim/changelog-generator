<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner;

use PhpParser\Node;

class FileState
{
    public string $state;
    public string $path;
    public string $extension;
    public string $section;

    /** @var Node[] */
    public array $before;

    /** @var Node[] */
    public array $after;

    public function __construct(string $state, string $path, string $extension, $before, $after)
    {
        $this->state = $state;
        $this->path = $path;
        $this->extension = $extension;

        $sectionPaths = \explode(\DIRECTORY_SEPARATOR, $path);

        if (\array_key_exists(1, $sectionPaths)) {
            $this->section = $sectionPaths[1];
        } else {
            $this->section = 'Other';
        }

        if (!\is_array($before)) {
            $before = [$before];
        }

        if (!\is_array($after)) {
            $after = [$after];
        }

        $this->before = $before;
        $this->after = $after;
    }
}
