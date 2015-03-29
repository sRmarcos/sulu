<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Bundle\ContentBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class DtlContentExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('cmf_routing')) {
            $container->prependExtensionConfig('cmf_routing', array(
                'dynamic' => array(
                    'url_generator' => 'dtl_content.routing.page_url_generator',
                ),
            ));
        }

        if ($container->hasExtension('jms_serializer')) {
            $container->prependExtensionConfig('jms_serializer', array(
                'metadata' => array(
                    'directories' => array(
                        array(
                            'path' => __DIR__ . '/../Resources/config/serializer',
                            'namespace_prefix' => 'DTL\Bundle\ContentBundle',
                        ),
                        array(
                            'path' => __DIR__ . '/../Resources/config/serializer',
                            'namespace_prefix' => 'DTL\Component\Content',
                        ),
                    ),
                ),
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('phpcr_odm.xml');
        $loader->load('form.xml');
        $loader->load('form_property_types.xml');
        $loader->load('routing.xml');
        $loader->load('structure.xml');
        $loader->load('controller.xml');
        $loader->load('property.xml');
        $loader->load('jms_serializer.xml');

        $compat = $config['compat']['enabled'];
        $loader->load('compat.xml');
        $container->setParameter('dtl_content.compat', $compat);

        $this->processStructure($config['structure'], $container);
        $this->processPhpcrOdm($config['phpcr_odm'], $container);
        $this->processRouting($config['routing'], $container);
    }

    private function processStructure($config, ContainerBuilder $container)
    {
        $this->processPaths($config['paths'], $container);
    }

    private function processRouting($config, ContainerBuilder $container)
    {
        if (false === $config['resource_locator_cache']) {
            $container->removeDefinition('dtl_content.routing.event_subscriber.document_cache');
            $container->removeDefinition('dtl_content.phpcr_odm.event_subscriber.document_cache');
        }
    }

    private function processPaths($config, ContainerBuilder $container)
    {
        $typePaths = array();

        foreach ($config as $path) {
            if (!isset($typePaths[$path['type']])) {
                $typePaths[$path['type']] = array();
            }

            $typePaths[$path['type']][$path['path']] = $path['path'];
        }

        $container->setParameter('dtl_content.structure.paths', $typePaths);
    }

    private function processPhpcrOdm($config, ContainerBuilder $container)
    {
        $namespaceRegistry = $container->getDefinition('dtl_content.phpcr_odm.namespace_registry');
        $namespaceRegistry->replaceArgument(0, $config['namespaces']);
    }
}

