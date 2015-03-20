<?php

namespace DTL\Bundle\ContentBundle\Tests\Integration\Compat;

use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\StructureInterface;
use DTL\Bundle\ContentBundle\Tests\Integration\BaseTestCase;
use DTL\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\Content\Structure;
use DTL\Component\Content\Document\PageInterface;

class ContentMapper_typeTest extends BaseTestCase
{
    private $contentMapper;
    private $documentManager;

    public function setUp()
    {
        $this->initPhpcr();
        $this->parent = $this->getDm()->find(null, '/cmf/sulu_io/contents');
        $this->contentMapper = $this->getContainer()->get('dtl_content.compat.content_mapper');
        $this->documentManager = $this->getContainer()->get('doctrine_phpcr.odm.document_manager');
    }

    public function provideSaveStructureType()
    {
        return array(
            array(
                'section',
                array(
                    'title' => 'hello',
                    'url' => '/foo',
                    'color' => 'red',
                    'checkbox' => 'yes',
                    'date' => 'foo',
                ),
            ),
        );
    }

    /**
     * @dataProvider provideSaveStructureType
     */
    public function testSaveStructureType($structureType, $data)
    {
        $this->contentMapper->saveRequest(
            ContentMapperRequest::create('page')
                ->setTemplateKey($structureType)
                ->setWebspaceKey('sulu_io')
                ->setUserId(1)
                ->setState(StructureInterface::STATE_PUBLISHED)
                ->setLocale('de')
                ->setData($data)
        );
    }
}
