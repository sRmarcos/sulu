<?php

namespace DTL\Component\Content\Compat\Structure;

use Sulu\Component\Content\StructureInterface;
use DTL\Component\Content\Structure\Structure;
use Sulu\Component\Content\Structure as LegacyStructure;
use DTL\Bundle\ContentBundle\Document\Document;
use Sulu\Component\Content\Property;
use DTL\Component\Content\Structure\Property as NewProperty;
use DTL\Component\Content\Document\PageInterface;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\Section\SectionProperty;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Block\BlockPropertyType;
use DTL\Component\Content\Document\DocumentInterface;
use DTL\Component\Content\Document\LocalizationState;
use DTL\Component\Content\Document\WorkflowState;
use Sulu\Component\Content\StructureType;
use DTL\Component\Content\Structure\Item;
use DTL\Component\Content\Structure\Section;
use DTL\Component\Content\Routing\PageUrlGenerator;
use DTL\Component\Content\Structure\Factory\StructureFactory;

class StructureBridge implements StructureInterface
{
    /**
     * @var Structure
     */
    protected $structure;

    /**
     * @var Document
     */
    protected $document;

    /**
     * @var PageUrlGenerator
     */
    private $urlGenerator;

    /**
     * @var StructureFactory
     */
    private $structureFactory;

    /**
     * @param Structure         $structure
     * @param DocumentInterface $document
     * @param PageUrlGenerator  $urlGenerator
     */
    public function __construct(
        Structure $structure,
        StructureFactory $structureFactory,
        PageUrlGenerator $urlGenerator,
        DocumentInterface $document = null
    ) {
        $this->structure = $structure;
        $this->structureFactory = $structureFactory;
        $this->document = $document;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param DocumentInterface $document
     */
    public function setDocument(DocumentInterface $document)
    {
        $this->document = $document;
    }

    /**
     * @param PageUrlGenerator $urlGenerator
     */
    public function setUrlGenerator(PageUrlGenerator $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritDoc}
     */
    public function setLanguageCode($language)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getLanguageCode()
    {
        return $this->getDocument()->getLocale();
    }

    /**
     * {@inheritDoc}
     */
    public function setWebspaceKey($webspace)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getWebspaceKey()
    {
        return $this->getDocument()->getWebspaceKey();
    }

    /**
     * {@inheritDoc}
     */
    public function getUuid()
    {
        return $this->getDocument()->getUuid();
    }

    /**
     * {@inheritDoc}
     */
    public function setUuid($uuid)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreator()
    {
        return $this->getDocument()->getCreator();
    }

    /**
     * {@inheritDoc}
     */
    public function setCreator($userId)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getChanger()
    {
        return $this->getDocument()->getChanger();
    }

    /**
     * {@inheritDoc}
     */
    public function setChanger($userId)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreated()
    {
        return $this->getDocument()->getCreated();
    }

    /**
     * {@inheritDoc}
     */
    public function setCreated(\DateTime $created)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getChanged()
    {
        return $this->getDocument()->getChanged();
    }

    /**
     * {@inheritDoc}
     */
    public function setChanged(\DateTime $changed)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getKey()
    {
        return $this->structure->name;
    }

    /**
     * TODO: Implement this
     */
    public function getInternal()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty($name)
    {
        $property = $this->structure->getChild($name);

        $propertyBridge = $this->createBridgeFromItem($name, $property);

        return $propertyBridge;
    }

    /**
     * {@inheritDoc}
     */
    public function hasProperty($name)
    {
        return $this->structure->hasChild($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getProperties($flatten = false)
    {
        if ($flatten) {
            $items = $this->structure->getProperties();
        } else {
            $items = $this->structure->getChildren();
        }

        $propertyBridges = array();
        foreach ($items as $propertyName => $property) {
            $propertyBridges[$propertyName] = $this->createBridgeFromItem($propertyName, $property);
        }

        return $propertyBridges;
    }

    /**
     * {@inheritDoc}
     */
    public function setHasChildren($hasChildren)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getHasChildren()
    {
        return $this->getDocument()->hasChildren();
    }

    /**
     * {@inheritDoc}
     */
    public function setChildren($children)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren()
    {
        $children = array();

        foreach ($this->getDocument()->getChildren() as $child) {
            $children[] = $this->documentToStructure($child);
        }

        return $children;
    }

    /**
     * {@inheritDoc}
     */
    public function getPublishedState()
    {
        return $this->getPage()->getPublishedState();
    }

    /**
     * {@inheritDoc}
     */
    public function setPublished($published)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getPublished()
    {
        return $this->getPage()->getPublished();
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyValue($name)
    {
        $this->notImplemented(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyNames()
    {
        return array_keys($this->structure->children);
    }

    /**
     * {@inheritDoc}
     */
    public function setType($type)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        if ($this->getDocument()->getLocalizationState() === LocalizationState::GHOST) {
            return StructureType::getGhost($this->getDocument()->getLocale());
        }

        if ($this->getDocument()->getLocalizationState() === LocalizationState::SHADOW) {
            return StructureType::getShadow($this->getDocument()->getLocale());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return $this->getDocument()->getPath();
    }

    /**
     * {@inheritDoc}
     */
    public function setPath($path)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function setHasTranslation($hasTranslation)
    {
        $this->readOnlyException(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getHasTranslation()
    {
        return $this->structure->getLocalizedProperties() ? true : false;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray($complete = true)
    {
        $result = array(
            'id' => $this->getUuid(),
            'path' => $this->getDocument()->getPath(),
            'nodeType' => $this->getNodeType(),
            'nodeState' => $this->getNodeState(),
            'internal' => false,
            'concreteLanguages' => $this->getDocument()->getRealLocales(),
            'hasSub' => count($this->getDocument()->getChildren()) ? true : false,
            'published' => $this->getDocument()->getPublished(),
            'title' => $this->getDocument()->getTitle(), // legacy system returns diffent fields for title depending on $complete
        );

        if ($this->document instanceof PageInterface) {
            $result['linked'] = $this->getDocument()->getRedirectType();
            $result['publishedState'] = $this->getDocument()->getWorkflowState() === WorkflowState::PUBLISHED;
            $result['navContexts'] = array();
        }

        if ($complete) {
            $result = array_merge($result, array(
                'enabledShadowLanguages' => $this->getDocument()->getShadowLocales(),
                'shadowOn' => $this->getDocument()->isLocalizationState(LocalizationState::SHADOW),
                'shadowBaseLanguage' => $this->getDocument()->getShadowLocale() ?: false,
                'template' => $this->structure->name,
                'originTemplate' => $this->structure->name,
                'creator' => $this->getDocument()->getCreator(),
                'changer' => $this->getDocument()->getChanger(),
                'created' => $this->getDocument()->getCreated(),
                'changed' => $this->getDocument()->getChanged(),
                'title' => $this->getDocument()->getTitle(),
                'url' => $this->urlGenerator->getResourceLocator($this->document),
            ));

            if (in_array(
                $this->getDocument()->getLocalizationState(),
                array(
                    LocalizationState::GHOST,
                    LocalizationState::SHADOW,
                )
            )) {
                $result['type'] = array(
                    'name' => $this->getDocument()->getLocalizationState(),
                    'value' => $this->getDocument()->getLocale(),
                );
            }

            $result = array_merge($this->getDocument()->getContent()->getArrayCopy(), $result);

            return $result;
        }

        if (null !== $this->getType()) {
            $result['type'] = $this->getType()->toArray();
        }

        if ($this->getDocument()->getRedirectType() == PageInterface::REDIRECT_TYPE_INTERNAL) {
            $result['linked'] = 'internal';
        } elseif ($this->getDocument()->getRedirectType() == PageInterface::REDIRECT_TYPE_EXTERNAL) {
            $result['linked'] = 'external';
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyByTagName($tagName, $highest = true)
    {
        $this->notImplemented(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertiesByTagName($tagName)
    {
        return $this->structure->getPropertiesByTag($tagName);
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyValueByTagName($tagName)
    {
        $this->notImplemented(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function hasTag($tag)
    {
        $this->notImplemented(__METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeType()
    {
        $redirectType = $this->getDocument()->getRedirectType();

        if (null === $redirectType) {
            return LegacyStructure::NODE_TYPE_CONTENT;
        }

        if (PageInterface::REDIRECT_TYPE_INTERNAL == $redirectType) {
            return LegacyStructure::NODE_TYPE_INTERNAL_LINK;
        }

        if (PageInterface::REDIRECT_TYPE_EXTERNAL == $redirectType) {
            return LegacyStructure::NODE_TYPE_EXTERNAL_LINK;
        }

        throw new \InvalidArgumentException(sprintf(
            'Unknown redirect type "%s"', $redirectType
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeName()
    {
        return $this->getDocument()->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalizedTitle($languageCode)
    {
        return $this->structure->getLocalizedTitle($languageCode);
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeState()
    {
        $state = $this->getDocument()->getWorkflowState();

        if ($state == WorkflowState::PUBLISHED) {
            return StructureInterface::STATE_PUBLISHED;
        }

        return StructureInterface::STATE_TEST;
    }

    /**
     * {@inheritDoc}
     */
    public function copyFrom(StructureInterface $structure)
    {
        $this->notImplemented(__METHOD__);
    }

    private function getSectionProperty($name, Section $property)
    {
        $sectionProperty = new SectionProperty(
            $name,
            array(
                'title' => $property->title,
                'infoText' => $property->description,
            ),
            $property->colSpan
        );

        foreach ($property->children as $childName => $child) {
            $sectionProperty->addChild($this->createBridgeFromItem($childName, $child));
        }

        return $sectionProperty;
    }

    private function getBlockProperty($name, NewProperty $property)
    {
        $blockProperty = new BlockProperty(
            $name,
            array(
                'title' => $property->title,
                'infoText' => $property->description,
            ),
            $property->type,
            $property->required,
            $property->localized,
            $property->maxOccurs,
            $property->minOccurs,
            $property->parameters,
            array(),
            $property->colSpan
        );

        foreach ($property->parameters['prototypes'] as $prototypeName => $prototype) {
            $blockType = new BlockPropertyType(
                $prototypeName,
                array(
                    'title' => $prototype->title,
                )
            );
        }

        return $blockProperty;
    }

    private function createBridgeFromItem($name, Item $property)
    {
        if ($property instanceof Section) {
            return $this->getSectionProperty($name, $property);
        }

        if ($property->type === 'block') {
            return $this->getBlockProperty($name, $property);
        }

        if (null === $property->type) {
            throw new \RuntimeException(sprintf(
                'Property name "%s" in "%s" has no type.',
                $property->name,
                $this->structure->resource
            ));
        }

        $propertyBridge = new Property(
            $name,
            array(
                'title' => $property->title,
                'infoText' => $property->description,
            ),
            $property->type,
            $property->required,
            $property->localized,
            $property->maxOccurs,
            $property->minOccurs,
            $property->parameters,
            array(),
            $property->colSpan
        );

        $propertyBridge->setStructure($this);

        foreach ($property->tags as $tag) {
            $propertyBridge->addTag(new PropertyTag($tag['name'], $tag['priority'], $tag['attributes']));
        }

        // map value

        if ($this->document) {
            $content = $this->document->getContent();
            if ($content->offsetExists($name)) {
                $data = $content->getArrayCopy();
                $propertyBridge->setValue($this->normalizeData($data[$name]));
            }
        }

        return $propertyBridge;
    }

    private function getDocument()
    {
        if (!$this->document) {
            throw new \RuntimeException(
                'Document has not been applied to structure yet, cannot retrieve data from structure.'
            );
        }

        return $this->document;
    }

    protected function readOnlyException($method)
    {
        throw new \BadMethodCallException(sprintf(
            'Compatibility layer StructureBridge instances are readonly. Tried to call "%s"',
            $method
        ));
    }

    private function getPage()
    {
        $document = $this->getDocument();
        if (!$document instanceof PageInterface) {
            throw new \BadMethodCallException(sprintf(
                'Cannot call getPublishedState on Document which does not implement PageInterface. Is "%s"',
                get_class($document)
            ));
        }

        return $document;
    }

    private function notImplemented($method)
    {
        throw new \InvalidArgumentException(sprintf(
            'Method "%s" is not yet implemented', $method
        ));
    }

    private function normalizeData(array $data = null)
    {
        if (null === $data) {
            return;
        }

        if (false === is_array($data)) {
            return $this->normalizeValue($data);
        }

        foreach ($data as &$value) {
            if (is_array($value)) {
                foreach ($value as $childKey => $childValue) {
                    $data[$childKey] = $this->normalizeData($childValue);
                }
            }

            $value = $this->normalizeValue($value);
        }

        return $data;
    }

    private function normalizeValue($value)
    {
        if ($value instanceof DocumentInterface) {
            return $this->documentToStructure($value);
        }

        return $value;
    }

    private function documentToStructure(DocumentInterface $document)
    {
        $structure = $this->structureFactory->getStructure($document->getDocumentType(), $document->getStructureType());

        return new $this($structure, $this->structureFactory, $this->urlGenerator, $document);
    }
}
