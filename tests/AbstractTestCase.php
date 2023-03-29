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
            $files = glob($this->getTestDirectory() . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->getTestDirectory());
        }
    }

    protected function addFileToTestDirectory(string $filename, string $content): void
    {
        file_put_contents($this->getPathOfTestDirectoryFile($filename), $content);
    }

    protected function getPathOfTestDirectoryFile(string $filename): string
    {
        return $this->getTestDirectory() . '/' . $filename;
    }
}
