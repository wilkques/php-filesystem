<?php

// Register Filesystem as a shared instance as early as possible (this file
// is loaded once via composer's "files" autoload, before any application
// code runs) — not inside Filesystem::make() itself. If registration only
// happened lazily there, any other class that gets Filesystem injected via
// constructor type-hint (e.g. Console, Config) before anyone happens to
// call Filesystem::make() first would resolve its own separate, unshared
// instance instead of the one everyone else gets.
if (!\Wilkques\Container\Container::getInstance()->bound('Wilkques\\Filesystem\\Filesystem')) {
    \Wilkques\Container\Container::getInstance()->singleton('Wilkques\\Filesystem\\Filesystem');
}

if (!function_exists('filesystem')) {
    /**
     * @return \Wilkques\Filesystem\Filesystem
     */
    function filesystem()
    {
        return \Wilkques\Filesystem\Filesystem::make();
    }
}