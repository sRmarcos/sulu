<?php

namespace DTL\Component\Content\Compat\Structure;

use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\StructureExtension\StructureExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use DTL\Component\Content\Structure\Factory\StructureFactoryInterface;
use DTL\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Structure as LegacyStructure;
use DTL\Component\Content\Structure\Structure;
use DTL\Component\Content\Routing\PageUrlGenerator;

class StructureManager implements StructureManagerInterface
{
    /**
     * @var StructureFactoryInterface
     */
    private $structureFactory;

    /**
     * @var PageUrlGenerator
     */
    private $urlGenerator;

    /**
     * @param StructureFactoryInterface $structureFactory
     * @param PageUrlGenerator
     */
    public function __construct(
        StructureFactoryInterface $structureFactory,
        PageUrlGenerator $urlGenerator
    )
    {
        $this->structureFactory = $structureFactory;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Returns structure for given key and type
     * @param string $key
     * @param string $type
     * @return StructureInterface
     */
    public function getStructure($key, $type = 'page')
    {
        $structure = $this->structureFactory->getStructure($type, $key);
        $compatStructure = new StructureBridge($structure, $this->structureFactory, $this->urlGenerator);

        return $compatStructure;
    }

    /**
     * Return all the structures of the given type
     * @param string $type
     * @return StructureInterface[]
     */
    public function getStructures($type = LegacyStructure::TYPE_PAGE)
    {
        $compatStructures = array();
        foreach ($this->structureFactory->getStructures($type) as $structure) {
            $compatStructures[] = new StructureBridge($structure, $this->structureFactory);
        }

        return $compatStructures;
    }

    /**
     * add dynamically an extension to structures
     * @param StructureExtensionInterface $extension
     * @param string $template default is all templates
     */
    public function addExtension(StructureExtensionInterface $extension, $template = 'all')
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * Returns extensions for structure
     * @param string $key
     * @return StructureExtensionInterface[]
     */
    public function getExtensions($key)
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * Indicates that the structure has a extension
     * @param string $key
     * @param string $name
     * @return boolean
     */
    public function hasExtension($key, $name)
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * Returns a extension
     * @param string $key
     * @param string $name
     * @return StructureExtensionInterface
     */
    public function getExtension($key, $name)
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * This is not required.
     */
    public function setContainer(ContainerInterface $container = null)
    {
    }

}
