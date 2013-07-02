<?php

namespace GoogleAPI\OAuth2Bundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class GoogleAPIOAuth2Extension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('google_api_oauth2.client_id', $config['keys']['client_id']);
        $container->setParameter('google_api_oauth2.client_secret', $config['keys']['client_secret']);
        $container->setParameter('google_api_oauth2.refresh_token', $config['keys']['refresh_token']);
        $container->setParameter('google_api_oauth2.redirect_uri', $config['urls']['redirect_uri']);
        $container->setParameter('google_api_oauth2.scopes', $config['scopes']);
        $container->setParameter('google_api_oauth2.use_objects', $config['preferences']['use_objects']);
    }
}
