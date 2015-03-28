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
class ContentContainer extends \ArrayObject
{
    /**
     * @var array
     */
    private $typeMap = array();

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
    public function mapTypes($content)
    {
        foreach ($content as $key => $value) {
            if (is_array($value) || $value instanceof \Traversable) {
                $typeMap[$key] = $this->mapTypes($value);
                continue;
            }

            $typeMap[$key] = is_object($value) ? get_class($value) : gettype($value);
        }

        return $typeMap;
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
}
