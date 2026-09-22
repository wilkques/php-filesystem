<?php

namespace Wilkques\Filesystem\Tests;

use Wilkques\Filesystem\Filesystem;

class FilesystemTest extends TestCase
{
    /**
     * @return Filesystem
     */
    protected function fs($followLinks = false)
    {
        return new Filesystem($followLinks);
    }

    public function testExistsAndMissing()
    {
        $path = $this->putFile('a.txt', 'hello');

        $fs = $this->fs();

        $this->assertTrue($fs->exists($path));
        $this->assertFalse($fs->missing($path));

        $this->assertFalse($fs->exists($this->tmpDir . '/nope.txt'));
        $this->assertTrue($fs->missing($this->tmpDir . '/nope.txt'));
    }

    public function testPutAndGet()
    {
        $path = $this->tmpDir . '/put.txt';

        $fs = $this->fs();

        $fs->put($path, 'hello world');

        $this->assertSame('hello world', $fs->get($path));
    }

    public function testGetThrowsWhenFileDoesNotExist()
    {
        $fs = $this->fs();

        $this->expectExceptionCompat('Exception');

        $fs->get($this->tmpDir . '/nope.txt');
    }

    public function testIsFileAndIsDirectory()
    {
        $path = $this->putFile('a.txt');

        $fs = $this->fs();

        $this->assertTrue($fs->isFile($path));
        $this->assertFalse($fs->isFile($this->tmpDir));

        $this->assertTrue($fs->isDirectory($this->tmpDir));
        $this->assertFalse($fs->isDirectory($path));
    }

    public function testIsReadableAndIsWritable()
    {
        $path = $this->putFile('a.txt');

        $fs = $this->fs();

        $this->assertTrue($fs->isReadable($path));
        $this->assertTrue($fs->isWritable($path));
    }

    public function testSize()
    {
        $path = $this->putFile('a.txt', 'hello');

        $fs = $this->fs();

        $this->assertSame(5, $fs->size($path));
    }

    public function testSharedGet()
    {
        $path = $this->putFile('a.txt', 'hello');

        $fs = $this->fs();

        $this->assertSame('hello', $fs->sharedGet($path));
        $this->assertSame('hello', $fs->get($path, true));
    }

    /**
     * Regression test: sharedGet() used to call fclose($handle) outside the
     * `if ($handle)` guard, so a failed fopen() (missing file) passed
     * `false` to fclose() — a warning on PHP < 8.0, a fatal TypeError on
     * PHP 8.0+.
     */
    public function testSharedGetOnMissingFileReturnsEmptyStringInsteadOfThrowing()
    {
        $fs = $this->fs();

        $result = @$fs->sharedGet($this->tmpDir . '/does-not-exist.txt');

        $this->assertSame('', $result);
    }

    public function testDelete()
    {
        $path = $this->putFile('a.txt');

        $fs = $this->fs();

        $this->assertTrue($fs->delete($path));
        $this->assertFalse($fs->exists($path));
    }

    public function testDeleteMultiplePaths()
    {
        $a = $this->putFile('a.txt');
        $b = $this->putFile('b.txt');

        $fs = $this->fs();

        $this->assertTrue($fs->delete(array($a, $b)));
        $this->assertFalse($fs->exists($a));
        $this->assertFalse($fs->exists($b));
    }

    public function testReplace()
    {
        $path = $this->putFile('a.txt', 'old');

        $fs = $this->fs();

        $fs->replace($path, 'new');

        $this->assertSame('new', $fs->get($path));
    }

    public function testReplaceInFile()
    {
        $path = $this->putFile('a.txt', 'hello world');

        $fs = $this->fs();

        $fs->replaceInFile('world', 'there', $path);

        $this->assertSame('hello there', $fs->get($path));
    }

    public function testPrependCreatesFileIfMissing()
    {
        $path = $this->tmpDir . '/a.txt';

        $fs = $this->fs();

        $fs->prepend($path, 'first');

        $this->assertSame('first', $fs->get($path));
    }

    public function testPrependExistingFile()
    {
        $path = $this->putFile('a.txt', 'second');

        $fs = $this->fs();

        $fs->prepend($path, 'first-');

        $this->assertSame('first-second', $fs->get($path));
    }

    public function testAppend()
    {
        $path = $this->putFile('a.txt', 'first');

        $fs = $this->fs();

        $fs->append($path, '-second');

        $this->assertSame('first-second', $fs->get($path));
    }

    public function testMove()
    {
        $from = $this->putFile('a.txt', 'hello');
        $to = $this->tmpDir . '/b.txt';

        $fs = $this->fs();

        $this->assertTrue($fs->move($from, $to));
        $this->assertFalse($fs->exists($from));
        $this->assertSame('hello', $fs->get($to));
    }

    public function testCopy()
    {
        $from = $this->putFile('a.txt', 'hello');
        $to = $this->tmpDir . '/b.txt';

        $fs = $this->fs();

        $this->assertTrue($fs->copy($from, $to));
        $this->assertTrue($fs->exists($from));
        $this->assertSame('hello', $fs->get($to));
    }

    public function testChmod()
    {
        $path = $this->putFile('a.txt');

        $fs = $this->fs();

        $fs->chmod($path, 0644);

        $this->assertSame('0644', $fs->chmod($path));
    }

    public function testEnsureDirectoryExistsAndMakeDirectory()
    {
        $dir = $this->tmpDir . '/nested/dir';

        $fs = $this->fs();

        $fs->ensureDirectoryExists($dir);

        $this->assertTrue($fs->isDirectory($dir));

        // Calling it again on an already-existing directory must not error.
        $fs->ensureDirectoryExists($dir);

        $this->assertTrue($fs->isDirectory($dir));
    }

    public function testMoveDirectory()
    {
        $this->putFile('src/a.txt', 'hello');

        $fs = $this->fs();

        $from = $this->tmpDir . '/src';
        $to = $this->tmpDir . '/dst';

        $this->assertTrue($fs->moveDirectory($from, $to));
        $this->assertFalse($fs->isDirectory($from));
        $this->assertSame('hello', $fs->get($to . '/a.txt'));
    }

    public function testCopyDirectory()
    {
        $this->putFile('src/a.txt', 'hello');
        $this->putFile('src/sub/b.txt', 'world');

        $fs = $this->fs();

        $from = $this->tmpDir . '/src';
        $to = $this->tmpDir . '/dst';

        $this->assertTrue($fs->copyDirectory($from, $to));
        $this->assertSame('hello', $fs->get($to . '/a.txt'));
        $this->assertSame('world', $fs->get($to . '/sub/b.txt'));
        // Source is untouched by a copy.
        $this->assertTrue($fs->isDirectory($from));
    }

    public function testDeleteDirectory()
    {
        $this->putFile('src/a.txt');
        $this->putFile('src/sub/b.txt');

        $fs = $this->fs();

        $dir = $this->tmpDir . '/src';

        $this->assertTrue($fs->deleteDirectory($dir));
        $this->assertFalse($fs->isDirectory($dir));
    }

    public function testDeleteDirectoryPreserve()
    {
        $this->putFile('src/a.txt');

        $fs = $this->fs();

        $dir = $this->tmpDir . '/src';

        $this->assertTrue($fs->deleteDirectory($dir, true));
        $this->assertTrue($fs->isDirectory($dir));
        $this->assertFalse($fs->exists($dir . '/a.txt'));
    }

    public function testDeleteDirectories()
    {
        $this->putFile('a/x.txt');
        $this->putFile('b/y.txt');

        $fs = $this->fs();

        $this->assertTrue($fs->deleteDirectories($this->tmpDir));
        $this->assertFalse($fs->isDirectory($this->tmpDir . '/a'));
        $this->assertFalse($fs->isDirectory($this->tmpDir . '/b'));
    }

    public function testCleanDirectory()
    {
        $this->putFile('a.txt');
        $this->putFile('sub/b.txt');

        $fs = $this->fs();

        $this->assertTrue($fs->cleanDirectory($this->tmpDir));
        $this->assertTrue($fs->isDirectory($this->tmpDir));
        $this->assertFalse($fs->exists($this->tmpDir . '/a.txt'));
    }

    /**
     * Regression test: directories() used to return files alongside
     * subdirectories (no isDir() filter), contradicting its name.
     */
    public function testDirectoriesOnlyReturnsDirectories()
    {
        $this->putFile('a.txt');
        $this->putFile('b.txt');
        $this->putFile('sub1/.keep');
        $this->putFile('sub2/.keep');

        $fs = $this->fs();

        $dirs = $fs->directories($this->tmpDir);

        sort($dirs);

        $this->assertSame(
            array($this->tmpDir . '/sub1', $this->tmpDir . '/sub2'),
            $dirs
        );
    }

    /**
     * Regression test: directories() used to be built on in()/$dirs, which
     * accumulates into a shared property across calls instead of resetting.
     * On a shared/singleton Filesystem instance (see testMakeReturnsThe
     * SameSharedInstance) — e.g. one Filesystem injected into both a
     * Console and a Config instance — an earlier directories() call for one
     * root would still be sitting in $dirs and leak into a later,
     * unrelated directories() call for a different root on the same
     * instance.
     */
    public function testDirectoriesDoesNotLeakStateAcrossCallsOnSharedInstance()
    {
        $this->putFile('rootA/sub1/.keep');
        $this->putFile('rootB/sub2/.keep');

        $shared = $this->fs();

        $rootA = $this->tmpDir . '/rootA';
        $rootB = $this->tmpDir . '/rootB';

        $dirsA = $shared->directories($rootA);
        $dirsB = $shared->directories($rootB);

        $this->assertSame(array($rootA . '/sub1'), $dirsA);
        $this->assertSame(array($rootB . '/sub2'), $dirsB);
    }

    public function testFilesNonRecursiveExcludesHiddenByDefault()
    {
        $this->putFile('a.txt');
        $this->putFile('.hidden');
        $this->putFile('sub/b.txt');

        $fs = $this->fs();

        $files = $fs->files($this->tmpDir);

        $this->assertSame(array($this->tmpDir . '/a.txt'), $files);
    }

    public function testFilesWithHiddenTrue()
    {
        $this->putFile('a.txt');
        $this->putFile('.hidden');

        $fs = $this->fs();

        $files = $fs->files($this->tmpDir, true);

        sort($files);

        $this->assertSame(
            array($this->tmpDir . '/.hidden', $this->tmpDir . '/a.txt'),
            $files
        );
    }

    public function testAllFilesRecursive()
    {
        $this->putFile('a.txt');
        $this->putFile('sub/b.txt');
        $this->putFile('sub/deep/c.txt');

        $fs = $this->fs();

        $files = $fs->allFiles($this->tmpDir);

        sort($files);

        $this->assertSame(
            array(
                $this->tmpDir . '/a.txt',
                $this->tmpDir . '/sub/b.txt',
                $this->tmpDir . '/sub/deep/c.txt',
            ),
            $files
        );
    }

    public function testGlob()
    {
        $this->putFile('a.txt');
        $this->putFile('b.txt');
        $this->putFile('c.json');

        $fs = $this->fs();

        $matches = $fs->glob($this->tmpDir . '/*.txt');

        sort($matches);

        $this->assertSame(
            array($this->tmpDir . '/a.txt', $this->tmpDir . '/b.txt'),
            $matches
        );
    }

    public function testNameBasenameDirnameExtension()
    {
        $path = $this->putFile('a.txt');

        $fs = $this->fs();

        $this->assertSame('a', $fs->name($path));
        $this->assertSame('a.txt', $fs->basename($path));
        $this->assertSame($this->tmpDir, $fs->dirname($path));
        $this->assertSame('txt', $fs->extension($path));
    }

    public function testType()
    {
        $path = $this->putFile('a.txt');

        $fs = $this->fs();

        $this->assertSame('file', $fs->type($path));
        $this->assertSame('dir', $fs->type($this->tmpDir));
    }

    public function testMimeType()
    {
        $path = $this->putFile('a.txt', 'hello');

        $fs = $this->fs();

        $mime = $fs->mimeType($path);

        $this->assertSame(0, strpos($mime, 'text/'));
    }

    public function testHash()
    {
        $path = $this->putFile('a.txt', 'hello');

        $fs = $this->fs();

        $this->assertSame(md5_file($path), $fs->hash($path));
        $this->assertSame(sha1_file($path), $fs->hash($path, 'sha1'));
    }

    public function testLastModified()
    {
        $path = $this->putFile('a.txt');

        $fs = $this->fs();

        $this->assertSame(filemtime($path), $fs->lastModified($path));
    }

    public function testGetRequireReturnsValueAndReceivesData()
    {
        $path = $this->putFile('req.php', '<?php return $x * 2;');

        $fs = $this->fs();

        $this->assertSame(10, $fs->getRequire($path, array('x' => 5)));
    }

    public function testGetRequireThrowsWhenFileDoesNotExist()
    {
        $fs = $this->fs();

        $this->expectExceptionCompat('Exception');

        $fs->getRequire($this->tmpDir . '/nope.php');
    }

    public function testRequireOnceRunsFile()
    {
        $marker = $this->tmpDir . '/ran.txt';

        $path = $this->putFile(
            'side-effect.php',
            '<?php file_put_contents(' . var_export($marker, true) . ', "ran");'
        );

        $fs = $this->fs();

        $result = $fs->requireOnce($path);

        $this->assertSame($fs, $result);
        $this->assertTrue(file_exists($marker));
    }

    public function testConstructorFollowLinksDefaultsFalse()
    {
        $fs = new Filesystem();

        $this->assertFalse($this->peek($fs, 'followLinks'));
    }

    public function testConstructorFollowLinksTrue()
    {
        $fs = new Filesystem(true);

        $this->assertTrue($this->peek($fs, 'followLinks'));
    }

    public function testFollowLinksFluentMethodStillWorks()
    {
        $fs = new Filesystem();

        $this->assertSame($fs, $fs->followLinks());

        $this->assertTrue($this->peek($fs, 'followLinks'));
    }

    /**
     * Regression test: make() used to resolve itself through the container
     * without ever registering as a singleton, so every call built a brand
     * new instance instead of sharing one.
     */
    public function testMakeReturnsTheSameSharedInstance()
    {
        $a = Filesystem::make();
        $b = Filesystem::make();

        $this->assertSame($a, $b);
    }

    public function testFilesystemHelperFunctionReturnsSameInstanceAsMake()
    {
        $this->assertSame(Filesystem::make(), filesystem());
    }
}
