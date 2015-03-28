<?php

namespace DTL\Component\Content\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use PHPCR\NodeInterface;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use DTL\Component\Content\Document\DocumentInterface;
use DTL\Bundle\ContentBundle\Document\BasePageDocument;

/**
 * Force the document type
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
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

    /**
     * @param PreSerializeEvent $event
     */
    public function onPreSerialize(PreSerializeEvent $event)
    {
        $object = $event->getObject();

        if ($object instanceof BasePageDocument) {
        }
    }
}

