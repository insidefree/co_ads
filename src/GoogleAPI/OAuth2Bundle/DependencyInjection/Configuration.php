<?php

namespace GoogleAPI\OAuth2Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    protected $scopes = array(
        'https://www.googleapis.com/auth/drive.file',
        'https://www.googleapis.com/auth/drive',
        'https://www.googleapis.com/auth/drive.apps.readonly',
        'https://www.googleapis.com/auth/drive.readonly',
        'https://www.googleapis.com/auth/drive.readonly.metadata',
        'https://www.googleapis.com/auth/drive.install'
    );

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('google_apio_auth2');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        $rootNode
            ->fixXmlConfig('scope')
            ->children()
                ->arrayNode('scopes')
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')
                        ->validate()
                            ->ifNotInArray($this->scopes)
                            ->thenInvalid('%s is not a valid scope.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('keys')
                    ->children()
                        ->scalarNode('client_id')
                            ->isRequired()
                        ->end()
                        ->scalarNode('client_secret')
                            ->isRequired()
                        ->end()
                        ->scalarNode('redirect_uri')
                            ->isRequired()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('preferences')
                    ->children()
                        ->booleanNode('use_objects')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
