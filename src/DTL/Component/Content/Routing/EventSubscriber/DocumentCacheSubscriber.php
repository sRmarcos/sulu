<?php

namespace DTL\Component\Content\Routing\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Cmf\Component\RoutingAuto\RoutingAutoEvents;
use Symfony\Cmf\Component\RoutingAuto\Event\AutoRouteCreateEvent;
use Symfony\Cmf\Component\RoutingAuto\Event\AutoRouteMigrateEvent;
use DTL\Component\Content\PhpcrOdm\DocumentCacheManager;
use DTL\Component\Content\Document\PageInterface;
use DTL\Component\Content\PhpcrOdm\DocumentNodeHelper;

/**
 * This subscribers purpose is to cache and manage the PageDocuments
 * resource locator.
 *
 * NOTE: This is potentially very inefficient when used on large trees.
 */
class DocumentCacheSubscriber implements EventSubscriberInterface
{
    private $cacheManager;
    private $documentNodeHelper;

    public function __construct(DocumentCacheManager $cacheManager, DocumentNodeHelper $documentNodeHelper)
    {
        $this->cacheManager = $cacheManager;
        $this->documentNodeHelper = $documentNodeHelper;
    }

    public static function getSubscribedEvents()
    {
        return array(
            RoutingAutoEvents::POST_CREATE => 'onPostCreate',
            RoutingAutoEvents::POST_MIGRATE => 'onPostMigrate',
        );
    }

    public function onPostCreate(AutoRouteCreateEvent $event)
    {
        $uriContext = $event->getUriContext();
        $document = $uriContext->getSubjectObject();

        if (!$document instanceof PageInterface) {
            return;
        }

        $this->cacheManager->setCache($document, 'resourceLocator', $uriContext->getUri());
    }

    public function onPostMigrate(AutoRouteMigrateEvent $event)
    {
        $destAutoRoute = $event->getDestAutoRoute();
        $document = $destAutoRoute->getContent();

        if (!$document instanceof PageInterface) {
            return;
        }

        $this->recursivelyNullify($document);
    }

    private function recursivelyNullify(PageInterface $page)
    {
        $propertyName = $this->documentNodeHelper->encodeLocalizedContentName('resourceLocator', $page->getLocale());
        $page->getPhpcrNode()->setProperty($propertyName, null);

        foreach ($page->getChildren() as $child) {
            $this->recursivelyNullify($child);
        }
    }
}
