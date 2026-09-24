<?php

namespace Wilkques\Filesystem;

use Wilkques\Helpers\Arrays;
use Wilkques\Helpers\Strings;

class Filesystem implements \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    private $dirs = array();

    /**
     * @var bool
     */
    protected $followLinks = false;

    /**
     * @param bool $followLinks
     */
    public function __construct($followLinks = false)
    {
        $this->followLinks = $followLinks;
    }

    /**
     * @return static
     */
    public static function make()
    {
        $container = \Wilkques\Container\Container::getInstance();

        if (!$container->bound(__CLASS__)) {
            $container->singleton(__CLASS__);
        }

        return $container->make(__CLASS__);
    }

    /**
     * @return static
     */
    public function followLinks()
    {
        $this->followLinks = true;

        return $this;
    }

    /**
     * Determine if a file or directory exists.
     *
     * @param  string  $path
     * @return bool
     */
    public function exists($path)
    {
        return file_exists($path);
    }

    /**
     * Determine if a file or directory is missing.
     *
     * @param  string  $path
     * @return bool
     */
    public function missing($path)
    {
        return ! $this->exists($path);
    }

    /**
     * @param string $path
     * @param mixed $data
     * @param bool|false $lock
     * 
     * @return mixed $data
     */
    public function put($path, $data, $lock = false)
    {
        return file_put_contents($path, $data, $lock ? LOCK_EX : 0);
    }

    /**
     * Determine if the given path is a file.
     *
     * @param  string  $file
     * @return bool
     */
    public function isFile($file)
    {
        return is_file($file);
    }

    /**
     * Determine if the given path is readable.
     *
     * @param  string  $path
     * @return bool
     */
    public function isReadable($path)
    {
        return is_readable($path);
    }

    /**
     * Determine if the given path is writable.
     *
     * @param  string  $path
     * @return bool
     */
    public function isWritable($path)
    {
        return is_writable($path);
    }

    /**
     * @param string $path
     * @param bool|false $lock
     *
     * @return string
     */
    public function get($path, $lock = false)
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }

        throw new \Exception("File does not exist at path {$path}.");
    }

    /**
     * Require the given file once.
     *
     * @param  string  $path
     * @param  array  $data
     * @return mixed
     */
    public function getRequire($path, $data = array())
    {
        if ($this->isFile($path)) {
            $requireClosure = function () use ($path, $data) {
                extract($data, EXTR_SKIP);

                return require $path;
            };

            return $requireClosure();
        }

        throw new \Exception("File does not exist at path {$path}.");
    }

    /**
     * Require the given file once.
     *
     * @param  string  $path
     * @param  array  $data
     * @return static
     */
    public function requireOnce($path, $data = array())
    {
        $requireOnceClosure = function () use ($path, $data) {
            extract($data, EXTR_SKIP);

            require_once $path;
        };

        $requireOnceClosure();

        return $this;
    }

    /**
     * Get the hash of the file at the given path.
     *
     * @param  string  $path
     * @param  string  $algorithm
     * @return string
     */
    public function hash($path, $algorithm = 'md5')
    {
        return hash_file($algorithm, $path);
    }

    /**
     * Get the file size of a given file.
     *
     * @param  string  $path
     * @return int
     */
    public function size($path)
    {
        return filesize($path);
    }

    /**
     * Get the file's last modification time.
     *
     * @param  string  $path
     * @return int
     */
    public function lastModified($path)
    {
        return filemtime($path);
    }

    /**
     * Get contents of a file with shared access.
     *
     * @param  string  $path
     * @return string
     */
    public function sharedGet($path)
    {
        $contents = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $contents = fread($handle, $this->size($path) ?: 1);

                    flock($handle, LOCK_UN);
                }
            } catch (\Exception $e) {
            }

            fclose($handle);
        }

        return $contents;
    }

    /**
     * @param  string|array  $paths
     * 
     * @return bool
     */
    public function delete($paths)
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                if (@unlink($path)) {
                    clearstatcache(false, $path);
                } else {
                    $success = false;
                }
            } catch (\Exception $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Write the contents of a file, replacing it atomically if it already exists.
     *
     * @param  string  $path
     * @param  string  $content
     * @return void
     */
    public function replace($path, $content)
    {
        // If the path already exists and is a symlink, get the real path...
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        chmod($tempPath, 0777 - umask());

        file_put_contents($tempPath, $content);

        rename($tempPath, $path);
    }

    /**
     * Replace a given string within a given file.
     *
     * @param  array|string  $search
     * @param  array|string  $replace
     * @param  string  $path
     * @return void
     */
    public function replaceInFile($search, $replace, $path)
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

    /**
     * Prepend to a file.
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public function prepend($path, $data)
    {
        if ($this->exists($path)) {
            return $this->put($path, $data . $this->get($path));
        }

        return $this->put($path, $data);
    }

    /**
     * Append to a file.
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public function append($path, $data)
    {
        return file_put_contents($path, $data, FILE_APPEND);
    }

    /**
     * Move a file to a new location.
     *
     * @param  string  $path
     * @param  string  $target
     * @return bool
     */
    public function move($path, $target)
    {
        return rename($path, $target);
    }

    /**
     * Copy a file to a new location.
     *
     * @param  string  $path
     * @param  string  $target
     * @return bool
     */
    public function copy($path, $target)
    {
        return copy($path, $target);
    }

    /**
     * Get or set UNIX mode of a file or directory.
     *
     * @param  string  $path
     * @param  int|null  $mode
     * @return mixed
     */
    public function chmod($path, $mode = null)
    {
        if ($mode) {
            return chmod($path, $mode);
        }

        return substr(sprintf('%o', fileperms($path)), -4);
    }

    /**
     * Extract the file name from a file path, without its extension.
     *
     * @param  string  $path
     * @return string
     */
    public function name($path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Extract the parent directory from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public function dirname($path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Extract the file extension from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public function extension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the file type of a given file.
     *
     * @param  string  $path
     * @return string
     */
    public function type($path)
    {
        return filetype($path);
    }

    /**
     * Get the mime-type of a given file.
     *
     * @param  string  $path
     * @return string|false
     */
    public function mimeType($path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Determine if the given path is a directory.
     *
     * @param  string  $directory
     * @return bool
     */
    public function isDirectory($directory)
    {
        return is_dir($directory);
    }

    /**
     * Ensure a directory exists.
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @return void
     */
    public function ensureDirectoryExists($path, $mode = 0755, $recursive = true)
    {
        if (! $this->isDirectory($path)) {
            $this->makeDirectory($path, $mode, $recursive);
        }
    }

    /**
     * Create a directory.
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @param  bool  $force
     * @return bool
     */
    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Move a directory.
     *
     * @param  string  $from
     * @param  string  $to
     * @param  bool  $overwrite
     * @return bool
     */
    public function moveDirectory($from, $to, $overwrite = false)
    {
        if ($overwrite && $this->isDirectory($to) && ! $this->deleteDirectory($to)) {
            return false;
        }

        return @rename($from, $to) === true;
    }

    /**
     * Copy a directory from one location to another.
     *
     * @param  string  $directory
     * @param  string  $destination
     * @param  int|null  $options
     * @return bool
     */
    public function copyDirectory($directory, $destination, $options = null)
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $options = $options ?: \FilesystemIterator::SKIP_DOTS;

        // If the destination directory does not actually exist, we will go ahead and
        // create it recursively, which just gets the destination prepared to copy
        // the files over. Once we make the directory we'll proceed the copying.
        $this->ensureDirectoryExists($destination, 0777);

        $items = new \FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            // As we spin through items, we will check to see if the current file is actually
            // a directory or a file. When it is actually a directory we will need to call
            // back into this function recursively to keep copying these nested folders.
            $target = $destination . '/' . $item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();

                if (! $this->copyDirectory($path, $target, $options)) {
                    return false;
                }
            }

            // If the current items is just a regular file, we will just copy this to the new
            // location and keep looping. If for some reason the copy fails we'll bail out
            // and return false, so the developer is aware that the copy process failed.
            else {
                if (! $this->copy($item->getPathname(), $target)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     *
     * @param  string  $directory
     * @param  bool  $preserve
     * @return bool
     */
    public function deleteDirectory($directory, $preserve = false)
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }

        $items = new \FilesystemIterator($directory);

        foreach ($items as $item) {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-directory otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir() && ! $item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            }

            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all of the files in this directory
            // and calling directories recursively, so we delete the real path.
            else {
                $this->delete($item->getPathname());
            }
        }

        if (! $preserve) {
            @rmdir($directory);
        }

        return true;
    }

    /**
     * Get all of the directories within a given directory.
     *
     * @param  string  $directory
     * @return array
     */
    public function directories($directory)
    {
        $directories = array();

        // Deliberately not built on in()/$dirs (like files()/allFiles()):
        // in() accumulates into the shared $dirs property across calls, so
        // on a shared/singleton Filesystem instance (see make()) an earlier
        // unrelated directories() call from one consumer would still be
        // sitting in $dirs and leak into a later, different consumer's
        // results.
        foreach ($this->searchInDirectory($directory) as $dir) {
            if (!$dir->isDir()) {
                continue;
            }

            $directories[] = $dir->getPathname();
        }

        return $directories;
    }

    /**
     * Find path names matching a given pattern.
     *
     * @param  string  $pattern
     * @param  int  $flags
     * @return array
     */
    public function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }

    /**
     * Get an array of all files in a directory (not recursive).
     *
     * @param  string  $directory
     * @param  bool  $hidden
     * @return array
     */
    public function files($directory, $hidden = false)
    {
        $files = array();

        foreach ($this->searchInDirectory($directory) as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (!$hidden && Strings::startsWith($file->getFilename(), '.')) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    /**
     * Get all of the files from the given directory (recursive).
     *
     * @param  string  $directory
     * @param  bool  $hidden
     * @return array
     */
    public function allFiles($directory, $hidden = false)
    {
        $files = array();

        $iterator = new \RecursiveIteratorIterator($this->searchInDirectory($directory));

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (!$hidden && Strings::startsWith($file->getFilename(), '.')) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    /**
     * Get the files and directories within a given directory, recursively,
     * preserving the directory structure (unlike allFiles(), which flattens
     * the whole tree into a single list of file paths).
     *
     * @param  string  $directory
     * @param  bool  $hidden
     * @return array
     */
    public function tree($directory, $hidden = false)
    {
        $result = array();

        foreach ($this->searchInDirectory($directory) as $item) {
            if (!$hidden && Strings::startsWith($item->getFilename(), '.')) {
                continue;
            }

            if ($item->isDir()) {
                $result[$item->getBasename()] = $this->tree($item->getPathname(), $hidden);
            } else {
                $result[$item->getBasename()] = $item->getPathname();
            }
        }

        return $result;
    }

    /**
     * Remove all of the directories within a given directory.
     *
     * @param  string  $directory
     * @return bool
     */
    public function deleteDirectories($directory)
    {
        $allDirectories = $this->directories($directory);

        if (! empty($allDirectories)) {
            foreach ($allDirectories as $directoryName) {
                $this->deleteDirectory($directoryName);
            }

            return true;
        }

        return false;
    }

    /**
     * Empty the specified directory of all files and folders.
     *
     * @param  string  $directory
     * @return bool
     */
    public function cleanDirectory($directory)
    {
        return $this->deleteDirectory($directory, true);
    }

    /**
     * Searches files and directories which match defined rules.
     *
     * @param string|string[] $dirs A directory path or an array of directories
     *
     * @return $this
     *
     * @throws \Exception if one of the directories does not exist
     */
    public function in($dirs)
    {
        $resolvedDirs = array();

        foreach ((array) $dirs as $dir) {
            if (is_dir($dir)) {
                $resolvedDirs[] = array($this->normalizeDir($dir));
            } elseif ($glob = glob($dir, (\defined('GLOB_BRACE') ? \GLOB_BRACE : 0) | \GLOB_ONLYDIR | \GLOB_NOSORT)) {
                sort($glob);
                $resolvedDirs[] = array_map(array($this, 'normalizeDir'), $glob);
            } else {
                throw new \Exception(sprintf('The "%s" directory does not exist.', $dir));
            }
        }

        $this->dirs = Arrays::merge($this->dirs, Arrays::collapse($resolvedDirs));

        return $this;
    }

    /**
     * Normalizes given directory names by removing trailing slashes.
     *
     * Excluding: (s)ftp:// or ssh2.(s)ftp:// 
     * 
     * @param string $dir
     * 
     * @return string
     */
    public function normalizeDir($dir)
    {
        if ('/' === $dir) {
            return $dir;
        }

        $dir = rtrim($dir, '/' . \DIRECTORY_SEPARATOR);

        if (preg_match('#^(ssh2\.)?s?ftp://#', $dir)) {
            $dir .= '/';
        }

        return $dir;
    }

    /**
     * @param string $dir
     * 
     * @return \RecursiveDirectoryIterator
     */
    public function searchInDirectory($dir)
    {
        $flags = \RecursiveDirectoryIterator::SKIP_DOTS;

        if ($this->followLinks) {
            $flags |= \RecursiveDirectoryIterator::FOLLOW_SYMLINKS;
        }

        return new \RecursiveDirectoryIterator($dir, $flags);
    }

    /**
     * Counts all the results collected by the iterators.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return iterator_count($this->getIterator());
    }

    /**
     * Returns an Iterator for the current Finder configuration.
     *
     * This method implements the IteratorAggregate interface.
     *
     * @return \Iterator<string, SplFileInfo>
     *
     * @throws \LogicException if the in() method has not been called
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        $iterator = new \AppendIterator();

        foreach ($this->dirs as $dir) {
            $iterator->append(new \IteratorIterator($this->searchInDirectory($dir)));
        }

        return $iterator;
    }
}
