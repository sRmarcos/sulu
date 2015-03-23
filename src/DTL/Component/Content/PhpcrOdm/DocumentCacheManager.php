<?php

namespace DTL\Component\Content\PhpcrOdm;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\DocumentManager;
use DTL\Component\Content\PhpcrOdm\NamespaceRoleRegistry;

/**
 * Manager for accessing cached properties in Documents
 * because cached properties should not be publically accessible.
 */
class DocumentCacheManager
{
    private $namespaceRegistry;
    private $documentManager;

    public function __construct(DocumentManager $documentManager, NamespaceRoleRegistry $namespaceRegistry)
    {
        $this->documentManager = $documentManager;
        $this->namespaceRegistry = $namespaceRegistry;
    }

    public function setCache($object, $field, $value)
    {
        $metadata = $this->getMetadata($object);
        $field = $this->validateProperty($metadata, $field);
        $metadata->setFieldValue($object, $field, $value);
    }

    public function getCache($object, $field)
    {
        $metadata = $this->getMetadata($object);
        $field = $this->validateProperty($metadata, $field);

        return $metadata->getFieldValue($object, $field);
    }

    private function validateProperty(ClassMetadata $metadata, $field)
    {
        $alias = $this->namespaceRegistry->getAlias('cache');
        $field = 'cache' . ucfirst($field);
        $mapping = $metadata->getFieldMapping($field);
        $phpcrName = $mapping['property'];

        if (0 !== strpos($phpcrName, $alias)) {
            throw new \InvalidArgumentException(sprintf(
                'Property "%s" in class "%s" does not seem to be a cached property. ' .
                'expected it to be in namespace "%s", but the property name is "%s"',
                $field, $metadata->getName(), $alias, $phpcrName
            ));
        }

        return $field;
    }

    private function getMetadata($object)
    {
        return $this->documentManager->getClassMetadata(get_class($object));
    }
}
