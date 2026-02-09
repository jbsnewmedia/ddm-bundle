<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests;

use JBSNewMedia\DDMBundle\DDMBundle;
use JBSNewMedia\DDMBundle\DependencyInjection\DDMExtension;
use PHPUnit\Framework\TestCase;

final class DDMBundleTest extends TestCase
{
    public function testGetContainerExtension(): void
    {
        $bundle = new DDMBundle();
        $extension = $bundle->getContainerExtension();
        $this->assertInstanceOf(DDMExtension::class, $extension);
        $this->assertSame($extension, $bundle->getContainerExtension());
    }

    public function testGetContainerExtensionReturnsFalse(): void
    {
        $bundle = new class extends DDMBundle {
            public function setExtensionFalse(): void
            {
                $this->extension = false;
            }
        };

        $bundle->setExtensionFalse();
        $this->assertNull($bundle->getContainerExtension());
    }

    public function testGetPath(): void
    {
        $bundle = new DDMBundle();
        $this->assertSame(\dirname(__DIR__), $bundle->getPath());
    }
}
