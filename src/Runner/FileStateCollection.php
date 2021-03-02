<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin\Runner;

use Shopware\Core\Framework\Struct\Collection;

class FileStateCollection extends Collection
{
    public function getExpectedClass(): ?string
    {
        return FileState::class;
    }

    public function filterByState(string $state): array
    {
        return \array_filter($this->getElements(), function (FileState $fileState) use($state) {
            return $fileState->state === $state;
        });
    }
}
