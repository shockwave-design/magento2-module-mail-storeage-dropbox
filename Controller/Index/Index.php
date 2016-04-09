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

use Shockwavedesign\Mail\Dropbox\Model\Config as DropboxConfig;

class Index extends \Magento\Framework\App\Action\Action
{
    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultPageFactory;

    /**
     * Dropbox store config
     *
     * @var DropboxConfig
     */
    protected $dropboxConfig;

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
     * @param DropboxConfig $dropboxConfig
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param Customer $customer
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        DropboxConfig $dropboxConfig,
        Customer $customer
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->dropboxConfig = $dropboxConfig;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->customer = $customer;

        parent::__construct($context);
    }

    /**
     * TODO
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        /** @var \Shockwavedesign\Mail\Dropbox\Model\Cron\Uploader $uploader */
        $uploader = $this->_objectManager->create('\Shockwavedesign\Mail\Dropbox\Model\Cron\Uploader');
        $uploader->invoke();
    }
}
























