<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavedesign\Mail\Dropbox\Controller\Index;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Area;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Mail\Template\TransportBuilder;

use \Dropbox as dbx;

use Shockwavedesign\Mail\Dropbox\Model\Config as ScopeConfig;

class Index extends \Magento\Framework\App\Action\Action
{
    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultPageFactory;

    /**
     * Core store config
     *
     * @var Config
     */
    protected $config;

    /**
     * Core store config
     *
     * @var Config
     */
    protected $scopeConfig;

    /** @var \Magento\Customer\Helper\View $customerViewHelper */
    protected $customerViewHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    private $storeManager;

    protected $customer;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param Customer $customer
     * @param ScopeConfig $scopeConfig
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Shockwavemk\Mail\Base\Model\Simulation\Config $config,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        Customer $customer,
        ScopeConfig $scopeConfig
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->config = $config;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->customer = $customer;
        $this->scopeConfig = $scopeConfig;

        parent::__construct($context);
    }

    /**
     * TODO
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        echo "dropbox test";

        /** @var \Shockwavedesign\Mail\Dropbox\Model\Dropbox\User $dropboxUser */
        $dropboxUser = $this->scopeConfig->getDropboxUser();

        $key = $this->config->getValue('system/smtp/dropbox_key', ScopeInterface::SCOPE_STORE);
        $secret = $this->config->getValue('system/smtp/dropbox_secret', ScopeInterface::SCOPE_STORE);

        $accessToken = $dropboxUser->getAccessToken();

        $path = $this->config->getValue('system/smtp/dropbox_host_temp_folder_path', ScopeInterface::SCOPE_STORE);

        $appInfo = dbx\AppInfo::loadFromJson(
            array(
                'key' => $key,
                'secret' => $secret
            )
        );
        //$webAuth = new dbx\WebAuthNoRedirect($appInfo, "PHP-Example/1.0");

        //$authorizeUrl = $webAuth->start();

        //echo "1. Go to: " . $authorizeUrl . "\n";
        //echo "2. Click \"Allow\" (you might have to log in first).\n";
        //echo "3. Copy the authorization code.\n";

        //$authCode = "WDUOKE3OsNIAAAAAAAATNEuROlA8b_uXFy0zJ6Rb_XM";

        //list($accessToken, $dropboxUserId) = $webAuth->finish($authCode);
        //print "DropboxUserId: " . $dropboxUserId . "Access Token: " . $accessToken . "\n";

        $dbxClient = new dbx\Client($accessToken, "PHP-Example/1.0");
        $accountInfo = $dbxClient->getAccountInfo();

        print_r($accountInfo);

        /*
        $f = fopen($path . "test.txt", "rb");
        $result = $dbxClient->uploadFile("/test.txt", dbx\WriteMode::add(), $f);
        fclose($f);
        print_r($result);
        */

        $folderMetadata = $dbxClient->getMetadataWithChildren("/");
        print_r($folderMetadata);

        $f = fopen($path . "test.txt", "w+b");
        $fileMetadata = $dbxClient->getFile("/test.txt", $f);
        fclose($f);
        print_r($fileMetadata);
    }
}
























