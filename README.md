# File System for PHP

[![Latest Stable Version](https://poser.pugx.org/wilkques/filesystem/v/stable)](https://packagist.org/packages/wilkques/filesystem)
[![License](https://poser.pugx.org/wilkques/filesystem/license)](https://packagist.org/packages/wilkques/filesystem)

## Installation
`composer require wilkques/filesystem`

## How to use
```php
$filesystem = \Wilkques\FileSystem::make();

// or

$filesystem = filesystem();

// create file & put content
$filesystem->put('<file/to/path>', '<content>');

// put content
$filesystem->append('<file/to/path>', '<content>');

// prepend content
$filesystem->prepend('<file/to/path>', '<content>');

// get file content
$resolve = $filesystem->get('<file/to/path>');

var_dump(
    $resolve
);

// delete file
$resolve = $filesystem->delete('<file/to/path>');
```