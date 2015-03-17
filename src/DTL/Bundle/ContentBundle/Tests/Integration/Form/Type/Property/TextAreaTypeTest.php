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

use DTL\Bundle\ContentBundle\Form\Type\Content\TextAreaType;
use DTL\Bundle\ContentBundle\Tests\Integration\Form\Type\TypeTestCase;

class TextAreaTypeTest extends TypeTestCase
{
    public function getPropertyAlias()
    {
        return 'text_area';
    }

    /**
     * {@inheritDoc}
     */
    public function provideFormView()
    {
        return array(
            array(
                array(
                ),
                array(
                ),
            ),
            array(
                array(
                    'placeholder' => array(
                        'de' => 'Willkommen',
                        'fr' => 'Bienvenue',
                    ),
                ),
                array(
                    'placeholder' => array(
                        'de' => 'Willkommen',
                        'fr' => 'Bienvenue',
                    ),
                ),
            ),
        );
    }
}
