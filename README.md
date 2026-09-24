# File System for PHP

[![TESTS](https://github.com/wilkques/php-filesystem/actions/workflows/ci.yml/badge.svg)](https://github.com/wilkques/php-filesystem/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/wilkques/filesystem/v/stable)](https://packagist.org/packages/wilkques/filesystem)
[![License](https://poser.pugx.org/wilkques/filesystem/license)](https://packagist.org/packages/wilkques/filesystem)

English | [繁體中文](README_ZH.md)

A local-filesystem helper for PHP — file/directory read, write, copy, move, delete, and scanning, kept compatible all the way back to **PHP 5.3**.

## Requirements

- PHP >= 5.3 (tested against 5.3, 5.6, 7.0, 7.1, 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3)

## Installation

```
composer require wilkques/filesystem
```

## Usage

```php
use Wilkques\Filesystem\Filesystem;

$filesystem = Filesystem::make();

// or, via the global helper
$filesystem = filesystem();

// create file & put content
$filesystem->put('/path/to/file', 'content');

// get file content
$filesystem->get('/path/to/file'); // 'content'

// append / prepend
$filesystem->append('/path/to/file', ' more');
$filesystem->prepend('/path/to/file', 'first ');

// delete file
$filesystem->delete('/path/to/file');
```

`Filesystem::make()` (and the `filesystem()` global helper) always return the **same shared instance**, resolved through `Wilkques\Container\Container`. `new Filesystem()` works too when you want an independent, non-shared instance (this is what the test suite uses).

### Following symlinks

By default, directory scans (`directories()`, `files()`, `allFiles()`, `in()`) do **not** follow symlinks — a symlinked entry is listed but not descended into, which avoids symlink loops and scanning outside the intended tree.

```php
// per-instance, via the constructor
$filesystem = new Filesystem(true);

// per-instance, fluent
$filesystem = (new Filesystem())->followLinks();

// globally, for every container-resolved Filesystem (Filesystem::make(),
// filesystem(), or injected into another class's constructor) — register
// this before anything resolves Filesystem for the first time
use Wilkques\Container\Container;

Container::getInstance()
    ->when(Filesystem::class)
    ->needs('$followLinks')
    ->give(true);
```

## API Reference

Every method below has a runnable example, taken from the test suite (`tests/FilesystemTest.php`) and verified on PHP 5.3.10, 7.4, and 8.3.

| Method | Description | Example |
| --- | --- | --- |
| `exists($path)` | Determine if a file or directory exists. | `$fs->exists('/tmp/a.txt'); // true` |
| `missing($path)` | Inverse of `exists()`. | `$fs->missing('/tmp/nope.txt'); // true` |
| `isFile($file)` | Determine if the given path is a file. | `$fs->isFile('/tmp/a.txt'); // true` |
| `isDirectory($directory)` | Determine if the given path is a directory. | `$fs->isDirectory('/tmp'); // true` |
| `isReadable($path)` | Determine if the given path is readable. | `$fs->isReadable('/tmp/a.txt'); // true` |
| `isWritable($path)` | Determine if the given path is writable. | `$fs->isWritable('/tmp/a.txt'); // true` |
| `get($path, $lock = false)` | Get a file's contents; throws if it doesn't exist. `$lock = true` reads via `sharedGet()`. | `$fs->get('/tmp/a.txt'); // 'hello world'` |
| `sharedGet($path)` | Get a file's contents using a shared (read) lock. | `$fs->sharedGet('/tmp/a.txt'); // 'hello world'` |
| `put($path, $data, $lock = false)` | Write a file's contents (creates or overwrites). | `$fs->put('/tmp/a.txt', 'hello world');` |
| `replace($path, $content)` | Atomically replace a file's contents (write-then-rename). | `$fs->replace('/tmp/a.txt', 'new content');` |
| `replaceInFile($search, $replace, $path)` | Replace a given string within a file. | `$fs->replaceInFile('world', 'there', '/tmp/a.txt');` |
| `prepend($path, $data)` | Prepend content to a file (creates it if missing). | `$fs->prepend('/tmp/a.txt', 'first-'); // 'first-...'` |
| `append($path, $data)` | Append content to a file. | `$fs->append('/tmp/a.txt', '-last'); // '...-last'` |
| `delete($paths)` | Delete one file, or an array of files. | `$fs->delete('/tmp/a.txt'); // true` |
| `move($path, $target)` | Move (rename) a file. | `$fs->move('/tmp/a.txt', '/tmp/b.txt');` |
| `copy($path, $target)` | Copy a file. | `$fs->copy('/tmp/a.txt', '/tmp/b.txt');` |
| `chmod($path, $mode = null)` | Get (no `$mode`) or set the UNIX permissions of a path. | `$fs->chmod('/tmp/a.txt'); // '0644'` |
| `size($path)` | Get a file's size in bytes. | `$fs->size('/tmp/a.txt'); // 5` |
| `lastModified($path)` | Get a file's last-modified UNIX timestamp. | `$fs->lastModified('/tmp/a.txt'); // e.g. 1700000000` |
| `hash($path, $algorithm = 'md5')` | Get a file's hash. | `$fs->hash('/tmp/a.txt'); // md5_file()'s value` |
| `type($path)` | Get a path's type. | `$fs->type('/tmp/a.txt'); // 'file'`, `$fs->type('/tmp'); // 'dir'` |
| `mimeType($path)` | Get a file's MIME type. | `$fs->mimeType('/tmp/a.txt'); // 'text/plain'` |
| `name($path)` | File name without extension. | `$fs->name('/tmp/a.txt'); // 'a'` |
| `basename($path)` | File name with extension. | `$fs->basename('/tmp/a.txt'); // 'a.txt'` |
| `dirname($path)` | Parent directory path. | `$fs->dirname('/tmp/a.txt'); // '/tmp'` |
| `extension($path)` | File extension. | `$fs->extension('/tmp/a.txt'); // 'txt'` |
| `getRequire($path, $data = [])` | `require` a PHP file and return its value; `$data` is `extract()`-ed into its scope. | `$fs->getRequire('/tmp/config.php', ['x' => 5]); // whatever config.php returns` |
| `requireOnce($path, $data = [])` | `require_once` a PHP file (for side effects); returns `$this`. | `$fs->requireOnce('/tmp/bootstrap.php');` |
| `ensureDirectoryExists($path, $mode = 0755, $recursive = true)` | Create a directory if it doesn't already exist. | `$fs->ensureDirectoryExists('/tmp/nested/dir');` |
| `makeDirectory($path, $mode = 0755, $recursive = false, $force = false)` | Create a directory. | `$fs->makeDirectory('/tmp/dir'); // true` |
| `moveDirectory($from, $to, $overwrite = false)` | Move a directory. | `$fs->moveDirectory('/tmp/src', '/tmp/dst'); // true` |
| `copyDirectory($directory, $destination, $options = null)` | Recursively copy a directory. | `$fs->copyDirectory('/tmp/src', '/tmp/dst'); // true` |
| `deleteDirectory($directory, $preserve = false)` | Recursively delete a directory; `$preserve = true` empties it but keeps the directory itself. | `$fs->deleteDirectory('/tmp/dir'); // true` |
| `deleteDirectories($directory)` | Delete every immediate subdirectory of `$directory`. | `$fs->deleteDirectories('/tmp'); // true` |
| `cleanDirectory($directory)` | Empty a directory of all files and subdirectories, keeping the directory itself. | `$fs->cleanDirectory('/tmp/dir'); // true` |
| `directories($directory)` | List the immediate subdirectories of `$directory` (files are excluded). | `$fs->directories('/tmp'); // ['/tmp/sub1', '/tmp/sub2']` |
| `files($directory, $hidden = false)` | List the files directly inside `$directory` (non-recursive); dotfiles excluded unless `$hidden = true`. | `$fs->files('/tmp'); // ['/tmp/a.txt']` |
| `allFiles($directory, $hidden = false)` | List every file inside `$directory`, recursively. Flattens the whole tree into a single list — directory structure is lost. | `$fs->allFiles('/tmp'); // ['/tmp/a.txt', '/tmp/sub/b.txt', ...]` |
| `tree($directory, $hidden = false)` | Recursively walk `$directory`, **preserving** the directory structure: files map to their full path, subdirectories map to a nested array of the same shape. | `$fs->tree('/tmp'); // ['a.txt' => '/tmp/a.txt', 'sub' => ['b.txt' => '/tmp/sub/b.txt']]` |
| `glob($pattern, $flags = 0)` | `glob()` wrapper. | `$fs->glob('/tmp/*.txt'); // ['/tmp/a.txt', '/tmp/b.txt']` |
| `followLinks()` | Turn on symlink-following for this instance's directory scans (see above). | `$fs->followLinks();` |
| `make()` *(static)* | Resolve the shared `Filesystem` instance via the container. | `Filesystem::make();` |

### Lower-level directory-scanning machinery

`directories()`/`files()`/`allFiles()` are the methods you'll normally reach for. All three are built directly on `searchInDirectory()` only — deliberately **not** on `in()` (see below), so a shared/singleton `Filesystem` instance (like the one `make()`/`filesystem()` returns) can't leak state between unrelated callers.

| Method | Description |
| --- | --- |
| `normalizeDir($dir)` | Strip a trailing slash from a directory path (except `(s)ftp://` URLs). |
| `searchInDirectory($dir)` | Get a `RecursiveDirectoryIterator` for a single directory, honoring `followLinks()`. Stateless — a fresh iterator every call, nothing stored on `$this`. |

### `in()` — a separate, Symfony Finder-style API

`in($dirs)` is a different, opt-in way to use a `Filesystem` instance: instead of calling a method that returns an array, you stage one or more directories/glob patterns and then iterate the instance itself.

| Method | Description |
| --- | --- |
| `in($dirs)` | Resolve one or more directories/glob patterns and stage them for iteration (fluent, returns `$this`). **Accumulates across calls** — it merges into the instance's staged directory list instead of replacing it, so calling `in()` more than once (or reusing it on a shared instance) combines every call's directories into one scan. |
| `count()` / `getIterator()` | `Filesystem` implements `Countable`/`IteratorAggregate` over whatever `in()` staged, so `count($fs)` and `foreach ($fs as $entry)` work directly after calling `in()`. **Not recursive** — each staged directory is scanned one level deep only (like `files()`, not like `allFiles()`). |

Because `in()` accumulates on the instance, it's easy to leak directories between unrelated callers if you call it on the shared instance from `make()`/`filesystem()`. Prefer `new Filesystem()` for this API, or make sure nothing else touches the instance between your `in()` call(s) and reading the result.

## Testing

```
composer install
vendor/bin/phpunit
```

## License

MIT
