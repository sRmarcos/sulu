<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\PhpcrOdm\EventSubscriber;

use Doctrine\ODM\PHPCR\Event;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use DTL\Component\Content\PhpcrOdm\Serializer\SerializerInterface;
use DTL\Bundle\ContentBundle\Document\ContentDocument;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\ManagerEventArgs;
use DTL\Component\Content\Document\DocumentInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;

class ContentSerializerSubscriber implements EventSubscriber
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var ContentInterface[]
     */
    private $serializationStack = array();

    /**
     * @var array
     */
    private $boundTranslations = array();

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Event::postLoad,
            Event::prePersist,
            Event::preUpdate,
            Event::endFlush,
            Event::postLoadTranslation,
            Event::preBindTranslation,
        );
    }

    /**
     * @param DocumentManager $documentManager
     * @param SerializerInterface $serializer
     */
    public function __construct(DocumentManager $documentManager, SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->documentManager = $documentManager;
    }

    /**
     * Deserialize the content data after loading the document
     *
     * @param LifecycleEventArgs $event
     */
    public function postLoad(LifecycleEventArgs $event)
    {
        $document = $event->getObject();

        if (false === $this->isDocument($document)) {
            return;
        }

        $data = $this->serializer->deserialize($document);
        $document->setContent($data);
    }

    public function postLoadTranslation(LifecycleEventArgs $event)
    {
        $document = $event->getObject();

        if (false === $this->isDocument($document)) {
            return;
        }

        if (!$document->getPhpcrNode()) {
            return;
        }

        $oid = spl_object_hash($document);

        if (isset($this->boundTranslations[$oid][$document->getLocale()])) {
            $content = $this->boundTranslations[$oid][$document->getLocale()];
            $document->setContent($content);
            return;
        }

        $this->postLoad($event);
    }

    public function preBindTranslation(LifecycleEventArgs $event)
    {
        $document = $event->getObject();
        if (false === $this->isDocument($document)) {
            return;
        }

        $unitOfWork = $event->getObjectManager()->getUnitOfWork();

        $oid = spl_object_hash($document);
        $currentLocale = $unitOfWork->getCurrentLocale($document);
        $this->boundTranslations[$oid][$currentLocale] = $document->getContent();
    }

    public function preUpdate(LifecycleEventArgs $event)
    {
        $this->prePersist($event);
    }

    /**
     * Serialize the content data before persisting the document
     *
     * @param LifecycleEventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $document = $event->getObject();

        if (false === $this->isDocument($document)) {
            return;
        }

        $this->serializationStack[] = $document;
    }

    /**
     * Update the PHPCR nodes with the new data after the flush
     * has happened (and we can be sure that the document is
     * now mapped to a PHPCR node.
     *
     * @param ManagerEventArgs $event
     */
    public function endFlush(ManagerEventArgs $event)
    {
        if (empty($this->serializationStack)) {
            return;
        }

        $session = $event->getObjectManager()->getPhpcrSession();

        foreach ($this->serializationStack as $document) {
            $this->serializer->serialize($document);
        }

        $session->save();

        $this->serializationStack = array();
    }

    /**
     * @param mixed $object
     */
    private function isDocument($object)
    {
        return $object instanceof DocumentInterface;
    }
}
