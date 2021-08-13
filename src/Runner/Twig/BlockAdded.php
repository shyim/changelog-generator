<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner\Twig;

use ChangelogGeneratorPlugin\Runner\FileState;
use ChangelogGeneratorPlugin\Runner\State;
use Twig\Source;

class BlockAdded extends TwigRunner
{
    public function canProcess(FileState $fileState): bool
    {
        return $fileState->extension === 'twig' && $fileState->state === State::MODIFIED;
    }

    public function process(FileState $fileState): void
    {
        [$before, $after] = $this->prepareTwigSources($fileState);
        $beforeTokenized = $this->twig->tokenize($before);

        $blocksBefore = [];

        while (!$beforeTokenized->isEOF()) {
            $token = $beforeTokenized->getCurrent();

            if ($token->getValue() === 'block') {
                $beforeTokenized->next();
                $blocksBefore[] = $beforeTokenized->getCurrent()->getValue();
            }

            $beforeTokenized->next();
        }

        $afterTokenized = $this->twig->tokenize($after);

        $blocksAfter = [];

        while (!$afterTokenized->isEOF()) {
            $token = $afterTokenized->getCurrent();

            if ($token->getValue() === 'block') {
                $afterTokenized->next();
                $blocksAfter[] = $afterTokenized->getCurrent()->getValue();
            }

            $afterTokenized->next();
        }

        foreach ($blocksAfter as $block) {
            if (!\in_array($block, $blocksBefore)) {
                $this->addSection(
                    \sprintf(
                        'Added twig block `%s` in `%s`',
                        $block,
                        $fileState->path
                    ),
                    $fileState
                );
            }
        }
    }

    public function getSubject(): string
    {
        return 'twig_block_added';
    }
}
