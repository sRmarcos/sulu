<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Bundle\ContentBundle\Tests\Integration\Document;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\Form\FormInterface;
use DTL\Component\Content\Form\ContentView;
use DTL\Bundle\ContentBundle\Document\PageDocument;
use Symfony\Cmf\Component\RoutingAuto\UriContextCollection;
use Symfony\Component\HttpFoundation\Request;
use DTL\Component\Content\Document\DocumentInterface;
use DTL\Component\Content\Document\LocalizationState;

class PageDocumentSerializationTest extends SuluTestCase
{
    public function setUp()
    {
        $this->manager = $this->getContainer()->get('doctrine_phpcr.odm.document_manager');
        $this->initPhpcr();
        $this->parent = $this->manager->find(null, '/cmf/sulu_io/contents');
        $this->serializer = $this->getContainer()->get('jms_serializer');
    }

    public function testSerialization()
    {
        $page = $this->createPage();
        $page->getContent()->preSerialize();
        $result = $this->serializer->serialize($page, 'json');

        return $result;
    }


    /**
     * @depends testSerialization
     */
    public function testDeserialization($data)
    {
        $page = $this->serializer->deserialize($data, PageDocument::class, 'json');
        var_dump($page->getContent());
        throw new \InvalidArgumentException('TODO: Content deserialization');
        $this->assertInstanceOf(PageDocument::class, $page);
    }

    private function createPage()
    {
        $page = new PageDocument();
        $page->setTitle('Hello');
        $page->setParent($this->parent);
        $page->setStructureType('contact');
        $page->setResourceSegment('foo');
        $page->setLocale('fr');
        $page->setContent(array(
            'title' => 'Foobar',
            'object' => new TestObject('hello'),
        ));

        return $page;
    }
}

class TestObject
{
    private $title;
    private $data = array(
        'key' => 'value',
    );

    public function __construct($title)
    {
        $this->title = $title;
    }

    public function getTitle() 
    {
        return $this->title;
    }
    
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getData() 
    {
        return $this->data;
    }
    
    
}
