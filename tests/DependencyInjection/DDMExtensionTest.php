<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\DependencyInjection;

use JBSNewMedia\DDMBundle\DependencyInjection\DDMExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DDMExtensionTest extends TestCase
{
    private ContainerBuilder $container;
    private DDMExtension $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extension = new DDMExtension();
    }

    public function testPrepend(): void
    {
        // Mock twig extension
        $this->container->registerExtension(new class extends \Symfony\Component\DependencyInjection\Extension\Extension {
            public function load(array $configs, ContainerBuilder $container): void {}
            public function getAlias(): string { return 'twig'; }
        });

        $this->extension->prepend($this->container);

        $config = $this->container->getExtensionConfig('twig');
        $this->assertNotEmpty($config);
        $this->assertArrayHasKey('paths', $config[0]);
    }

    public function testLoad(): void
    {
        $this->extension->load([], $this->container);
        $this->assertTrue($this->container->has('JBSNewMedia\DDMBundle\Service\DDMFactory'));
    }
}
