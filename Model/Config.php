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
    const XML_PATH_DROPBOX_USER = 'system/smtp/dropbox_user';

    const XML_PATH_DROPBOX_KEY = 'system/smtp/dropbox_key';

    const XML_PATH_DROPBOX_SECRET = 'system/smtp/dropbox_secret';

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    protected $encryptor;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
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
        return $this->scopeConfig->getValue(self::XML_PATH_DROPBOX_USER);
    }

    public function getDropboxUsers()
    {
        return array(
            'display_name' => 'entity_id'
        );
    }
}
