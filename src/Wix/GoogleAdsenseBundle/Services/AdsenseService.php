<?php

namespace Wix\GoogleAdsenseBundle\Services;

use Wix\GoogleAdsenseBundle\Document\Component;
use Wix\GoogleAdsenseBundle\Document\User;
use Wix\GoogleAdsenseBundle\Document\AdUnit;
use Doctrine\ODM\MongoDB\DocumentManager;

class AdsenseService
{
    /** @var \Google_AdSenseHostService $google */
    private $google;

    /** @var DocumentManager $documentManager */
    private $documentManager;

    public function __construct(\Google_AdSenseHostService $google_AdSenseHostService, DocumentManager $documentManager)
    {
        $this->google          = $google_AdSenseHostService;
        $this->documentManager = $documentManager;
    }

    /**
     * inserts a new ad unit for this account and returns an object representation of it.
     *
     * @param AdUnit $adUnit
     * @param Component $component
     * @param User $user
     * @return mixed
     */
    public function insertAdUnit(AdUnit $adUnit, Component $component, User $user)
    {
        $googleAdUnit = $this->populateAdUnit($adUnit, $this->createEmptyAdUnit($component), $component);
        $googleAdUnit = $this->google->accounts_adunits->insert($user->getAccountId(), $user->getClientId(), $googleAdUnit);
        $adCode       = $this->google->accounts_adunits->getAdCode($user->getAccountId(), $user->getClientId(), $googleAdUnit->getId());
        $component
            ->setAdUnitId($googleAdUnit->getId())
            ->setAdCode($adCode->getAdCode());
        
        $this->documentManager->persist($component);
        $this->documentManager->flush();
    }

    private function createEmptyAdUnit(Component $component)
    {
        /* backup option */
        $backupOption = new \Google_AdUnitContentAdsSettingsBackupOption();
        $backupOption->setType('COLOR');
        $backupOption->setColor('ffffff');

        /* ads settings */
        $contentAdsSettings = new \Google_AdUnitContentAdsSettings();
        $contentAdsSettings->setBackupOption($backupOption);

        /* colors */
        $colors = new \Google_AdStyleColors();

        /* font */
        $font = new \Google_AdStyleFont();

        /* custom style */
        $customStyle = new \Google_AdStyle();
        $customStyle->setColors($colors);
        $customStyle->setFont($font);

        /* ad unit */
        $googleAdUnit = new \Google_AdUnit();
        $googleAdUnit->setContentAdsSettings($contentAdsSettings);
        $googleAdUnit->setCustomStyle($customStyle);
        $googleAdUnit->setName($this->getAdUnitName($component));

        return $googleAdUnit;
    }

    private function getAdUnitName(Component $component)
    {
        return sprintf('Wix ad unit for user %s #%s',
            $component->getInstanceId(),
            $component->getComponentId()
        );
    }

    private function populateAdUnit(AdUnit $adUnit, \Google_AdUnit $googleAdUnit, Component $component)
    {
        $googleAdUnit
            ->setId($component->getAdUnitId());

        $googleAdUnit
            ->getContentAdsSettings()
            ->setType($adUnit->getType());

        $googleAdUnit
            ->getContentAdsSettings()
            ->setSize($adUnit->getSize());

        $googleAdUnit
            ->getCustomStyle()
            ->setCorners($adUnit->getCornerStyle());

        /* font */
        $googleAdUnit
            ->getCustomStyle()
            ->getFont()
            ->setFamily($adUnit->getFontFamily());

        $googleAdUnit
            ->getCustomStyle()
            ->getFont()
            ->setSize($adUnit->getFontSize());

        /* colors */
        $googleAdUnit
            ->getCustomStyle()
            ->getColors()
            ->setBackground($adUnit->getBackgroundColor());

        $googleAdUnit
            ->getCustomStyle()
            ->getColors()
            ->setBorder($adUnit->getBorderColor());

        $googleAdUnit
            ->getCustomStyle()
            ->getColors()
            ->setText($adUnit->getTextColor());

        $googleAdUnit
            ->getCustomStyle()
            ->getColors()
            ->setTitle($adUnit->getTitleColor());

        $googleAdUnit
            ->getCustomStyle()
            ->getColors()
            ->setUrl($adUnit->getUrlColor());

        return $googleAdUnit;
    }


}