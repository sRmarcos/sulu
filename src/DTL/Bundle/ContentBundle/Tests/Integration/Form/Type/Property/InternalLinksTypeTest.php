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

class InternalLinksTypeTest extends TypeTestCase
{
    static public $document1;
    static public $document2;

    public function setUp()
    {
    }

    public function getPropertyAlias()
    {
        return 'internal_links';
    }

    public function provideFormSubmit()
    {
        $this->initPhpcr();
        $parent = $this->getDm()->find(null, '/cmf/sulu_io/contents');
        $document = new PageDocument();
        $document->setTitle('contact');
        $document->setParent($parent);
        $document->setResourceLocator('contact');
        $document->setStructureType('contact');
        $this->getDm()->persist($document);
        $document1 = $document;

        $document = new PageDocument();
        $document->setTitle('contact-2');
        $document->setParent($parent);
        $document->setResourceLocator('contact-2');
        $document->setStructureType('contact');
        $document2 = $document;
        $this->getDm()->persist($document);
        $this->getDm()->flush();

        return array(
            array(
                array(),
                array(
                    $document1->getUuid(),
                    $document2->getUuid(),
                ),
                array(),
            ),
        );
    }
}

