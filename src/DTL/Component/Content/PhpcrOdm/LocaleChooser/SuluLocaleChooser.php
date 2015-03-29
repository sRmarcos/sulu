<?php

namespace DTL\Component\Content\PhpcrOdm\LocaleChooser;

use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooserInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use DTL\Component\Content\Document\DocumentInterface;
use DTL\Component\Content\PhpcrOdm\DocumentNodeHelper;

/**
 * Sulu locale chooser implementation for the PHPCR-ODM
 */
class SuluLocaleChooser implements LocaleChooserInterface
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var DocumentNodeHelper
     */
    private $documentNodeHelper;

    /**
     * @param RequestAnalyzerInterface $requestAnalyzer
     * @param WebspaceManagerInterface $webspaceManager
     * @param DocumentNodeHelper $documentNodeHelper
     */
    public function __construct(
        RequestAnalyzerInterface $requestAnalyzer,
        WebspaceManagerInterface $webspaceManager,
        DocumentNodeHelper $documentNodeHelper
    )
    {
        $this->webspaceManager = $webspaceManager;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->documentNodeHelper = $documentNodeHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getFallbackLocales($document, ClassMetadata $metadata, $forLocale = null)
    {
        if (!$document instanceof DocumentInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Document must be instance of DocumentInterface, got "%s"',
                get_class($document)
            ));
        }

        $document->setRequestedLocale($forLocale);

        if (null === $document->getPhpcrNode()) {
            return array();
        }

        $res = $this->doGetFallbackLocales($document, $metadata, $forLocale);
        return $res;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocale()
    {
        $currentLocalization = $this->requestAnalyzer->getCurrentLocalization();

        if (!$currentLocalization) {
            return null;
        }

        return $currentLocalization->getLocalization();
    }

    /**
     * Return the fallback locales for the given document. If the webspace is available,
     * return the webspace locales, otherwise return any locales that are available in
     * the document and which are not the current locale.
     *
     * @param DocumentInterface $document
     * @param ClassMetadata $metadata
     * @param string $forLocale
     */
    private function doGetFallbackLocales(DocumentInterface $document, ClassMetadata $metadata, $forLocale = null)
    {
        $webspace = $this->requestAnalyzer->getWebspace();

        if (null === $webspace) {
            return $this->getOtherLocales($document, $forLocale);
        }

        $documentLocalizations = $this->documentNodeHelper->getLocales($document->getPhpcrNode());

        $localization = $webspace->getLocalization($forLocale);

        if (null === $localization) {
            return $documentLocalizations;
        }

        $locales = array_merge(
            $this->getParentLocalizations($localization),
            $this->getChildLocalizations($localization),
            $documentLocalizations
        );

        $locales = array_filter($locales, function ($locale) use ($forLocale) {
            return $locale !== $forLocale;
        });

        $locales = array_unique($locales);

        return array_values($locales);
    }

    /**
     * Return any existing locales which are not the $forLocale
     *
     * @param DocumentInterface $document
     * @param string $forLocale
     *
     * @return array
     */
    private function getOtherLocales(DocumentInterface $document, $forLocale)
    {
        $locales = $this->documentNodeHelper->getLocales($document->getPhpcrNode());
        return array_filter($locales, function ($locale) use ($forLocale) {
            return $locale !== $forLocale;
        });

    }

    /**
     * Return parent localizatios for the given Sulu Localization object.
     *
     * @param Localization $localization
     *
     * @return array
     */
    private function getParentLocalizations(Localization $localization)

    {
        $locales = array();

        do {
            $locales[] = $localization->getLocalization('_');
            $localization = $localization->getParent();
        } while ($localization != null);

        return $locales;
    }

    /**
     * Recursively return all child localizations
     *
     * @param Localization $localization
     * @param array $locales
     *
     * @return array
     */
    private function getChildLocalizations(Localization $localization, $locales = array())
    {
        $childLocalizations = $localization->getChildren();

        if (empty($childLocalizations)) {
            return $locales;
        }

        foreach ($childLocalizations as $childLocalization) {
            $locales[] = $childLocalization->getLocalization('_');
            $locales = $this->getChildLocalizations($childLocalization, $locales);
        }

        return $locales;
    }

    // The following methods  are required by the interface but are not required.
    // See: https://github.com/doctrine/phpcr-odm/issues/604
    public function setLocale($locale) {}
    public function getDefaultLocalesOrder() {}
    public function getDefaultLocale() {}
    public function setLocalePreference($localPreference) {}
    public function setFallbackLocales($locale, array $order, $replace = false) {}
}
