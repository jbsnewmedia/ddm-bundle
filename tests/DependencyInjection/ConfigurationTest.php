<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\Tests\DependencyInjection;

use JBSNewMedia\DDMBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class ConfigurationTest extends TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration();
        $this->assertInstanceOf(TreeBuilder::class, $configuration->getConfigTreeBuilder());
    }
}
