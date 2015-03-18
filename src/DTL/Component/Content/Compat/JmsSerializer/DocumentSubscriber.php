<?php

namespace DTL\Component\Content\Compat\JmsSerializer;

use DTL\Component\Content\Document\DocumentInterface;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;

class DocumentSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            array(
                'event' => Events::PRE_SERIALIZE,
                'method' => 'onPreSerialize',
            ),
        );
    }

    public function onPreSerialize(PreSerializeEvent $event)
    {
        $object = $event->getObject();

        if ($object instanceof DocumentInterface) {
            $event->setType(DocumentInterface::class);
        }
    }
}
