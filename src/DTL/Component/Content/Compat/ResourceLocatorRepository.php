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
use Symfony\Cmf\Component\RoutingAuto\UriGenerator as RoutingAutoUriGenerator;
use Doctrine\ODM\PHPCR\DocumentManager;
use Symfony\Cmf\Component\RoutingAuto\UriContext;
use Symfony\Cmf\Component\RoutingAuto\Model\AutoRouteInterface;
use DTL\Component\Content\Structure\Structure;
use DTL\Component\Content\Structure\Factory\StructureFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use DTL\Bundle\ContentBundle\Document\Route;
use DTL\Bundle\ContentBundle\Document\BasePageDocument;
use DTL\Component\Content\Document\PageInterface;
use DTL\Component\Content\Routing\PageUrlGenerator;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * Resource locator repository
 */
class ResourceLocatorRepository implements ResourceLocatorRepositoryInterface
{
    private $uriGenerator;
    private $documentManager;
    private $structureFactory;
    private $urlGenerator;
    private $pageUrlGenerator;
    private $sessionManager;

    public function __construct(
        RoutingAutoUriGenerator $uriGenerator,
        DocumentManager $documentManager,
        StructureFactoryInterface $structureFactory,
        UrlGeneratorInterface $urlGenerator,
        PageUrlGenerator $pageUrlGenerator,
        SessionManagerInterface $sessionManager
    )
    {
        $this->uriGenerator = $uriGenerator;
        $this->documentManager = $documentManager;
        $this->structureFactory = $structureFactory;
        $this->urlGenerator = $urlGenerator;
        $this->pageUrlGenerator = $pageUrlGenerator;
        $this->sessionManager = $sessionManager;
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
                'self' => $this->urlGenerator->generate('post_node_resourcelocator_generate'),
            )
        );
    }

    public function getHistory($uuid, $webspaceKey, $languageCode)
    {
        $document = $this->documentManager->findTranslation(PageInterface::class, $uuid, $languageCode);
        $route = $document->getRoute($languageCode);
        $routes = array($route);

        foreach ($routes as $route) {
            $path = $this->pageUrlGenerator->getResourceLocatorFromRoute($route, $webspaceKey, $languageCode);

            $result[] = array(
                'id' => $route->getUuid(),
                'resourceLocator' => $path,
                'created' => $route->getCreated(),
                '_links' => array(
                    'delete' => $this->urlGenerator->generate('delete_node_resourcelocator', array(
                        'path' => $path,
                        'language' => $languageCode,
                        'webspace' => $webspaceKey,
                    )),
                    'restore' => $this->urlGenerator->generate('put_node_resourcelocator_restore', array(
                        'path' => $path,
                        'language' => $languageCode,
                        'webspace' => $webspaceKey,
                    )),
                )
            );
        }

        return array(
            '_embedded' => array(
                'resourcelocators' => $result
            ),
            '_links' => array(
                'self' => $this->urlGenerator->generate('get_node_resourcelocators', array(
                    'uuid' => $uuid,
                    'webspace' => $webspaceKey,
                    'language' => $languageCode,
                )),
            ),
            'total' => sizeof($result)
        );
    }

    public function delete($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $path = sprintf(
            '%s%s',
            $this->sessionManager->getRoutePath($webspaceKey, $languageCode),
            $path
        );
        $route = $this->documentManager->find(AutoRouteInterface::class, $path);

        if (!$route) {
            throw new \RuntimeException(sprintf(
                'Could not find route at path "%s"', $path
            ));
        }

        $this->documentManager->remove($route);
        $this->documentManager->flush();
    }

    public function restore($path, $userId,  $webspaceKey, $languageCode, $segmentKey = null)
    {
        throw new \InvalidArgumentException('TODO: THIS');
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
