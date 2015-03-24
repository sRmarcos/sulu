<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Compat;

use DTL\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\ContentBundle\Repository\ResourceLocatorRepositoryInterface;
use Symfony\Cmf\Component\RoutingAuto\UriGenerator;
use Doctrine\ODM\PHPCR\DocumentManager;
use Symfony\Cmf\Component\RoutingAuto\UriContext;
use DTL\Component\Content\Structure\Structure;
use DTL\Component\Content\Structure\Factory\StructureFactoryInterface;

/**
 * Resource locator repository
 */
class ResourceLocatorRepository implements ResourceLocatorRepositoryInterface
{
    private $uriGenerator;
    private $documentManager;
    private $structureFactory;

    public function __construct(
        UriGenerator $uriGenerator,
        DocumentManager $documentManager,
        StructureFactoryInterface $structureFactory
    )
    {
        $this->uriGenerator = $uriGenerator;
        $this->documentManager = $documentManager;
        $this->structureFactory = $structureFactory;
    }

    public function generate($parts, $parentUuid, $uuid, $webspaceKey, $languageCode, $templateKey, $segmentKey = null)
    {
        $structure = $this->structureFactory->getStructure('page', $templateKey);
        $title = $this->getTitle($structure, $parts);

        $page = $this->getPage($uuid, $parentUuid);
        $page->setResourceSegment($title);

        $uriContext = new UriContext($page, $languageCode);
        $resourceLocator = $this->uriGenerator->generateUri($uriContext);

        return array(
            'resourceLocator' => $resourceLocator,
            '_links' => array(
            )
        );
    }

    public function getHistory($uuid, $webspaceKey, $languageCode)
    {
    }

    public function delete($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
    }

    public function restore($path, $userId,  $webspaceKey, $languageCode, $segmentKey = null)
    {
    }

    private function getPage($uuid, $parentUuid)
    {
        if ($uuid) {
            return $this->documentManager->find(null, $uuid);
        }

        $parent = $this->documentManager->find(null, $parentUuid);

        if (!$parent) {
            throw new \RuntimeException(
                'No parent UUID given for resource locator generation'
            );
        }

        // Note this is currently hard coded -- we should refactor so that the admin passes the
        // document type.
        $page = new PageDocument();
        $page->setParent($parent);

        return $page;
    }

    /**
     * @param StructureInterface $structure
     * @param array $parts
     * @param string $separator default '-'
     * @return string
     */
    private function getTitle(Structure $structure, array $parts, $separator = '-')
    {
        $title = '';
        // concat rlp parts in sort of priority
        foreach ($structure->getPropertiesByTag('sulu.rlp.part') as $property) {
            $title = $parts[$property->name] . $separator . $title;
        }
        $title = substr($title, 0, -1);

        return $title;
    }
}
