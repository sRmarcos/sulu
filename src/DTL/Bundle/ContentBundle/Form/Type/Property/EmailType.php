<?php

namespace DTL\Bundle\ContentBundle\Form\Type\Property;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType as BaseDateType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Form type for email property type
 */
class EmailType extends BaseDateType
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'email';
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'property';
    }
}

