<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Bundle\ContentBundle\Tests\Integration\Form\Type\Property;

use DTL\Bundle\ContentBundle\Form\Type\Content\InternalLinksType;
use DTL\Bundle\ContentBundle\Tests\Integration\Form\Type\TypeTestCase;
use DTL\Bundle\ContentBundle\Document\PageDocument;
use DTL\Component\Content\Document\DocumentInterface;

class InternalLinksTypeTest extends TypeTestCase
{
    public $document1;
    public $document2;

    public function setUp()
    {
        $this->initPhpcr();
        $parent = $this->getDm()->find(null, '/cmf/sulu_io/contents');
        $document = new PageDocument();
        $document->setTitle('contact');
        $document->setParent($parent);
        $document->setResourceLocator('/contact');
        $document->setStructureType('contact');
        $this->getDm()->persist($document);
        $this->document1 = $document;

        $document = new PageDocument();
        $document->setTitle('contact-2');
        $document->setParent($parent);
        $document->setResourceLocator('/contact-2');
        $document->setStructureType('contact');
        $this->document2 = $document;
        $this->getDm()->persist($document);
        $this->getDm()->flush();
    }

    public function getPropertyAlias()
    {
        return 'internal_links';
    }

    public function provideFormSubmit()
    {
        return array(
            array(
                array(),
                array(
                    'document1',
                    'document2',
                ),
                array(
                    'document1',
                    'document2',
                ),
            ),
        );
    }

    protected function prepareFormSubmitData($data)
    {
        $result = array();
        foreach ($data as $documentVarName) {
            $result[] = $this->{$documentVarName}->getUUid();
        }

        return $result;
    }

    protected function assertFormSubmitData($expectedData, $content)
    {
        $this->assertCount(count($expectedData), $content);

        foreach ($expectedData as $expectedDocumentVarName) {
            $contentDocument = array_shift($content);
            $expectedDocument = $this->{$expectedDocumentVarName};
            $this->assertEquals($expectedDocument->getUuid(), $contentDocument->getUUid());
        }
    }
}

