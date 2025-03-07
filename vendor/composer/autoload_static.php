<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit108edfd1a1a8b1b4770c9c41501e3f2e
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Stripe\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Stripe\\' => 
        array (
            0 => __DIR__ . '/..' . '/stripe/stripe-php/lib',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit108edfd1a1a8b1b4770c9c41501e3f2e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit108edfd1a1a8b1b4770c9c41501e3f2e::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
