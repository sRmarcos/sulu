<?php

namespace DTL\Component\Content\Compat\JmsSerializer;

use JMS\Serializer\JsonSerializationVisitor;
use DTL\Component\Content\Document\DocumentInterface;
use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;

class DocumentHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'event' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => DocumentInterface::class,
                'method' => 'handleDocument',
            ),
        );
    }

    public function handleDocument(
        JsonSerializationVisitor $visitor,
        DocumentInterface $document,
        array $type,
        Context $context
    ) {
        return $document->getUuid();
    }
}
