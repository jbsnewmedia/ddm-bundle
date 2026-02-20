<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\DependencyInjection;

use JBSNewMedia\DDMBundle\DependencyInjection\Configuration;
use JBSNewMedia\DDMBundle\DependencyInjection\DDMExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Processor;

final class DependencyInjectionTest extends TestCase
{
    public function testConfiguration(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, []);

        $this->assertIsArray($config);
    }

    public function testExtensionPrepend(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new class extends \Symfony\Component\DependencyInjection\Extension\Extension {
            public function load(array $configs, ContainerBuilder $container): void {}
            public function getAlias(): string { return 'twig'; }
        });
        $container->registerExtension(new class extends \Symfony\Component\DependencyInjection\Extension\Extension {
            public function load(array $configs, ContainerBuilder $container): void {}
            public function getAlias(): string { return 'doctrine'; }
        });

        $extension = new DDMExtension();
        $extension->prepend($container);

        $twigConfigs = $container->getExtensionConfig('twig');
        $this->assertCount(1, $twigConfigs);
        $this->assertArrayHasKey('paths', $twigConfigs[0]);

        $doctrineConfigs = $container->getExtensionConfig('doctrine');
        $this->assertCount(1, $doctrineConfigs);
        $this->assertArrayHasKey('orm', $doctrineConfigs[0]);
        $this->assertSame(\JBSNewMedia\DDMBundle\Doctrine\ORM\Query\AST\Functions\Cast::class, $doctrineConfigs[0]['orm']['dql']['string_functions']['CAST']);
    }

    public function testExtensionLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new DDMExtension();

        $extension->load([], $container);

        $this->assertTrue($container->has('JBSNewMedia\DDMBundle\Service\DDMFactory'));
    }
}
