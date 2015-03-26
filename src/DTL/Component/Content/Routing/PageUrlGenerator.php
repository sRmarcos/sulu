<?php

namespace DTL\Component\Content\Routing;

use Symfony\Component\Routing\RouterInterface;
use DTL\Component\Content\Document\PageInterface;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use DTL\Bundle\ContentBundle\Document\Route;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Doctrine\ODM\PHPCR\DocumentManager;

/**
 * UrlGenerator for objects implementing the PageInterface
 */
class PageUrlGenerator implements VersatileGeneratorInterface
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(
        SessionManagerInterface $sessionManager,
        RequestAnalyzerInterface $requestAnalyzer,
        DocumentManager $documentManager
    )
    {
        $this->sessionManager = $sessionManager;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($document)
    {
        return $document instanceof PageInterface || $document instanceof Route;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteDebugMessage($document, array $parameters = array())
    {
        return get_class($document);
    }

    /**
     * {@inheritDoc}
     */
    public function generate($document, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if ($referenceType !== UrlGeneratorInterface::ABSOLUTE_URL) {
            throw new \BadMethodCallException(
                'Cannot generate non-absolute URLs, you must call the generate method with `true` ' . 
                'as the last argument.'
            );
        }

        $resourceLocator = $this->getResourceLocator($document);
        $portalUrl = $this->requestAnalyzer->getPortalUrl();

        return sprintf('http://%s%s', $portalUrl, $resourceLocator);
    }

    /**
     * {@inheritDoc}
     */
    public function getContext()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function setContext(RequestContext $context)
    {
    }

    /**
     * Return the resource locator for the given PageInterface object
     *
     * @param PageInterface $page
     * @param mixed $useCache
     */
    public function getResourceLocator(PageInterface $page)
    {
        $routes = $page->getRoutes();

        if (0 === $routes->count()) {
            $this->documentManager->refresh($page);
        }

        $routes = $page->getRoutes();

        if (0 === $routes->count()) {
            throw new RouteNotFoundException(sprintf(
                'Page document at "%s" does not have any route objects associated with it',
                $page->getPath()
            ));
        }

        foreach ($routes as $route) {
            if ($route->getAutoRouteTag() == $page->getLocale()) {
                return $this->getResourceLocatorFromRoute($route, $page->getWebspaceKey(), $page->getLocale());
            }
        }

        return $this->getResourceLocatorFromRoute($route, $page->getWebspaceKey(), $page->getLOcale());
    }

    /**
     * Return the resource locator for the given Route
     *
     * @param Route $route
     * @param string $webspaceKey
     * @param string $locale
     *
     * @throws RuntimeException If the resource locator cannot be determined
     */
    public function getResourceLocatorFromRoute(Route $route, $webspaceKey, $locale)
    {
        $portalPrefix = $this->sessionManager->getRoutePath($webspaceKey, $locale);

        if ($portalPrefix == $route->getPath()) {
            return '/';
        }

        $resourceLocator = substr(
            $route->getPath(),
            strlen($portalPrefix)
        );

        if (!$resourceLocator) {
            throw new \RuntimeException(sprintf(
                'Could not determine resource locator for route at path "%s" and portal prefix "%s"',
                $route->getPath(), $portalPrefix
            ));
        }

        return $resourceLocator;
    }
}

