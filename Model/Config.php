<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavedesign\Mail\Dropbox\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Dropbox config
 */
class Config
{
    const XML_PATH_DROPBOX_USER = 'system/smtp/dropbox_user_id';

    const XML_PATH_DROPBOX_KEY = 'system/smtp/dropbox_key';

    const XML_PATH_DROPBOX_SECRET = 'system/smtp/dropbox_secret';

    const XML_PATH_DROPBOX_HOST_TEMP_FOLDER_PATH = 'system/smtp/dropbox_host_temp_folder_path';

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    protected $encryptor;
    protected $user;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Shockwavedesign\Mail\Dropbox\Model\Dropbox\User $user
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->user = $user;
    }

    public function getDropboxKey()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DROPBOX_KEY);
    }

    public  function getDropboxSecret()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DROPBOX_SECRET);
    }

    // Dropbox user

    public function getDropboxUser()
    {
        $dropboxUserId = $this->scopeConfig->getValue(self::XML_PATH_DROPBOX_USER);
        return $this->user->load($dropboxUserId);
    }

    public function getDropboxUsers()
    {
        $userCollection = $this->user->getCollection();
        return $userCollection->getData();
    }

    // Spool path
    public function getDropboxHostTempFolderPath()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DROPBOX_HOST_TEMP_FOLDER_PATH);
    }
}
