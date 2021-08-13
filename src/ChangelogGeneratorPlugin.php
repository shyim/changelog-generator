<?php declare(strict_types=1);

namespace ChangelogGeneratorPlugin;

use Shopware\Core\Framework\Plugin;

if (\file_exists(\dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once \dirname(__DIR__) . '/vendor/autoload.php';
}

class ChangelogGeneratorPlugin extends Plugin
{
}
