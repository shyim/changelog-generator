<?php

declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner;

final class State
{
    public const ADDED = 'A';
    public const BROKEN = 'B';
    public const COPIED = 'C';
    public const DELETED = 'D';
    public const MODIFIED = 'M';
    public const MODE = 'T';
    public const RENAMED = 'R';
    public const UNKNOWN = 'X';
    public const UNMERGED = 'U';

    public const STATES = [
        self::ADDED,
        self::BROKEN,
        self::COPIED,
        self::DELETED,
        self::MODIFIED,
        self::MODE,
        self::RENAMED,
        self::UNKNOWN,
        self::UNMERGED
    ];
}
;
