<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9ac67348c41601b204b2f48694febc0c
{
    public static $classMap = array (
        'ShamanHead\\PhpPorser\\App\\Analyzer' => __DIR__ . '/../..' . '/app/Analyzer.php',
        'ShamanHead\\PhpPorser\\App\\Children' => __DIR__ . '/../..' . '/app/Children.php',
        'ShamanHead\\PhpPorser\\App\\Dom' => __DIR__ . '/../..' . '/app/Dom.php',
        'ShamanHead\\PhpPorser\\App\\DomText' => __DIR__ . '/../..' . '/app/DomText.php',
        'ShamanHead\\PhpPorser\\App\\Element' => __DIR__ . '/../..' . '/app/Element.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit9ac67348c41601b204b2f48694febc0c::$classMap;

        }, null, ClassLoader::class);
    }
}
