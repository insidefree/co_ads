<?php

namespace Wix\APIBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('wix_api');

        $rootNode
            ->children()
                ->arrayNode('classes')
                    ->children()
                        ->scalarNode('service')
                            ->isRequired()
                        ->end()
                        ->scalarNode('instance')
                            ->isRequired()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('keys')
                    ->children()
                        ->scalarNode('app_id')
                            ->isRequired()
                        ->end()
                        ->scalarNode('app_secret')
                            ->isRequired()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
