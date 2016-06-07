<?php
namespace Wix\GoogleAdsenseBundle\Command;

use Documents\UserRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wix\GoogleAdsenseBundle\Controller\SettingsController;
use Wix\GoogleAdsenseBundle\Document\AdUnit;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Wix\GoogleAdsenseBundle\Document\Component;
use Wix\GoogleAdsenseBundle\Document\User;

class SubmitMBClientCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('wix:submit_mb_client')
            ->setDescription('Submit users who have isMbClient field in the database to Adsense')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adsenseService = $this->getContainer()->get('wix_google_adsense.adsense_service');
        $google = $this->getContainer()->get('google_api.oauth2.adsense_host_service');

        if( ! $google instanceof \Google_AdSenseHostService )
        {
            return false;
        }

        $repo = $this->getContainer()->get('doctrine_mongodb');
        $dm   = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');

        if( ! $repo instanceof ManagerRegistry)
        {
            return false;
        }
        $component = $repo->getRepository("WixGoogleAdsenseBundle:Component");
        if( ! $component instanceof DocumentRepository)
        {
            return false;
        }

        $user = $repo->getRepository("WixGoogleAdsenseBundle:User");
        if( ! $user instanceof DocumentRepository)
        {
            return false;
        }
        $count = 0;
        $users = $user->createQueryBuilder('User')->field('isMbClient')->exists(true)->field('adUnitId')->exists(false)->getQuery()->execute();
        foreach( $users AS $user )
        {
            if( ! $user instanceof User )
            {
                continue;
            }
            $components = $component->createQueryBuilder("Component")->field('instanceId')->equals($user->getInstanceId())->field('adUnitId')->exists(false)->getQuery()->execute();
            $count += count($components);
            echo count($components)." , ";

            foreach( $components AS $component )
            {
                if( ! $component instanceof Component)
                {
                    continue;
                }

                $emptyAdUnit  = $this->createEmptyAdUnit($component);
                $adUnit       = $component->getAdUnit();
                $googleAdUnit = $this->populateAdUnit($adUnit, $emptyAdUnit, $component);

                echo $user->getAccountId() . "," . $user->getClientId() . "," . $component->getComponentId();

                $googleAdUnit = $google->accounts_adunits->insert($user->getAccountId(), $user->getClientId(), $googleAdUnit);
                $adCode       = $google->accounts_adunits->getAdCode($user->getAccountId(), $user->getClientId(), $googleAdUnit->getId());

                echo $googleAdUnit->getId();

                $component->setAdUnitId($googleAdUnit->getId())->setAdCode($adCode->getAdCode());

                //$dm->persist($user);
                $dm->persist($component);
                $dm->flush();

                exit;
            }
        }
        echo $count;
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