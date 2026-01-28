<?php

declare(strict_types=1);

namespace JBSNewMedia\DDMBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ddm');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
