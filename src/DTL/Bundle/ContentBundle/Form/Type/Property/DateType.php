<?php

namespace DTL\Bundle\ContentBundle\Form\Type\Property;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType as BaseDateType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Form type for color property type
 */
class DateType extends BaseDateType
{
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        parent::setDefaultOptions($options);
        $options->setDefaults(array(
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'date';
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'property';
    }
}

