<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Compat\Serializer;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Context;
use JMS\Serializer\JsonDeserializationVisitor;
use DTL\Component\Content\Compat\Structure\StructureBridge;
use DTL\Component\Content\Routing\PageUrlGenerator;
use DTL\Component\Content\Structure\Factory\StructureFactory;
use DTL\Bundle\ContentBundle\Document\PageDocument;

/**
 * Handle serializeation and deserialization of the StructureBridge
 */
class StructureBridgeHandler implements SubscribingHandlerInterface
{
    private $urlGenerator;
    private $structureFactory;

    public function __construct(
        PageUrlGenerator $urlGenerator,
        StructureFactory $structureFactory
    )
    {
        $this->urlGenerator = $urlGenerator;
        $this->structureFactory = $structureFactory;
    }

    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => StructureBridge::class,
                'method' => 'doSerialize',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => StructureBridge::class,
                'method' => 'doDeserialize',
            ),
        );
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param NodeInterface $nodeInterface
     * @param array $type
     * @param Context $context
     */
    public function doSerialize(
        JsonSerializationVisitor $visitor,
        StructureBridge $bridge,
        array $type,
        Context $context
    ) {
        $refl = new \ReflectionClass(StructureBridge::class);
        $documentProperty = $refl->getProperty('document');
        $structureProperty = $refl->getProperty('structure');
        $documentProperty->setAccessible(true);
        $structureProperty->setAccessible(true);

        $document = $documentProperty->getValue($bridge);
        $structure = $structureProperty->getValue($bridge);

        $context->accept(array(
            'document' => $document,
            'structure' => $structure->name
        ));
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param NodeInterface $nodeInterface
     * @param array $type
     * @param Context $context
     */
    public function doDeserialize(
        JsonDeserializationVisitor $visitor,
        array $data,
        array $type,
        Context $context
    ) {

        $document = $context->accept($data['document'], array('name' => PageDocument::class));
        $structure = $this->structureFactory->getStructure('page', $data['structure']);

        $bridge = new StructureBridge($structure, $this->structureFactory, $this->urlGenerator, $document);

        // filthy hack to set the Visitor::$result to null and force the
        // serializer to return the Bridge and not the Document
        $visitor->setNavigator($context->getNavigator());

        return $bridge;
    }
}

