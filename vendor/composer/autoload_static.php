<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9638c8e80988e36561a054c46f5f131f
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'Automattic\\WooCommerce\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Automattic\\WooCommerce\\' => 
        array (
            0 => __DIR__ . '/..' . '/automattic/woocommerce/src/WooCommerce',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9638c8e80988e36561a054c46f5f131f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9638c8e80988e36561a054c46f5f131f::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}