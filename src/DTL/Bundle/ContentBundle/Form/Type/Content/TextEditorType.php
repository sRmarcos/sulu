<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Bundle\ContentBundle\Form\Type\Content;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TextEditorType extends AbstractContentType
{
    public function getParent()
    {
        return 'text';
    }

    public function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            'godMode' => false,
            'tables' => true,
            'links' => true,
            'pasteFromWord' => true,
        );
    }

    public function getName()
    {
        return 'text_editor';
    }
}

