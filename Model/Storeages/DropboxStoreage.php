<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavedesign\Mail\Dropbox\Model\Storeages;

use \Dropbox as dbx;

class DropboxStoreage implements \Shockwavemk\Mail\Base\Model\Storeages\StoreageInterface
{
    const MESSAGE_FILE_NAME = 'message.html';

    const MAIL_FILE_NAME = 'mail.json';

    /**
     * @var \Shockwavemk\Mail\Base\Model\Storeages\StoreageInterface
     */
    protected $_storage;

    /**
     * @var \Shockwavemk\Mail\Base\Model\Config $_config
     */
    protected $_config;

    /**
     * @var \Shockwavedesign\Mail\Dropbox\Model\Config $_dropboxStorageConfig
     */
    protected $_dropboxStorageConfig;

    protected $_retryLimit;

    /**
     * @param \Shockwavemk\Mail\Base\Model\Config $config
     * @param \Magento\Framework\ObjectManagerInterface $manager
     * @throws \Magento\Framework\Exception\MailException
     * @internal param null $parameters
     */
    public function __construct(
        \Shockwavemk\Mail\Base\Model\Config $config,
        \Shockwavedesign\Mail\Dropbox\Model\Config $dropboxStoreageConfig,
        \Magento\Framework\Mail\MessageInterface $message,
        \Magento\Framework\ObjectManagerInterface $manager
    )
    {
        $this->_config = $config;
        $this->_retryLimit = 10; // TODO
        $this->_dropboxStorageConfig = $dropboxStoreageConfig;
    }

    /**
     * Send a mail using this transport
     * @param \Magento\Framework\Mail\MessageInterface $message
     * @param int $id
     * @throws \Exception
     */
    public function saveMessage($message, $id)
    {
        // first save file to spool path
        // to avoid exceptions on external storeage provider connection

        // convert message to html

        /** @var \Shockwavemk\Mail\Base\Model\Mail\Message $message */
        $messageHtml = quoted_printable_decode($message->getBodyHtml(true));

        // store message in temporary file system spooler
        $dropboxHostTempFolderPath = $this->_dropboxStorageConfig->getDropboxHostTempFolderPath();

        $folderPath = $dropboxHostTempFolderPath . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR;
        $fileName = self::MESSAGE_FILE_NAME;
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $fileName;

        // create a folder for message if needed
        if(!is_dir($folderPath))
        {
            $this->createFolder($folderPath);
        }

        // try to store message to filesystem
        $this->storeFile($messageHtml, $filePath);
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


        $f = fopen("working-draft.txt", "w+b");
        $fileMetadata = $dbxClient->getFile("/working-draft.txt", $f);
        fclose($f);
        print_r($fileMetadata);

        return $binaryString;
    }

    /**
     * TODO
     *
     * @param \Shockwavemk\Mail\Base\Model\Mail $mail
     * @param int $id
     * @return  $id
     */
    public function saveMail($mail, $id)
    {
        // first save file to spool path
        // to avoid exceptions on external storeage provider connection

        // convert message to json
        $mailJson = json_encode($mail);

        // store message in temporary file system spooler
        $dropboxHostTempFolderPath = $this->_dropboxStorageConfig->getDropboxHostTempFolderPath();

        $folderPath = $dropboxHostTempFolderPath . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR;
        $fileName = self::MAIL_FILE_NAME;
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $fileName;

        // create a folder for message if needed
        if(!is_dir($folderPath))
        {
            $this->createFolder($folderPath);
        }

        // try to store message to filesystem
        $this->storeFile($mailJson, $filePath);
    }

    /**
     * TODO
     *
     * @param $id
     * @return \Shockwavemk\Mail\Base\Model\Mail
     */
    public function loadMail($id)
    {
        // TODO: Implement loadMail() method.
    }

    /**
     * TODO
     *
     * @return array
     */
    public function loadAttachments($id)
    {
        // TODO: Implement loadAttachments() method.
        return array();
    }

    /**
     * TODO
     *
     * @return $id
     */
    public function saveAttachments($attachments, $id)
    {
        // TODO: Implement saveAttachments() method.
    }

    private function storeFile($json, $filePath)
    {
        for ($i = 0; $i < $this->_retryLimit; ++$i) {
            /* We try an exclusive creation of the file. This is an atomic operation, it avoid locking mechanism */
            $fp = @fopen($filePath, 'x');

            if (false === fwrite($fp, $json)) {
                return false;
            }
            return fclose($fp);
        }

        throw new \Exception('Unable to create a file for enqueuing Message');
    }

    private function createFolder($folderPath)
    {
        return mkdir($folderPath, 0777, true);
    }
}
