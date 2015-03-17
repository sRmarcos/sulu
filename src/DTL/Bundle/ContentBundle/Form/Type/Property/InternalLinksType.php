<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Bundle\ContentBundle\Form\Type\Property;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use DTL\Bundle\ContentBundle\Form\DataTransformer\DocumentCollectionToUuidsTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ODM\PHPCR\DocumentManager;

/**
 * Form type for internal links property type
 */
class InternalLinksType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setDefaults(array(
            'type' => 'document_object',
            'compound' => true,
            'allow_add' => true,
            'allow_delete' => true,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'internal_links';
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'collection';
    }
}
