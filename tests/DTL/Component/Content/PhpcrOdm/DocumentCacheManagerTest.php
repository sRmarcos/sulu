<?php

namespace DTL\Component\Content\PhpcrOdm;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\DocumentManager;
use DTL\Component\Content\PhpcrOdm\NamespaceRoleRegistry;

class DocumentCacheManagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManager::class);
        $this->namespaceRegistry = $this->prophesize(NamespaceRoleRegistry::class);
        $this->classMetadata = $this->prophesize(ClassMetadata::class);
        $this->testObject = new \stdClass();

        $this->manager = new DocumentCacheManager(
            $this->documentManager->reveal(),
            $this->namespaceRegistry->reveal()
        );

        $this->namespaceRegistry->getAlias('cache')->willReturn('cach');
        $this->classMetadata->getName()->willReturn('stdClass');
    }

    public function testSetCache()
    {
        $this->documentManager->getClassMetadata('stdClass')->willReturn($this->classMetadata);
        $this->classMetadata->getFieldMapping('hello')->willReturn(array(
            'property' => 'cach:hello',
        ));
        $this->classMetadata->setFieldValue($this->testObject, 'hello', 'goodbye')->shouldBeCalled();
        $this->manager->setCache($this->testObject, 'hello', 'goodbye');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Property "hello" in class "stdClass" does not seem to be a cached property
     */
    public function testSetCacheNotExist()
    {
        $this->documentManager->getClassMetadata('stdClass')->willReturn($this->classMetadata);
        $this->classMetadata->getFieldMapping('hello')->willReturn(array(
            'property' => 'hello',
        ));
        $this->manager->setCache($this->testObject, 'hello', 'goodbye');
    }

    public function testGetCache()
    {
        $this->documentManager->getClassMetadata('stdClass')->willReturn($this->classMetadata);
        $this->classMetadata->getFieldMapping('hello')->willReturn(array(
            'property' => 'cach:hello',
        ));
        $this->classMetadata->getFieldValue($this->testObject, 'hello')->willReturn('goodbye');
        $result = $this->manager->getCache($this->testObject, 'hello');
        $this->assertEquals('goodbye', $result);
    }
}
