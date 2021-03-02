<?php

use Shopware\Core\Framework\Plugin\KernelPluginLoader\DbalKernelPluginLoader;
use Shopware\Development\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;

set_time_limit(0);

$classLoader = require __DIR__.' /../../../vendor/autoload.php';

require __DIR__ . '/deps/vendor/autoload.php';
require __DIR__ . '/ScriptKernel.php';
require __DIR__ . '/PublicBundle.php';

if (!class_exists(Application::class)) {
    throw new \RuntimeException('You need to add "symfony/framework-bundle" as a Composer dependency.');
}

if (!class_exists(Dotenv::class)) {
    throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
}

(new Dotenv())->load(__DIR__.'/../../../.env');

$input = new ArgvInput();
$env = 'test';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? ('prod' !== $env)) && !$input->hasParameterOption('--no-debug', true);

if ($debug) {
    umask(0000);

    if (class_exists(Debug::class)) {
        Debug::enable();
    }
}

function getCacheId(Connection $connection): string
{
    try {
        $cacheId = $connection->fetchColumn(
            'SELECT `value` FROM app_config WHERE `key` = :key',
            ['key' => 'cache-id']
        );
    } catch (\Exception $e) {
        return Uuid::randomHex();
    }

    return $cacheId ?? Uuid::randomHex();
}

$connection = Kernel::getConnection();

if ($env === 'dev') {
    $connection->getConfiguration()->setSQLLogger(
        new \Shopware\Core\Profiling\Doctrine\DebugStack()
    );
}

$shopwareVersion = '6.3.3.0';

$pluginLoader = new DbalKernelPluginLoader($classLoader, null, $connection);

$cacheId = getCacheId($connection);

$kernel = new ScriptKernel($env, $debug, $pluginLoader, $cacheId, $shopwareVersion, $connection);
$kernel->boot();

return $kernel->getContainer()->get('test.service_container');
