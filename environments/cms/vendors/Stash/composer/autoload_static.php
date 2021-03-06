<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6399406aec6565f1885ca9b993b5ddcb
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Stash\\' => 6,
        ),
        'P' => 
        array (
            'Psr\\Cache\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Stash\\' => 
        array (
            0 => __DIR__ . '/..' . '/tedivm/stash/src/Stash',
        ),
        'Psr\\Cache\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/cache/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit6399406aec6565f1885ca9b993b5ddcb::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit6399406aec6565f1885ca9b993b5ddcb::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
