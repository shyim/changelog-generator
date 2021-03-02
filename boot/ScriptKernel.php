<?php

class ScriptKernel extends \Shopware\Development\Kernel
{
    public function registerBundles()
    {
        yield from parent::registerBundles();
        yield new PublicBundle();
    }
}
