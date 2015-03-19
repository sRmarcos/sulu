<?php

namespace DTL\Component\Content\Compat;

use Sulu\Bundle\ContentBundle\Repository\NodeRepository as LegacyNodeRepository;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Doctrine\ODM\PHPCR\DocumentManager;
use DTL\Component\Content\Compat\ContentMapper;
use Sulu\Component\Content\Mapper\ContentMapperRequest;

class NodeRepository implements NodeRepositoryInterface
{
    /**
     * @var LegacyNodeRepository
     */
    private $legacyNodeRepository;

    /**
     * @var ContentMapper
     */
    private $contentMapper;

    /**
     * @var string
     */
    private $apiBasePath = '/admin/api/nodes';

    /**
     * @param LegacyNodeRepository
     */
    public function __construct(LegacyNodeRepository $legacyNodeRepository, ContentMapper $contentMapper)
    {
        $this->legacyNodeRepository = $legacyNodeRepository;
        $this->contentMapper = $contentMapper;
    }

    /**
     * {@inheritDoc}
     */
    public function getNode(
        $uuid,
        $webspaceKey,
        $languageCode,
        $breadcrumb = false,
        $complete = true,
        $excludeGhosts = false
    )
    {
        return $this->legacyNodeRepository->getNode($uuid, $webspaceKey, $languageCode, $breadcrumb, $complete, $excludeGhosts);
    }

    /**
     * {@inheritDoc}
     */
    public function getNodes(
        $parent,
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $flat = true,
        $complete = true,
        $excludeGhosts = false
    )
    {
        return $this->legacyNodeRepository->getNodes(
            $parent,
            $webspaceKey,
            $languageCode,
            $depth,
            $flat,
            $complete,
            $excludeGhosts
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getNodesByIds($ids, $webspaceKey, $languageCode)
    {
        $result = array();
        $idString = '';

        if (!empty($ids)) {
            $idString = implode(',', $ids);

            $structures = $this->contentMapper->loadByIds($ids, $languageCode);

            foreach ($structures as $structure) {
                $result[] = $structure->toArray();
            }
        }

        return array(
            '_embedded' => array(
                'nodes' => $result
            ),
            'total' => sizeof($result),
            '_links' => array(
                'self' => $this->apiBasePath . '?ids=' . $idString
            )
        );
    }

    /**
        * {@inheritDoc}
     */
    public function getWebspaceNode(
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $excludeGhosts = false
    )
    {
        return $this->legacyNodeRepository->getWebspaceNode($webspaceKey, $languageCode, $depth, $excludeGhosts);
    }

    /**
        * {@inheritDoc}
     */
    public function getWebspaceNodes($languageCode)
    {
        return $this->legacyNodeRepository->getWebspaceNodes($languageCode);
    }

    /**
        * {@inheritDoc}
     */
    public function getFilteredNodes(array $filterConfig, $languageCode, $webspaceKey, $preview = false, $api = false)
    {
        return $this->legacyNodeRepository->getFilteredNodes($filterConfig, $languageCode, $webspaceKey, $preview, $api);
    }

    /**
        * {@inheritDoc}
     */
    public function getIndexNode($webspaceKey, $languageCode)
    {
        return $this->legacyNodeRepository->getIndexNode($webspaceKey, $languageCode);
    }

    /**
        * {@inheritDoc}
     */
    public function saveNode(
        $data,
        $templateKey,
        $webspaceKey,
        $languageCode,
        $userId,
        $uuid = null,
        $parentUuid = null,
        $state = null
    )
    {
        return $this->legacyNodeRepository->saveNode(
            $data,
            $templateKey,
            $webspaceKey,
            $languageCode,
            $userId,
            $uuid,
            $parentUuid,
            $state
        );
    }

    /**
     * {@inheritDoc}
     */
    public function saveIndexNode(
        $data,
        $templateKey,
        $webspaceKey,
        $languageCode,
        $userId,
        $isShadow = false,
        $shadowBaseLanguage = null
    )
    {
        return $this->legacyNodeRepository->saveIndexNode(
            $data,
            $templateKey,
            $webspaceKey,
            $languageCode,
            $userId,
            $isShadow,
            $shadowBaseLanguage
        );
    }

    /**
        * {@inheritDoc}
     */
    public function deleteNode($uuid, $webspaceKey)
    {
        return $this->legacyNodeRepository->deleteNode($uuid, $webspaceKey);
    }

    /**
        * {@inheritDoc}
     */
    public function getNodesTree(
        $uuid,
        $webspaceKey,
        $languageCode,
        $excludeGhosts = false,
        $appendWebspaceNode = false
    )
    {
        return $this->legacyNodeRepository->getNodesTree(
            $uuid,
            $webspaceKey,
            $languageCode,
            $excludeGhosts = false,
            $appendWebspaceNode = false
        );
    }

    /**
        * {@inheritDoc}
     */
    public function saveNodeRequest(ContentMapperRequest $mapperRequest)
    {
        return $this->legacyNodeRepository->saveNodeRequest($mapperRequest);
    }

    /**
     * {@inheritDoc}
     */
    public function loadExtensionData($uuid, $extension, $webspaceKey, $languageCode)
    {
        return $this->legacyNodeRepository->loadExtensionData($uuid, $extension, $webspaceKey, $languageCode);
    }

    /**
     * {@inheritDoc}
     */
    public function saveExtensionData($uuid, $data, $extensionName, $webspaceKey, $languageCode, $userId)
    {
        return $this->legacyNodeRepository->saveExtensionData($uuid, $data, $extensionName, $webspaceKey, $languageCode, $userId);
    }

    /**
     * {@inheritDoc}
     */
    public function moveNode($uuid, $destinationUuid, $webspaceKey, $languageCode, $userId)
    {
        return $this->legacyNodeRepository->moveNode($uuid, $destinationUuid, $webspaceKey, $languageCode, $userId);
    }

    /**
     * {@inheritDoc}
     */
    public function copyNode($uuid, $destinationUuid, $webspaceKey, $languageCode, $userId)
    {
        return $this->legacyNodeRepository->copyNode($uuid, $destinationUuid, $webspaceKey, $languageCode, $userId);
    }

    /**
        * {@inheritDoc}
     */
    public function orderBefore($uuid, $beforeUuid, $webspaceKey, $languageCode, $userId)
    {
        return $this->legacyNodeRepository->orderBefore($uuid, $beforeUuid, $webspaceKey, $languageCode, $userId);
    }

    /**
        * {@inheritDoc}
     */
    public function orderAt($uuid, $position, $webspaceKey, $languageCode, $userId)
    {
        return $this->legacyNodeRepository->orderAt($uuid, $position, $webspaceKey, $languageCode, $userId);
    }

    /**
        * {@inheritDoc}
     */
    public function copyLocale($uuid, $userId, $webspaceKey, $srcLocale, $destLocales)
    {
        return $this->legacyNodeRepository->copyLocale($uuid, $userId, $webspaceKey, $srcLocale, $destLocales);
    }
}
