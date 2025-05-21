<?php

if (!function_exists('filesystem')) {
    /**
     * @return \Wilkques\Filesystem\Filesystem
     */
    function filesystem()
    {
        return \Wilkques\Filesystem\Filesystem::make();
    }
}