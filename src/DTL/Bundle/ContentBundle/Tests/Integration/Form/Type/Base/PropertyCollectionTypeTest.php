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

use DTL\Bundle\ContentBundle\Form\Type\Content\ResizeableType;
use DTL\Bundle\ContentBundle\Tests\Integration\Form\Type\TypeTestCase;

class PropertyCollectionTypeTest extends TypeTestCase
{
    public function getPropertyAlias()
    {
        return 'property_collection';
    }

    /**
     * {@inheritDoc}
     */
    public function provideFormView()
    {
        return array(
            array(
                array(
                    'type' => 'text_area',
                    'max_occurs' => 1,
                    'max_occurs' => 1,
                ),
                array(
                    'multiple' => false,
                ),
            ),
            array(
                array(
                    'type' => 'text_area',
                    'min_occurs' => 1,
                    'max_occurs' => 2,
                ),
                array(
                    'multiple' => true,
                ),
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function provideFormSubmit()
    {
        return array(
            array(
                array(
                    'type' => 'text_area',
                    'min_occurs' => 1,
                    'max_occurs' => 2,
                ),
                array('hello'),
                array('hello'),
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function provideFrontViewValue()
    {
        return array(
            array(
                array(
                    'type' => 'text_area',
                    'max_occurs' => 1,
                    'max_occurs' => 1,
                ),
                'hello',
                'hello',
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function provideFrontViewAttributes()
    {
        return array(
            array(
                array(
                    'type' => 'text_area',
                    'max_occurs' => 1,
                    'max_occurs' => 1,
                ),
                array(
                ),
            ),
        );
    }
}
