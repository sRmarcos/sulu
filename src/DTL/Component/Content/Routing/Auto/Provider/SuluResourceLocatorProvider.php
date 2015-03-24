<?php

namespace DTL\Component\Content\Routing\Auto\Provider;

use Symfony\Cmf\Component\RoutingAuto\TokenProviderInterface;
use Symfony\Cmf\Component\RoutingAuto\UriContext;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use DTL\Component\Content\Document\PageInterface;
use Doctrine\ODM\PHPCR\DocumentManager;

class SuluResourceLocatorProvider implements TokenProviderInterface
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function provideValue(UriContext $uriContext, $options)
    {
        $subject = $uriContext->getSubjectObject();
        $document = $subject;

        if (!$document instanceof PageInterface) {
            throw new \InvalidArgumentException(
                'Object must be instance of PageInterface'
            );
        }

        if ($options['parent']) {
            $document = $subject->getParent();

            if (!$document) {
                throw new \RuntimeException(sprintf(
                    'Document with title "%s" has no parent when trying to provide parent resource locator path',
                    $document->getTitle()
                ));
            }
        }

        $resourceSegments = array();

        do {
            if (!$document instanceof PageInterface) {
                break;
            }
            $this->documentManager->findTranslation(null, $document->getUuid(), $uriContext->getLocale());

            $resourceSegment = $document->getResourceSegment();

            if ($resourceSegment) {
                $resourceSegments[] = $resourceSegment;
            }
        } while ($document = $document->getParent());

        $resourceSegments = array_reverse($resourceSegments);

        $value = implode('/', $resourceSegments);

        return $value;
    }

    public function configureOptions(OptionsResolverInterface $options)
    {
        $options->setDefaults(array(
            'parent' => false,
        ));
    }
}
