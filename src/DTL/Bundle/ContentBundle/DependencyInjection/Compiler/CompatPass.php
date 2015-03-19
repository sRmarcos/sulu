<?php

namespace DTL\Bundle\ContentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Replaces legacy services with compatibility layers
 */
class CompatPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->getParameter('dtl_content.compat')) {
            return;
        }

        $this->replaceStructureManager($container);
        $this->replaceContentMapper($container);
        $this->replaceNodeRepository($container);
    }

    public function replaceStructureManager(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sulu.content.structure_manager')) {
            return;
        }

        $container->removeDefinition('sulu.content.structure_manager');
        $container->setAlias('sulu.content.structure_manager', 'dtl_content.compat.structure.structure_manager');
    }

    public function replaceContentMapper(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sulu.content.mapper')) {
            return;
        }

        $container->removeDefinition('sulu.content.mapper');
        $container->setAlias('sulu.content.mapper', 'dtl_content.compat.content_mapper');
    }

    public function replaceNodeRepository(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sulu_content.node_repository')) {
            return;
        }

        $definition = $container->getDefinition('sulu_content.node_repository');
        $container->setDefinition('sulu_content.node_repository.original', $definition);
        $container->removeDefinition('sulu_content.node_repository');
        $nodeRepository = $container->register('dtl_content.compat.node_repository', 'DTL\Component\Content\Compat\NodeRepository');
        $nodeRepository->addArgument(new Reference('sulu_content.node_repository.original'));
        $nodeRepository->addArgument(new Reference('dtl_content.compat.content_mapper'));
        $container->setAlias('sulu_content.node_repository', 'dtl_content.compat.node_repository');
    }
}
