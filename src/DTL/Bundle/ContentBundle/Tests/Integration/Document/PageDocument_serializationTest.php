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
use DTL\Component\Content\PhpcrOdm\ContentContainer;

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
        $this->assertInstanceOf(PageDocument::class, $page);
        $content = $page->getContent();

        $this->assertInternalType('double', $content['double']);
        $this->assertInternalType('integer', $content['integer']);
        $this->assertInstanceOf(PageDocument::class, $content['object']);
        $this->assertInstanceOf(ContentContainer::class, $content);
        $this->assertCount(2, $content['arrayOfObjects']);
        $this->assertContainsOnlyInstancesOf(PageDocument::class, $content['arrayOfObjects']);
    }

    private function createPage()
    {
        $internalLink = new PageDocument();
        $internalLink->setTitle('Hello');

        $page = new PageDocument();
        $page->setTitle('Hello');
        $page->setParent($this->parent);
        $page->setStructureType('contact');
        $page->setResourceSegment('foo');
        $page->setLocale('fr');
        $page->setContent(array(
            'title' => 'Foobar',
            'object' => $internalLink,
            'arrayOfObjects' => array(
                $internalLink,
                $internalLink,
            ),
            'integer' => 1234,
            'double' => 1234.00,
        ));

        return $page;
    }
}
