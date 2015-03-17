<?php

namespace DTL\Bundle\ContentBundle\Form\Type\Property;

use Symfony\Component\Form\AbstractType;

/**
 * Form type for color property type
 */
class ColorType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'color';
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'property';
    }
}
