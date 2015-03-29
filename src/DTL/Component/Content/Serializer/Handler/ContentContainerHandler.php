<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Serializer\Handler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Context;
use DTL\Component\Content\PhpcrOdm\ContentContainer;
use JMS\Serializer\JsonDeserializationVisitor;

/**
 * Handle serializeation and deserialization of document content
 */
class ContentContainerHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => ContentContainer::class,
                'method' => 'doSerialize',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => ContentContainer::class,
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
        ContentContainer $container,
        array $type,
        Context $context
    ) {
        $array = $container->getArrayCopy();
        $container->preSerialize();
        return $context->accept(array(
            'typeMap' => $container->getTypeMap(),
            'content' => $container->getArrayCopy(),
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
        $container = new ContentContainer();
        $typeMap = $data['typeMap'];

        if (!isset($data['content'])) {
            return $container;
        }

        $content = $data['content'];

        foreach ($content as $key => $value) {
            $type = $typeMap[$key];
            $deserialized = $context->accept(
                $value,
                array(
                    'name' => $type[0],
                    'params' => isset($type[1]) ? array(array('name' => $type[1])) : array()
                )
            );

            $container[$key] = $deserialized;
        }

        return $container;
    }
}
