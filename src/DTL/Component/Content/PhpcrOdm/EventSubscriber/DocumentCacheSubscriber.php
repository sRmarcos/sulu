<?php

namespace DTL\Component\Content\PhpcrOdm\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use DTL\Component\Content\Document\PageInterface;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ODM\PHPCR\Event;
use DTL\Component\Content\PhpcrOdm\NamespaceRoleRegistry;

/**
 * Subscriber to map the registered cache fields to the document
 */
class DocumentCacheSubscriber implements EventSubscriber
{
    private $classFieldMap = array();
    private $namespaceRegistry;

    /**
     * @param mixed $classFieldMap
     */
    public function __construct(NamespaceRoleRegistry $registry, array $classFieldMap = array())
    {
        $this->classFieldMap = $classFieldMap;
        $this->namespaceRegistry = $registry;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Event::loadClassMetadata
        );
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();
        $reflection = $metadata->getReflectionClass();
        $alias = $this->namespaceRegistry->getAlias('cache');

        foreach ($this->classFieldMap as $className => $cacheFields) {
            if (!$reflection->isSubclassOf($className)) {
                continue;
            }

            foreach ($cacheFields as $cacheField) {
                $cacheFieldName = 'cache' . ucfirst($cacheField);
                if (!$reflection->hasProperty($cacheFieldName)) {
                    throw new \InvalidArgumentException(sprintf(
                        'You must create a "%s" property in class "%s" in order to add a cached field mapping',
                        $cacheFieldName, $reflection->getName()
                    ));
                }

                $mapping = array(
                    'fieldName' => $cacheFieldName,
                    'property' =>  'cache-' . str_replace('_', '-', Inflector::tableize($cacheField)),
                    'type' => 'string',
                    'nullable' => true,
                    'translated' => true,
                );

                $metadata->mapField($mapping);
            }
        }
    }
}
