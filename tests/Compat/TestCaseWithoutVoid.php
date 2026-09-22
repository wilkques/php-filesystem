<?php

namespace Wilkques\Filesystem\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Wilkques\Container\Container;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var string
     */
    protected $tmpDir;

    protected function setUp()
    {
        parent::setUp();

        $this->resetContainerSingleton();

        $this->tmpDir = sys_get_temp_dir() . '/wilkques-filesystem-tests-' . uniqid();

        mkdir($this->tmpDir, 0777, true);

        $this->additionalSetUp();
    }

    protected function tearDown()
    {
        $this->additionalTearDown();

        $this->removeDirectory($this->tmpDir);

        $this->resetContainerSingleton();

        parent::tearDown();
    }

    /**
     * Hook for subclasses that need extra per-test setup. Deliberately NOT
     * named setUp(): PHPUnit enforces return-type covariance on setUp()/
     * tearDown() overrides, and that return type is compile-time syntax
     * that differs across the PHPUnit versions this suite runs under (see
     * tests/bootstrap.php) — a plain, un-typed hook method has no such
     * constraint. See wilkques/console's tests/TestCase.php.
     */
    protected function additionalSetUp()
    {
    }

    /**
     * @see additionalSetUp()
     */
    protected function additionalTearDown()
    {
    }

    /**
     * Reset the Container's static singleton between tests so that a test
     * exercising Filesystem::make()/filesystem() (which resolves through
     * Container::getInstance()) can never leak state into another test.
     */
    protected function resetContainerSingleton()
    {
        if (method_exists('Wilkques\\Container\\Container', 'setInstance')) {
            Container::setInstance(null);

            return;
        }

        $reflection = new \ReflectionClass('Wilkques\\Container\\Container');

        $property = $reflection->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    /**
     * Recursively delete a directory tree, independent of the Filesystem
     * class under test (so a broken deleteDirectory() can't also break
     * test cleanup).
     *
     * @param string $dir
     *
     * @return void
     */
    protected function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \FilesystemIterator($dir);

        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                $this->removeDirectory($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($dir);
    }

    /**
     * @param string $relative
     * @param string $contents
     *
     * @return string absolute path to the file just written
     */
    protected function putFile($relative, $contents = '')
    {
        $path = $this->tmpDir . '/' . $relative;

        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, $contents);

        return $path;
    }

    /**
     * Read a protected/private property off of an object (or a static
     * property off of a class) via reflection.
     *
     * @param object|string $objectOrClass
     * @param string        $property
     *
     * @return mixed
     */
    protected function peek($objectOrClass, $property)
    {
        $reflection = new \ReflectionClass($objectOrClass);

        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);

        if (is_object($objectOrClass)) {
            return $prop->getValue($objectOrClass);
        }

        return $prop->getValue();
    }

    /**
     * PHPUnit assertion/expectation method names that have been renamed
     * across the major versions this suite runs under (see
     * tests/bootstrap.php re: "phpunit/phpunit": "*").
     *
     * @param string $class
     *
     * @return void
     */
    protected function expectExceptionCompat($class)
    {
        if (method_exists($this, 'expectException')) {
            // PHPUnit >= 5.2
            $this->expectException($class);

            return;
        }

        // PHPUnit 4.x: no expectException() at all.
        $this->setExpectedException($class);
    }
}
