<?php
declare(strict_types=1);

namespace StackoverflowSlackConnectorTests;

use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    protected array $usedEnvVars = [];

    protected string $testDirectory = '';

    protected function setEnvVar($name, $value): void
    {
        putenv("$name=$value");
        $this->usedEnvVars[$name] = $name;
    }

    protected function clearEnvVars(): void
    {
        foreach ($this->usedEnvVars as $envVar) {
            putenv($envVar);
        }
        $this->usedEnvVars = [];
    }

    protected function createTestDirectory(): void
    {
        if (!is_dir($this->getTestDirectory())) {
            mkdir($this->getTestDirectory());
        }
    }

    protected function getTestDirectory(): string
    {
        if ($this->testDirectory === '') {
            $inheritingClassFQCN = get_class($this);
            $inheritingClassName = substr($inheritingClassFQCN, strrpos($inheritingClassFQCN, '\\') + 1);

            try {
                $inheritingClassDirectory = dirname((new \ReflectionClass($inheritingClassFQCN))->getFileName());
            } catch (\ReflectionException $exception) {
                $inheritingClassDirectory = __DIR__;
            }

            $this->testDirectory = $inheritingClassDirectory.'/'.$inheritingClassName;
        }

        return $this->testDirectory;
    }

    protected function removeTestDirectory(): void
    {
        if (is_dir($this->getTestDirectory())) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->getTestDirectory(), \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $path) {
                if ($path->isDir() && !$path->isLink()) {
                    rmdir($path->getPathname());
                } else {
                    unlink($path->getPathname());
                }
            }
            rmdir($this->getTestDirectory());
        }
    }

    protected function createDirectoryInTestDirectory(string $path): void
    {
        mkdir($this->getPathInTestDirectory($path), 0777, true);
    }

    protected function createFileInTestDirectory(string $path, string $content): void
    {
        file_put_contents($this->getPathInTestDirectory($path), $content);
    }

    protected function getPathInTestDirectory(string $path): string
    {
        return $this->getTestDirectory() . '/' . $path;
    }

    protected function getPathInTestDirectoryAsUrl(string $path): string
    {
        return 'file://' . $this->getPathInTestDirectory($path);
    }
}
