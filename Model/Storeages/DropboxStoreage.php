<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavedesign\Mail\Dropbox\Model\Storeages;

use \Dropbox as dbx;

class DropboxStoreage implements \Shockwavemk\Mail\Base\Model\Storeages\StoreageInterface
{
    /**
     * @var \Shockwavemk\Mail\Base\Model\Storeages\StoreageInterface
     */
    protected $_storage;

    /**
     * @param \Shockwavemk\Mail\Base\Model\Config $config
     * @param \Magento\Framework\Mail\MessageInterface $message
     * @param \Magento\Framework\ObjectManagerInterface $manager
     * @throws \Magento\Framework\Exception\MailException
     * @internal param null $parameters
     */
    public function __construct(
        \Shockwavemk\Mail\Base\Model\Config $config,
        \Magento\Framework\Mail\MessageInterface $message,
        \Magento\Framework\ObjectManagerInterface $manager
    )
    {

        $appInfo = dbx\AppInfo::loadFromJsonFile("INSERT_PATH_TO_JSON_CONFIG_PATH");
        $webAuth = new dbx\WebAuthNoRedirect($appInfo, "PHP-Example/1.0");
        $authorizeUrl = $webAuth->start();

        $dbxClient = new dbx\Client($accessToken, "PHP-Example/1.0");
        $accountInfo = $dbxClient->getAccountInfo();
        print_r($accountInfo);
    }

    /**
     * Send a mail using this transport
     *
     */
    public function saveMessage()
    {
        // TODO
    }

    /**
     * Send a mail using this transport
     *
     * @return \Magento\Framework\Mail\MessageInterface
     * @throws \Magento\Framework\Exception\MailException
     */
    public function loadMessage($id)
    {
        // TODO
    }

    /**
     * TODO
     *
     * @param $id
     * @param $filePath
     * @return \Shockwavemk\Mail\Base\Model\Storeages\StoreageInterface
     */
    public function addAttachment($id, $filePath)
    {
        // TODO: Implement addAttachment() method.
    }

    /**
     * TODO
     *
     * @return array
     */
    public function getAttachments($id)
    {
        // TODO: Implement getAttachments() method.
    }

    /**
     * Load binary data from storeage provider
     *
     * @param $id
     * @return string
     */
    public function loadAttachment($id)
    {
        $binaryString = "TODO_FILE_CONTENT"; // TODO
        return $binaryString;
    }
}
