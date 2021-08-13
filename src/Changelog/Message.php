<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Changelog;

use ChangelogGeneratorPlugin\Runner\FileState;

class Message
{
    public string $file;
    public string $section;
    public string $message;
    public string $subject;

    public function __construct(
        string $message,
        FileState $fileState,
        string $subject = 'unknown'
    ) {
        $this->file = $fileState->path;
        $this->section = $fileState->section;
        $this->message = $message;
        $this->subject = $subject;
    }
}
