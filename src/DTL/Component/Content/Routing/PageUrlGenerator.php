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
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(
        SessionManagerInterface $sessionManager,
        RequestAnalyzerInterface $requestAnalyzer
    )
    {
        $this->sessionManager = $sessionManager;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($page)
    {
        return $page instanceof PageInterface;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteDebugMessage($page, array $parameters = array())
    {
        return get_class($page);
    }

    /**
     * {@inheritDoc}
     */
    public function generate($page, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if ($referenceType !== UrlGeneratorInterface::ABSOLUTE_URL) {
            throw new \BadMethodCallException(
                'Cannot generate non-absolute URLs, you must call the generate method with `true` ' . 
                'as the last argument.'
            );
        }

        $resourceLocator = $this->getResourceLocator($page);
        $portalUrl = $this->requestAnalyzer->getPortalUrl();

        return sprintf('http://%s%s', $portalUrl, $resourceLocator);
    }

    /**
     * Return the resource locator for the given PageInterface object
     *
     * @param PageInterface $page
     * @param mixed $useCache
     */
    private function getResourceLocator(PageInterface $page, $useCache = true)
    {
        $cachedUrl = $page->getCachedResourceLocator();
        if (true === $useCache && $cachedUrl) {
            return $page->getCachedResourceLocator();
        }

        $routes = $page->getRoutes();

        if (empty($routes)) {
            throw new RouteNotFoundException(sprintf(
                'Page document at "%s" does not have any route objects associated with it',
                $page->getPath()
            ));
        }

        foreach ($routes as $route) {
            if ($route->getAutoRouteTag() == $page->getLocale()) {
                return $this->getResourceLocatorFromRoute($page, $route);
            }
        }

        return $this->getResourceLocatorFromRoute($page, reset($routes));
    }

    private function getResourceLocatorFromRoute($page, Route $route)
    {
        $routeLocalePath = $this->sessionManager->getRoutePath($page->getWebspaceKey(), $page->getLocale());
        $resourceLocator = substr(
            $route->getPath(),
            strlen($routeLocalePath)
        );

        return $resourceLocator;
    }

    public function getContext()
    {
    }

    public function setContext(RequestContext $context)
    {
    }
}

