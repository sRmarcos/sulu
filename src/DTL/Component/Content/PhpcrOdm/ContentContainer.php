<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\PhpcrOdm;

/**
 * Content container
 *
 * Holds the data which maps to a Sulu Structure.
 *
 * The content has a requirement to be serialized for the purposes
 * of the preview. This class provides methods which can map the types
 * of the contained content prior to serialization in order that it can
 * be deserialized with the correct types.
 */
class ContentContainer implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @var array
     */
    private $typeMap = array();

    /**
     * @var array
     */
    private $content = array();

    public function __construct(array $content = array())
    {
        $this->content = $content;
    }

    /**
     * Initialize the type map
     */
    public function preSerialize()
    {
        $this->typeMap = $this->mapTypes($this->getArrayCopy());
    }

    /**
     * Recursively map the type of each content
     *
     * @param array $content
     * @return array
     */
    private function mapTypes($content)
    {
        $typeMap = array();
        foreach ($content as $key => $value) {
            if (is_array($value) || $value instanceof \Traversable) {
                if (!count($value)) {
                    continue;
                }

                $typeMap[$key] = array('array', $this->getType(reset($value)));
                continue;
            }

            $typeMap[$key] = array($this->getType($value), null);
        }

        return $typeMap;
    }

    private function getType($value)
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }

    /**
     * Return the type map
     *
     * @return array
     */
    public function getTypeMap()
    {
        return $this->typeMap;
    }

    public function exchangeArray($newArray)
    {
        $this->content = $newArray;
    }

    public function getArrayCopy()
    {
        return $this->content;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->content[$offset];
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->content[$offset] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->content[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->content[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->content);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->content);
    }

}
