<?php declare(strict_types=1);

use Shopware\Core\Framework\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PublicBundle extends Bundle
{
    protected $name = 'Public Bundle';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PublicCompilerPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_REMOVE);
    }
}

class PublicCompilerPass implements \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definitions = $container->getDefinitions();
        foreach ($definitions as $definition) {
            $definition->setPublic(true);
        }
    }
}
