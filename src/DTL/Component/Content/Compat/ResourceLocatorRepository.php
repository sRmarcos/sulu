<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Bundle\ContentBundle\Repository;

use Sulu\Bundle\ContentBundle\Repository\ResourceLocatorRepository;
use DTL\Bundle\ContentBundle\Document\PageDocument;

/**
 * resource locator repository
 */
class ResourceLocatorRepository
{
    private $uriGenerator;

    public function __construct(UriGenerator $uriGenerator)
    {
        $this->uriGenerator = $uriGenerator;
    }

    public function generate($parts, $parentUuid, $uuid, $webspaceKey, $languageCode, $templateKey, $segmentKey = null)
    {
        $page = $this->getPage($uuid, $parentUuid);
        $uriContext = new UrlContext($page, $languageCode);
        $resourceLocator = $this->uriGenerator->generateUri($uriContext);

        return $resourceLocator;
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
                'No parent UUID given for resource locator generation',
            );
        }

        // Note this is currently hard coded -- we should refactor so that the admin passes the
        // document type.
        $page = new PageDocument();
        $page->setParent($parent);

        return $page;
    }
}
