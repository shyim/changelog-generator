<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf3acefba73f2985cb2a1ec0565c2d5b6
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Peast\\test\\' => 11,
            'Peast\\' => 6,
        ),
        'C' => 
        array (
            'ChangelogGeneratorPlugin\\' => 25,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Peast\\test\\' => 
        array (
            0 => __DIR__ . '/..' . '/mck89/peast/test/Peast',
        ),
        'Peast\\' => 
        array (
            0 => __DIR__ . '/..' . '/mck89/peast/lib/Peast',
        ),
        'ChangelogGeneratorPlugin\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitf3acefba73f2985cb2a1ec0565c2d5b6::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitf3acefba73f2985cb2a1ec0565c2d5b6::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitf3acefba73f2985cb2a1ec0565c2d5b6::$classMap;

        }, null, ClassLoader::class);
    }
}
