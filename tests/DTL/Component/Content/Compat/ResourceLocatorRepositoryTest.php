<?php

namespace DTL\Component\Content\Compat;

use DTL\Component\Content\Compat\DataNormalizer;
use Sulu\Component\Content\StructureInterface;
use Symfony\Cmf\Component\RoutingAuto\UriGenerator;
use DTL\Component\Content\Compat\ResourceLocatorRepository;
use Doctrine\ODM\PHPCR\DocumentManager;
use DTL\Component\Content\Structure\Factory\StructureFactoryInterface;

class ResourceLocatorRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->uriGenerator = $this->prophesize(UriGenerator::class);
        $this->documentManager = $this->prophesize(DocumentManager::class);
        $this->structureFactory = $this->prophesize(StructureFactoryInterface::class);
        $this->repository = new ResourceLocatorRepository(
            $this->uriGenerator->reveal(),
            $this->documentManager->reveal(),
            $this->structureFactory->reveal()
        );
    }

    public function testGenerate()
    {
    }
}
