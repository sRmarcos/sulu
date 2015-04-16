<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use DateTime;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Jackalope\Query\Row;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryResultInterface;
use PHPCR\Util\PathHelper;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\BreadcrumbItem;
use Sulu\Component\Content\Compat\DataNormalizer;
use Sulu\Component\Content\Compat\Structure as LegacyStructure;
use Sulu\Component\Content\Compat\Property as LegacyProperty;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Compat\StructureType;
use Sulu\Component\Content\Compat\Stucture\LegacyStructureConstants;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManager;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\Property\PropertyInterface;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Exception\ExtensionNotFoundException;
use Sulu\Component\Content\Exception\TranslatedNodeNotFoundException;
use Sulu\Component\Content\Extension\ExtensionManager;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\Content\Mapper\Event\ContentNodeDeleteEvent;
use Sulu\Component\Content\Mapper\Event\ContentNodeEvent;
use Sulu\Component\Content\Mapper\LocalizationFinder\LocalizationFinderInterface;
use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Structure\Page;
use Sulu\Component\Content\Structure\Property;
use Sulu\Component\Content\Structure\Structure;
use Sulu\Component\Content\Template\TemplateResolver;
use Sulu\Component\Content\Types\ResourceLocatorInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Util\NodeHelper;
use Sulu\Component\Util\SuluNodeHelper;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;

/**
 * Maps content nodes to phpcr nodes with content types and provides utility function to handle content nodes
 *
 * Short term todo:
 *
 * - Rename localization, locale, language etc. to "locale"
 *
 * @package Sulu\Component\Content\Mapper
 */
class ContentMapper implements ContentMapperInterface
{
    /**
     * @var ContentTypeManager
     */
    private $contentTypeManager;

    /**
     * @var ExtensionManager
     */
    private $structureManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LocalizationFinderInterface
     */
    private $localizationFinder;

    /**
     * namespace of translation
     * @var string
     */
    private $languageNamespace;

    /**
     * prefix for internal properties
     * @var string
     */
    private $internalPrefix;

    /**
     * default language of translation
     * @var string
     */
    private $defaultLanguage;

    /**
     * default template
     * @var string[]
     */
    private $defaultTemplates;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var PathCleanupInterface
     */
    private $cleaner;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var TemplateResolver
     */
    private $templateResolver;

    /**
     * excepted states
     * @var array
     */
    private $states = array(
        WorkflowStage::PUBLISHED,
        WorkflowStage::TEST,
    );

    /**
     * @var MultipleTranslatedProperties
     */
    private $properties;

    /**
     * @var boolean
     */
    private $ignoreMandatoryFlag = false;

    /**
     * @var boolean
     */
    private $noRenamingFlag = false;

    /**
     * @var Cache
     */
    private $extensionDataCache;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var RlpStrategyInterface
     */
    private $strategy;

    /**
     * @var DataNormalizer
     */
    private $dataNormalizer;

    /**
     * @Var DocumentManager
     */
    private $documentManager;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    public function __construct(
        DocumentManager $documentManager,
        DataNormalizer $dataNormalizer,
        WebspaceManagerInterface $webspaceManager,
        FormFactoryInterface $formFactory,
        DocumentInspector $inspector,
        PropertyEncoder $encoder,

        StructureManagerInterface $structureManager,

        ContentTypeManagerInterface $contentTypeManager,
        SessionManagerInterface $sessionManager,
        EventDispatcherInterface $eventDispatcher,
        LocalizationFinderInterface $localizationFinder,
        PathCleanupInterface $cleaner,
        TemplateResolver $templateResolver,
        SuluNodeHelper $nodeHelper,
        RlpStrategyInterface $strategy,
        $defaultLanguage,
        $defaultTemplates,
        $languageNamespace,
        $internalPrefix,
        $stopwatch = null
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;
        $this->dataNormalizer = $dataNormalizer;
        $this->sessionManager = $sessionManager;
        $this->webspaceManager = $webspaceManager;
        $this->documentManager = $documentManager;
        $this->formFactory = $formFactory;
        $this->inspector = $inspector;
        $this->encoder = $encoder;

        // deprecated
        $this->localizationFinder = $localizationFinder;
        $this->eventDispatcher = $eventDispatcher;
        $this->defaultLanguage = $defaultLanguage;
        $this->defaultTemplates = $defaultTemplates;
        $this->languageNamespace = $languageNamespace;
        $this->internalPrefix = $internalPrefix;
        $this->cleaner = $cleaner;
        $this->templateResolver = $templateResolver;
        $this->nodeHelper = $nodeHelper;
        $this->strategy = $strategy;

        // optional
        $this->stopwatch = $stopwatch;
    }

    /**
     * {@inheritDoc}
     */
    public function saveRequest(ContentMapperRequest $request)
    {
        return $this->save(
            $request->getData(),
            $request->getTemplateKey(),
            $request->getWebspaceKey(),
            $request->getLocale(),
            $request->getUserId(),
            $request->getPartialUpdate(),
            $request->getUuid(),
            $request->getParentUuid(),
            $request->getState(),
            $request->getIsShadow(),
            $request->getShadowBaseLanguage(),
            $request->getType()
        );
    }

    /**
     * @deprecated
     *
     * {@inheritdoc}
     */
    public function save(
        $data,
        $templateKey,
        $webspaceKey,
        $locale,
        $userId,
        $partialUpdate = true,
        $uuid = null,
        $parentUuid = null,
        $state = null,
        $isShadow = null,
        $shadowBaseLanguage = null,
        $structureType = LegacyStructure::TYPE_PAGE
    ) {
        // $event = new ContentNodeEvent($node, $structure);
        // $this->eventDispatcher->dispatch(ContentEvents::NODE_PRE_SAVE, $event);

        $data = $this->dataNormalizer->normalize($data, $state, $parentUuid);

        $content = $data['content'];
        $extensions = $data['extensions'];
        unset($data['content']);
        unset($data['extensions']);

        if ($isShadow) {
            $data['shadowLocaleEnabled'] = true;
        }

        if ($shadowBaseLanguage) {
            $data['shadowLocale'] = $shadowBaseLanguage;
        }

        if ($uuid) {
            $document = $this->documentManager->find($uuid, $locale, $structureType);
        } else {
            $document = $this->documentManager->create($structureType);
        }

        $form = $this->formFactory->create($structureType, $document, array(
            'webspace_key' => $webspaceKey,
            'structure_name' => $templateKey,
        ));

        $form->submit($data, false);

        // TODO: Refactor the content so that conetnt types are agnostic to the node types
        //       Currently it is not possible to map content with a form as content types
        //       can do whatever they want in terms of mapping.
        $document->getContent()->bind($content);

        // TODO: As with content data, extensions should be set through the form
        $document->setExtensionsData($extensions);


        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        $this->documentManager->persist($document, $locale);
        $this->documentManager->flush();

        return $this->documentToStructure($document);
    }

    /**
     * TODO: Refactor this .. this should be handled in a listener or in the form, or something
     * {@inheritdoc}
     */
    public function saveExtension(
        $uuid,
        $data,
        $extensionName,
        $webspaceKey,
        $locale,
        $userId
    ) {
        $document = $this->documentManager->find($uuid, $locale);

        if ($document === null) {
            throw new TranslatedNodeNotFoundException($uuid, $locale);
        }

        if (!$document instanceof ExtensionBehavior) {
            throw new \RuntimeException(sprintf(
                'Document of class "%s" must implement the ExtensionableBehavior if it is to be extended',
                get_class($document)
            ));
        }

        // save data of extensions
        $extension = $this->structureManager->getExtension($document->getStructureType(), $extensionName);
        $node = $this->inspector->getNode($document);

        $extension->save($node, $data, $webspaceKey, $locale);
        $extensionData = $extension->load($node, $webspaceKey, $locale);

        $document->setExtension($extension->getName(), $extensionData);

        $this->documentManager->flush();

        return $this->documentToStructure($document);
    }

    /**
     * {@inheritdoc}
     */
    public function saveStartPage(
        $data,
        $templateKey,
        $webspaceKey,
        $locale,
        $userId,
        $partialUpdate = true,
        $isShadow = null,
        $shadowBaseLanguage = null
    ) {
        $uuid = $this->inspector->getUuid($this->getContentDocument($webspaceKey, $locale));

        return $this->save(
            $data,
            $templateKey,
            $webspaceKey,
            $locale,
            $userId,
            $partialUpdate,
            $uuid,
            null,
            WorkflowStage::PUBLISHED,
            $isShadow,
            $shadowBaseLanguage
        );
    }

    public function loadByParent(
        $uuid,
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $flat = true,
        $ignoreExceptions = false,
        $excludeGhosts = false
    ) {
        $parent = null;
        if ($uuid) {
            $parent = $this->documentManager->find($uuid, $languageCode);
        }

        if (null === $parent) {
            $parent = $this->getContentDocument($webspaceKey, $languageCode);
        }

        $fetchDepth = -1;
        if (false === $flat) {
            $fetchDepth = $depth;
        }

        $children = $this->inspector->getChildren($parent, null, $fetchDepth, $languageCode);
        $children = $this->documentsToStructureCollection($children->getArrayCopy());

        if ($flat) {
            foreach ($children as $child) {
                if ($depth === null || $depth > 1) {
                    $childChildren = $this->loadByParent(
                        $child->getUuid(),
                        $webspaceKey,
                        $languageCode,
                        $depth - 1,
                        $flat,
                        $ignoreExceptions,
                        $excludeGhosts
                    );
                    $children = array_merge($children, $childChildren);
                }
            }
        }

        return $children;
    }

    /**
     * {@inheritdoc}
     */
    public function load($uuid, $webspaceKey, $locale, $loadGhostContent = false)
    {
        $document = $this->documentManager->find($uuid, $locale);

        return $this->documentToStructure($document);
    }

    /**
     * {@inheritdoc}
     */
    public function loadStartPage($webspaceKey, $locale)
    {
        $startPage = $this->getContentDocument($webspaceKey, $locale);
        $startPage->setWorkflowStage(WorkflowStage::PUBLISHED);
        $startPage->setNavigationContexts(array());

        return $this->documentToStructure($startPage);
    }

    /**
     * {@inheritdoc}
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey, $locale, $segmentKey = null)
    {
        $uuid = $this->getResourceLocator()->loadContentNodeUuid(
            $resourceLocator,
            $webspaceKey,
            $locale,
            $segmentKey
        );

        $document = $this->loadDocument($uuid, $locale, array(
            'exclude_shadow' => false,
        ));

        return $this->documentToStructure($document);
    }

    /**
     * {@inheritdoc}
     */
    public function loadBySql2($sql2, $locale, $webspaceKey, $limit = null)
    {
        $query = $this->documentManager->createQuery($sql2, $locale);
        $query->setMaxResults($limit);

        $documents = $query->execute();

        return $this->documentsToStructureCollection($documents);
    }

    /**
     * {@inheritDoc}
     */
    public function loadByQuery(
        QueryInterface $query,
        $locale,
        $webspaceKey = null,
        $excludeGhost = true,
        $loadGhostContent = false
    ) {
        return $this->documentManager->createQuery($query, $locale, LegacyStructure::TYPE_PAGE)->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function loadTreeByUuid(
        $uuid,
        $locale,
        $webspaceKey = null,
        $excludeGhost = true,
        $loadGhostContent = false
    ) {
        $webspaceChildren = $this->inspector->getChildren($this->getContentDocument($webspaceKey, $locale));

        $documents = $this->filterDocuments($webspaceChildren, $locale, array(
            'load_ghost_content' => $loadGhostContent,
            'exclude_ghost' => $excludeGhost,
        ));

        return $this->documentsToStructureCollection($documents);
    }

    /**
     * {@inheritdoc}
     */
    public function loadTreeByPath(
        $path,
        $locale,
        $webspaceKey,
        $excludeGhost = true,
        $loadGhostContent = false
    ) {
        $documents = $this->loadTreeByUuid(null, $locale, null, $excludeGhost, $loadGhostContent);
        return $this->documentsToStructureCollection($documents);
    }

    /**
     * Load/hydrate a shalow structure with the given node.
     * Shallow structures do not have content properties / extensions
     * hydrated.
     *
     * @param NodeInterface $node
     * @param string        $localization
     * @param string        $webspaceKey
     *
     * @return StructureInterface
     */
    public function loadShallowStructureByNode(NodeInterface $contentNode, $localization, $webspaceKey)
    {
        $document = $this->documentManager->find($contentNode->getPath());

        return $this->documentToStructure($document);
    }

    /**
     * {@inheritdoc}
     */
    public function loadByNode(
        NodeInterface $node,
        $locale,
        $webspaceKey = null,
        $excludeGhost = true,
        $loadGhostContent = false,
        $excludeShadow = true
    ) {
        $document = $this->loadDocument(
            $node->getIdentifier(),
            $locale,
            array(
                'load_ghost_content' => $loadGhostContent,
                'exclude_ghost' => $excludeGhost,
                'exclude_shadow' => $excludeShadow,
            )
        );

        return $this->documentToStructure($document);
    }

    /**
     * {@inheritdoc}
     */
    public function loadBreadcrumb($uuid, $locale, $webspaceKey)
    {
        $document = $this->documentManager->find($uuid, $locale);

        $documents = array();
        $contentDocument = $this->getContentDocument($webspaceKey, $locale);
        $contentDepth = $this->inspector->getDepth($contentDocument);

        do {
            $documents[] = $document;

            $document = $this->inspector->getParent($document);
            $documentDepth = $this->inspector->getDepth($document);
        } while ($document instanceof ContentBehavior && $documentDepth >= $contentDepth);

        $items = array();
        foreach ($documents as $document) {
            $items[] = new BreadcrumbItem(
                $this->inspector->getDepth($document) - $contentDepth,
                $this->inspector->getUuid($document),
                $document->getTitle()
            );
        }

        $items = array_reverse($items);

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($uuid, $webspaceKey, $dereference = false)
    {
        $document = $this->documentManager->find($uuid);
        $this->documentManager->remove($document);
    }

    /**
     * {@inheritdoc}
     */
    public function move($uuid, $destParentUuid, $userId, $webspaceKey, $locale)
    {
        throw new \Exception('Do this');
        return $this->copyOrMove($uuid, $destParentUuid, $userId, $webspaceKey, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($uuid, $destParentUuid, $userId, $webspaceKey, $locale)
    {
        throw new \RuntimeException('Do this');
        $document = $this->documentManager->find($uuid);
        $this->documentManager->copy($document, $destParentUuid);
    }

    /**
     * {@inheritDoc}
     */
    public function copyLanguage(
        $uuid,
        $userId,
        $webspaceKey,
        $srcLanguageCode,
        $destLanguageCodes,
        $structureType = LegacyStructure::TYPE_PAGE
    ) {
        throw new \RuntimeException('Do this');
        if (!is_array($destLanguageCodes)) {
            $destLanguageCodes = array($destLanguageCodes);
        }

        $document = $this->documentManager->find($uuid, $srcLanguageCode);

        $parentNode = $this->getSession()->getNodeByIdentifier($document->getUuid())->getParent();
        $resourceLocator = $this->getResourceLocator();

        $data = $structure->toArray(true);
        foreach ($destLanguageCodes as $destLanguageCode) {
            if ($structure->hasTag('sulu.rlp')) {
                $parentUrl = $resourceLocator->getResourceLocatorByUuid(
                    $parentNode->getIdentifier(),
                    $webspaceKey,
                    $destLanguageCode
                );
                $rlp = $this->getResourceLocator()->getStrategy()->generate(
                    $document->getTitle(),
                    $parentUrl,
                    $webspaceKey,
                    $destLanguageCode
                );

                $data[$structure->getPropertyByTagName('sulu.rlp')->getName()] = $rlp;
            }

            $this->save(
                $data,
                $structure->getKey(),
                $webspaceKey,
                $destLanguageCode,
                $userId,
                false,
                $uuid,
                null,
                WorkflowStage::TEST,
                $structure->getIsShadow(),
                $structure->getShadowBaseLanguage(),
                $structureType
            );
        }

        return $structure;
    }

    /**
     * {@inheritDoc}
     */
    public function orderBefore($uuid, $beforeUuid, $userId, $webspaceKey, $locale)
    {
        throw new \RuntimeException('Do this');
    }

    /**
     * {@inheritDoc}
     */
    public function orderAt($uuid, $position, $userId, $webspaceKey, $locale)
    {
        throw new \RuntimeException('Do this');
    }

    /**
     * TODO: Refactor this. This should not effect the global state of the object, this
     *       should be scoped for each save request.
     *
     * TRUE dont rename pages on save
     * @param  boolean $noRenamingFlag
     * @return $this
     */
    public function setNoRenamingFlag($noRenamingFlag)
    {
        $this->noRenamingFlag = $noRenamingFlag;

        return $this;
    }

    /**
     * TRUE ignores mandatory in save
     * @param  bool  $ignoreMandatoryFlag
     * @return $this
     */
    public function setIgnoreMandatoryFlag($ignoreMandatoryFlag)
    {
        $this->ignoreMandatoryFlag = $ignoreMandatoryFlag;

        return $this;
    }

    /**
     * @param  string             $webspaceKey
     * @param  string             $locale
     * @param  bool               $move
     * @return StructureInterface
     */
    private function copyOrMove($uuid, $destParentUuid, $userId, $webspaceKey, $locale, $move = true)
    {
        // find localizations
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $localizations = $webspace->getAllLocalizations();

        // prepare utility
        $session = $this->getSession();

        // load from phpcr
        $node = $session->getNodeByIdentifier($uuid);
        $parentNode = $session->getNodeByIdentifier($destParentUuid);

        // prepare content node
        $content = $this->loadByNode($node, $locale, $webspaceKey, false, true);
        $nodeName = $content->getPropertyValue('title');

        // node name should not have a slash
        $nodeName = str_replace('/', '-', $nodeName);

        $nodeName = $this->cleaner->cleanup($nodeName, $locale);
        $nodeName = $this->getUniquePath($nodeName, $parentNode);

        // prepare pathes
        $path = $node->getPath();
        $destPath = $parentNode->getPath().'/'.$nodeName;

        if ($move) {
            // move node
            $session->move($path, $destPath);
        } else {
            // copy node
            $session->getWorkspace()->copy($path, $destPath);
            $session->save();

            // load new phpcr and content node
            $node = $session->getNode($destPath);
        }

        foreach ($localizations as $locale) {
            $content = $this->loadByNode($node, $locale->getLocalization(), $webspaceKey, false, true);

            // prepare parent content node
            $parentContent = $this->loadByNode($parentNode, $locale->getLocalization(), $webspaceKey, false, true);
            $parentResourceLocator = '/';
            if ($parentContent->hasTag('sulu.rlp')) {
                $parentResourceLocator = $parentContent->getPropertyValueByTagName('sulu.rlp');
            }
            // correct resource locator
            if (
                $content->getType() === null && $content->hasTag('sulu.rlp') &&
                $content->getNodeType() === RedirectType::NONE
            ) {
                $this->adaptResourceLocator(
                    $content,
                    $node,
                    $parentResourceLocator,
                    $webspaceKey,
                    $locale->getLocalization(),
                    $userId
                );

                // set changer of node
                $propertyTranslator->setLanguage($locale);
                $node->setProperty($propertyTranslator->getName('changer'), $userId);
                $node->setProperty($propertyTranslator->getName('changed'), new DateTime());
            }
        }

        // set changer of node in specific language
        $this->setChanger($node, $userId, $locale);

        $session->save();

        return $this->loadByNode($node, $locale, $webspaceKey);
    }

    /**
     * adopts resource locator for just moved or copied node
     * @param StructureInterface $content
     * @param NodeInterface      $node
     * @param string             $parentResourceLocator
     * @param string             $webspaceKey
     * @param string             $locale
     * @param int                $userId
     */
    private function adaptResourceLocator(
        StructureInterface $content,
        NodeInterface $node,
        $parentResourceLocator,
        $webspaceKey,
        $locale,
        $userId
    ) {
        // prepare objects
        $property = $content->getPropertyByTagName('sulu.rlp');
        $translatedProperty = new TranslatedProperty($property, $locale, $this->languageNamespace);
        $contentType = $this->getResourceLocator();
        $strategy = $contentType->getStrategy();

        // get resource locator pathes
        $srcResourceLocator = $content->getPropertyValueByTagName('sulu.rlp');

        if ($srcResourceLocator !== null) {
            $resourceLocatorPart = PathHelper::getNodeName($srcResourceLocator);
        } else {
            $resourceLocatorPart = $content->getPropertyValue('title');
        }

        // generate new resourcelocator
        $destResourceLocator = $strategy->generate(
            $resourceLocatorPart,
            $parentResourceLocator,
            $webspaceKey,
            $locale
        );

        // save new resource-locator
        $property->setValue($destResourceLocator);
        $contentType->write($node, $translatedProperty, $userId, $webspaceKey, $locale, null);
    }

    /**
     * Remove node with references (path, history path ...)
     *
     * @param NodeInterface $node
     * @param string Webspace - required by event listeners
     * @param boolean       $dereference Remove REFERENCE properties (or property
     *                                   values in the case of multi-value) from referencing nodes
     */
    private function deleteRecursively(NodeInterface $node, $webspace, $dereference = false)
    {
        foreach ($node->getReferences() as $ref) {
            if ($ref instanceof \PHPCR\PropertyInterface) {
                $child = $ref->getParent();

                if ($dereference) {
                    if ($ref->isMultiple()) {
                        $values = $ref->getValue();
                        foreach ($values as $i => $referringNode) {
                            if ($node->getIdentifier() === $referringNode->getIdentifier()) {
                                unset($values[$i]);
                            }
                        }

                        $ref->getParent()->setProperty($ref->getName(), $values, PropertyType::REFERENCE);
                    } else {
                        $ref->remove();
                    }
                }
            } else {
                $child = $ref;
            }

            if ($this->nodeHelper->hasSuluNodeType($child, array('sulu:path'))) {
                $this->deleteRecursively($child, $webspace, $dereference);
            }
        }

        $dispatchPost = false;

        // if the node being deleted is a structure, dispatch an event
        if ($this->nodeHelper->getStructureTypeForNode($node)) {
            $event = new ContentNodeDeleteEvent($this, $this->nodeHelper, $node, $webspace);
            $this->eventDispatcher->dispatch(ContentEvents::NODE_PRE_DELETE, $event);
            $dispatchPost = true;
        }

        $node->remove();

        if (true === $dispatchPost) {
            $this->eventDispatcher->dispatch(ContentEvents::NODE_POST_DELETE, $event);
        }
    }

    /**
     * @return ResourceLocatorInterface
     */
    public function getResourceLocator()
    {
        return $this->getContentType('resource_locator');
    }

    /**
     * returns a type with given name
     * @param $name
     * @return ContentTypeInterface
     */
    protected function getContentType($name)
    {
        return $this->contentTypeManager->get($name);
    }

    /**
     * @param $webspaceKey
     * @return Document
     */
    private function getContentDocument($webspaceKey, $locale)
    {
        return $this->documentManager->find(
            $this->sessionManager->getContentPath($webspaceKey),
            $locale
        );
    }

    /**
     * @param $webspaceKey
     * @param  string        $locale
     * @param  string        $segment
     * @return NodeInterface
     */
    protected function getRootRouteNode($webspaceKey, $locale, $segment)
    {
        return $this->documentManager->find(
            $this->sessionManager->getRoutePath($webspaceKey, $locale, $segment)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function convertQueryResultToArray(
        QueryResultInterface $queryResult,
        $webspaceKey,
        $locales,
        $fields,
        $maxDepth
    ) {
        $rootDepth = substr_count($this->sessionManager->getContentPath($webspaceKey), '/');

        $result = array();
        foreach ($locales as $locale) {
            foreach ($queryResult->getRows() as $row) {
                $pageDepth = substr_count($row->getPath('page'), '/') - $rootDepth;

                if ($maxDepth === null || $maxDepth < 0 || ($maxDepth > 0 && $pageDepth <= $maxDepth)) {
                    $item = $this->rowToArray($row, $locale, $webspaceKey, $fields);

                    if (false !== $item && !in_array($item, $result)) {
                        $result[] = $item;
                    }
                }
            };
        }

        return $result;
    }

    /**
     * converts a query row to an array
     */
    private function rowToArray(Row $row, $locale, $webspaceKey, $fields)
    {
        // reset cache
        $this->initializeExtensionCache();
        $templateName = $this->encoder->localizedSystemName('template', $locale);
        $nodeTypeName = $this->encoder->localizedSystemName('nodeType', $locale);

        // check and determine shadow-nodes
        $node = $row->getNode('page');
        $document = $this->documentManager->find($node->getIdentifier(), $locale);

        $redirectType = $document->getRedirectType();

        if ($redirectType === RedirectType::INTERNAL) {
            throw new \InvalidArgumentException('WTF');
        }

        $originLocale = $locale;
        if ($document instanceof ShadowLocaleBehavior) {
            $locale = $document->isShadowLocaleEnabled() ? $document->getShadowLocale() : $originLocale;
        }

        $nodeState = null;
        if ($document instanceof WorkflowStageBehavior) {
            $nodeState = $document->getWorkflowStage();
        }

        // if page is not piblished ignore it
        if ($nodeState !== WorkflowStage::PUBLISHED) {
            return false;
        }

        if (!isset($url)) {
            $structure = $this->inspector->getStructure($document);
            $url = $this->getRowUrl($document, $structure, $webspaceKey);
        }

        if (false === $url) {
            return;
        }

        // generate field data
        $fieldsData = $this->getFieldsData(
            $row,
            $node,
            $document,
            $fields[$originLocale],
            $document->getStructureType(),
            $webspaceKey,
            $locale
        );

        $structureType = $document->getStructureType();
        $shortPath = $this->inspector->getContentPath($document);

        return array_merge(
            array(
                'uuid' => $document->getUuid(),
                'nodeType' => $document->getRedirectType(),
                'path' => $shortPath,
                'changed' => $document->getChanged(),
                'changer' => $document->getChanger(),
                'created' => $document->getCreated(),
                'published' => $document->getWorkflowStage() === WorkflowStage::PUBLISHED,
                'creator' => $document->getCreator(),
                'title' => $document->getTitle(),
                'url' => $url,
                'urls' => $this->getLocalizedUrlsForPage($document),
                'locale' => $locale,
                'webspaceKey' => $this->inspector->getWebspace($document),
                'template' => $structureType,
                'parent' => $document->getParent()->getUuid(),
                'order' => null, // TODO: Implement order
            ),
            $fieldsData
        );
    }

    /**
     * Return extracted data (configured by fields array) from node
     */
    private function getFieldsData(Row $row, NodeInterface $node, $document, $fields, $templateKey, $webspaceKey, $locale)
    {
        $fieldsData = array();
        foreach ($fields as $field) {
            // determine target for data in result array
            if (isset($field['target'])) {
                if (!isset($fieldsData[$field['target']])) {
                    $fieldsData[$field['target']] = array();
                }
                $target = &$fieldsData[$field['target']];
            } else {
                $target = &$fieldsData;
            }

            // create target
            if (!isset($target[$field['name']])) {
                $target[$field['name']] = '';
            }
            if (($data = $this->getFieldData($field, $row, $node, $document, $templateKey, $webspaceKey, $locale)) !== null) {
                $target[$field['name']] = $data;
            }
        }

        return $fieldsData;
    }

    /**
     * Return data for one field
     */
    private function getFieldData($field, Row $row, NodeInterface $node, $document, $templateKey, $webspaceKey, $locale)
    {
        if (isset($field['column'])) {
            // normal data from node property
            return $row->getValue($field['column']);
        } elseif (isset($field['extension'])) {
            // data from extension
            return $this->getExtensionData(
                $node,
                $field['extension'],
                $field['property'],
                $webspaceKey,
                $locale
            );
        } elseif (
            isset($field['property'])
            && (!isset($field['templateKey']) || $field['templateKey'] === $templateKey)
        ) {
            // not extension data but property of node
            return $this->getPropertyData($document, $field['property']);
        }

        return;
    }

    /**
     * Returns data for property
     */
    private function getPropertyData($document, LegacyProperty $property)
    {
        return $document->getContent()->getProperty($property->getName())->getValue();
    }

    /**
     * Returns data for extension and property name
     */
    private function getExtensionData(
        NodeInterface $node,
        ExtensionInterface $extension,
        $propertyName,
        $webspaceKey,
        $locale
    ) {
        // extension data: load ones
        if (!$this->extensionDataCache->contains($extension->getName())) {
            $this->extensionDataCache->save(
                $extension->getName(),
                $this->loadExtensionData(
                    $node,
                    $extension,
                    $webspaceKey,
                    $locale
                )
            );
        }

        // get extension data from cache
        $data = $this->extensionDataCache->fetch($extension->getName());

        // if property exists set it to target (with default value '')
        return isset($data[$propertyName]) ? $data[$propertyName] : null;
    }

    /**
     * load data from extension
     */
    private function loadExtensionData(NodeInterface $node, ExtensionInterface $extension, $webspaceKey, $locale)
    {
        $extension->setLanguageCode($locale, $this->languageNamespace, '');
        $data = $extension->load(
            $node,
            $webspaceKey,
            $locale
        );

        return $extension->getContentData($data);
    }


    /**
     * Returns url of a row
     */
    private function getRowUrl(
        $document,
        Structure $structure,
        $webspaceKey
    ) {
        // if homepage
        if ($webspaceKey !== null && $this->sessionManager->getContentPath($webspaceKey) === $document->getPath()) {
            return '/';
        }

        if ($structure->hasPropertyWithTagName('sulu.rlp')) {
            $property = $structure->getPropertyByTagName('sulu.rlp');

            if ($property->getContentTypeName() !== 'resource_locator') {
                return 'http://'.$document->getContent()->getProperty($property->getName())->getValue();
            }

            return $document->getResourceSegment();
        }

        return '';
    }

    /**
     * Returns urls for given page for all locales in webspace
     * @param  Page          $page
     * @param  NodeInterface $node
     * @param  string        $webspaceKey
     * @param  string        $segmentKey
     * @return array
     */
    private function getLocalizedUrlsForPage(PageDocument $page)
    {
        $localizedUrls = array();
        $webspaceKey = $this->inspector->getWebspace($page);
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);

        foreach ($webspace->getAllLocalizations() as $localization) {
            $page = $this->documentManager->find($page->getUuid(), $localization->getLocalization());
            $localizedUrls[$page->getLocale()] = $page->getResourceSegment();
        }

        return $localizedUrls;
    }

    /**
     * {@inheritdoc}
     */
    public function restoreHistoryPath($path, $userId, $webspaceKey, $locale, $segmentKey = null)
    {
        $this->strategy->restoreByPath($path, $webspaceKey, $locale, $segmentKey);

        $content = $this->loadByResourceLocator($path, $webspaceKey, $locale, $segmentKey);
        $property = $content->getPropertyByTagName('sulu.rlp');
        $property->setValue($path);

        $node = $this->sessionManager->getSession()->getNodeByIdentifier($content->getUuid());

        $contentType = $this->contentTypeManager->get($property->getContentTypeName());
        $contentType->write(
            $node,
            new TranslatedProperty($property, $locale, $this->languageNamespace),
            $userId,
            $webspaceKey,
            $locale,
            $segmentKey
        );

        $this->sessionManager->getSession()->save();
    }

    private function validateRequired(ContentMapperRequest $request, $keys)
    {
        foreach ($keys as $required) {
            $method = 'get'.ucfirst($required);
            $val = $request->$method();

            if (null === $val) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'ContentMapperRequest "%s" cannot be null',
                        $required
                    )
                );
            }
        }
    }

    private function loadDocument($pathOrUuid, $locale, $options)
    {
        $options = array_merge(array(
            'load_ghost_content' => false,
            'exclude_ghost' => true,
            'exclude_shadow' => true,
        ), $options);

        $document = $this->documentManager->find($pathOrUuid, $locale);

        if ($this->optionsShouldExcludeDocument($document, $options)) {
            return;
        }

        return $document;
    }

    private function filterDocuments($documents, $locale, $options)
    {
        $options = array_merge(array(
            'load_ghost_content' => false,
            'exclude_ghost' => true,
            'exclude_shadow' => true,
        ), $options);

        $collection = array();
        foreach ($documents as $document) {
            if ($this->optionsShouldExcludeDocument($document, $options)) {
                continue;
            }

            $collection[] = $document;
        }

        return $collection;
    }

    private function optionsShouldExcludeDocument($document, array $options = array())
    {
        $state = $this->inspector->getLocalizationState($document);

        $isShadowOrGhost = $state === LocalizationState::GHOST || $state === LocalizationState::SHADOW;

        if (($options['exclude_ghost'] && $options['exclude_shadow']) && $isShadowOrGhost) {
            return true;
        }

        return false;
    }

    /**
     * initializes cache for extension data
     */
    private function initializeExtensionCache()
    {
        $this->extensionDataCache = new ArrayCache();
    }

    /**
     * Return a structure bridge corresponding to the given document
     *
     * @param DocumentInterface $document
     *
     * @return StructureBridge
     */
    private function documentToStructure($document)
    {
        if (null === $document) {
            return null;
        }
        $structure = $this->inspector->getStructure($document);
        $documentAlias = $this->inspector->getMetadata($document)->getAlias();

        $structureBridge = $this->structureManager->wrapStructure($documentAlias, $structure);
        $structureBridge->setDocument($document);

        return $structureBridge;
    }

    private function documentsToStructureCollection($documents)
    {
        $collection = array();
        foreach ($documents as $document) {
            if (!$document instanceof ContentBehavior) {
                continue;
            }
            $collection[] = $this->documentToStructure($document);
        }

        return $collection;
    }
}
