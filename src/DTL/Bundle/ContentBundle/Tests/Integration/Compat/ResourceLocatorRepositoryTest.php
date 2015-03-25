<?php

namespace DTL\Bundle\ContentBundle\Tests\Integration\Compat;

use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\StructureInterface;
use DTL\Bundle\ContentBundle\Tests\Integration\BaseTestCase;
use DTL\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\Content\Structure;
use DTL\Component\Content\Document\PageInterface;
use PHPCR\Util\PathHelper;

class ResourceLocatorRepositoryTest extends BaseTestCase
{
    private $contentMapper;
    private $documentManager;
    private $documents;

    public function setUp()
    {
        $this->initPhpcr();
        $this->parent = $this->getDm()->find(null, '/cmf/sulu_io/contents');
        $this->repository = $this->getContainer()->get('dtl_content.compat.resource_locator_repository');
        $this->documentManager = $this->getContainer()->get('doctrine_phpcr.odm.document_manager');
        $this->loadFixtures();
    }

    public function provideGenerate()
    {
        return array(
            // it generates a URL when a parent UUID is given
            array(
                array(
                    'title' => 'Hello World',
                ),
                'document1',
                null,
                array(
                    'resourceLocator' => '/document1/hello-world',
                    '_links' => array(
                        'self' => '/admin/api/nodes/resourcelocators/generates',
                    ),
                ),
            ),
            // it generates a URL when a spefific document UUID is given
            array(
                array(
                    'title' => 'Hello World',
                ),
                'document1',
                'document2',
                array(
                    'resourceLocator' => '/document1/hello-world',
                    '_links' => array(
                        'self' => '/admin/api/nodes/resourcelocators/generates',
                    ),
                ),
            ),
            // it generates a URL when a spefific document UUID is given and no parent is given
            array(
                array(
                    'title' => 'Hello World',
                ),
                null,
                'document2',
                array(
                    'resourceLocator' => '/document1/hello-world',
                    '_links' => array(
                        'self' => '/admin/api/nodes/resourcelocators/generates',
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider provideGenerate
     */
    public function testGenerate($parts, $parentDocument, $document, $expectedResult)
    {
        if ($parentDocument) {
            $parentDocument = $this->documents[$parentDocument];
        }

        $parentUuid = $parentDocument ? $parentDocument->getUuid() : null;

        if ($document) {
            $document = $this->documents[$document];
        }

        $documentUuid = $document ? $document->getUuid() : null;

        $result = $this->repository->generate(
            $parts,
            $parentUuid,
            $documentUuid,
            'sulu_io',
            'de',
            'contact'
        );

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetHistory()
    {
        $expectedHistory = array(
            '/document1/document2',
            '/document1/goodbye',
        );

        $document1 = $this->documents['document2'];
        $document1->setResourceSegment('goodbye');
        $this->documentManager->flush();
        $document1->setResourceSegment('hellogoodbye');
        $this->documentManager->flush();
        $this->documentManager->refresh($document1);

        $result = $this->repository->getHistory($document1->getUuid(), 'sulu_io', 'de');

        $actualHistory = array();

        foreach ($result['_embedded']['resourcelocators'] as $historum) {
            $actualHistory[] = $historum['resourceLocator'];
        }

        $this->assertEquals($expectedHistory, $actualHistory);
    }

    public function testDelete()
    {
        $path = '/document1/document2';
        $routePath = '/cmf/sulu_io/routes/de/document1/document2';
        $locale = 'de';

        $this->assertNotNull($this->documentManager->find(null, $routePath));
        $this->repository->delete($path, 'sulu_io', $locale);
        $this->assertNull($this->documentManager->find(null, $routePath));
    }

    private function loadFixtures()
    {
        $this->documents['document1'] = $this->createDocument('/cmf/sulu_io/contents/document1');
        $this->documents['document2'] = $this->createDocument('/cmf/sulu_io/contents/document1/document2');
        $this->documentManager->flush();
    }

    private function createDocument($path)
    {
        $parent = $this->documentManager->find(null, PathHelper::getParentPath($path));
        $name = PathHelper::getNodeName($path);

        if (null === $parent) {
            throw new \InvalidArgumentException('Cannot find parent: ' . $path);
        }

        $document = new PageDocument();
        $document->setTitle($name);
        $document->setParent($parent);
        $document->setStructureType('contact');
        $document->setResourceSegment($name);
        $document->setLocale('de');

        $this->documentManager->persist($document);

        return $document;
    }
}
