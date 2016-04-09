<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavedesign\Mail\Dropbox\Model\Storeages;

use DirectoryIterator;
use \Dropbox as dbx;
use FilesystemIterator;
use IteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileObject;


class DropboxStoreage implements \Shockwavemk\Mail\Base\Model\Storeages\StoreageInterface
{
    const MESSAGE_FILE_NAME = 'message.json';
    const MESSAGE_HTML_FILE_NAME = 'message.html';
    const MAIL_FILE_NAME = 'mail.json';
    const ATTACHMENT_PATH = 'attachments';

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

    protected $_manager;

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
        $this->_manager = $manager;
    }

    public function getDropboxClient()
    {
        /** @var \Shockwavedesign\Mail\Dropbox\Model\Dropbox\User $dropboxUser */
        $dropboxUser = $this->_dropboxStorageConfig->getDropboxUser();
        $accessToken = $dropboxUser->getAccessToken();

        $path = $this->_dropboxStorageConfig->getDropboxHostTempFolderPath();
        $key = $this->_dropboxStorageConfig->getDropboxKey();
        $secret = $this->_dropboxStorageConfig->getDropboxSecret();

        return new dbx\Client($accessToken, "PHP-Example/1.0");
    }

    /**
     * Save file to spool path to avoid exceptions on external storeage provider connection
     *
     * @param \Shockwavemk\Mail\Base\Model\Mail $mail
     * @return $this
     * @throws \Exception
     */
    public function saveMessage($mail)
    {
        // convert message to html
        $messageHtml = quoted_printable_decode($mail->getMessage()->getBodyHtml(true));
        // try to store message to filesystem
        $this->storeFile(
            $messageHtml,
            $this->getFilePath($mail->getId(), self::MESSAGE_HTML_FILE_NAME)
        );

        // convert message to json
        $messageJson = json_encode($mail->getMessage());
        // try to store message to filesystem
        $this->storeFile(
            $messageJson,
            $this->getFilePath($mail->getId(), self::MESSAGE_FILE_NAME)
        );

        return $this;
    }

    /**
     * Restore a message from filesystem
     *
     * @return \Shockwavemk\Mail\Base\Model\Mail\Message $message
     * @throws \Magento\Framework\Exception\MailException
     */
    public function loadMessage($id)
    {
        $filePath = $this->getFilePath($id, self::MESSAGE_FILE_NAME);
        $messageJson = $this->restoreFile($filePath);
        $messageData = json_decode($messageJson);

        /** @var \Shockwavemk\Mail\Base\Model\Mail\Message $message */
        $message = $this->_manager->create('Shockwavemk\Mail\Base\Model\Mail\Message');

        if(!empty($messageData->type)) {
            $message->setType($messageData->type);
        }

        if(!empty($messageData->txt)) {
            $message->setBodyText($messageData->txt);
        }

        if(!empty($messageData->html)) {
            $message->setBodyHtml($messageData->html);
        }

        if(!empty($messageData->from)) {
            $message->setFrom($messageData->from);
        }

        if(!empty($messageData->subject)) {
            $message->setSubject($messageData->subject);
        }

        foreach($messageData->recipients as $recipient)
        {
            $message->addTo($recipient);
        }


        return $message;
    }

    /**
     * TODO
     *
     * @param \Shockwavemk\Mail\Base\Model\Mail $mail
     * @return bool
     * @throws \Exception
     */
    public function saveMail($mail)
    {
        // first save file to spool path
        // to avoid exceptions on external storeage provider connection

        // convert message to json
        $mailJson = json_encode($mail);

        // store message in temporary file system spooler
        $dropboxHostTempFolderPath = $this->_dropboxStorageConfig->getDropboxHostTempFolderPath();

        $folderPath = $dropboxHostTempFolderPath .
            DIRECTORY_SEPARATOR .
            $mail->getId();

        $fullFilePath = $folderPath .
            DIRECTORY_SEPARATOR .
            self::MAIL_FILE_NAME;

        // create a folder for message if needed
        if(!is_dir($folderPath))
        {
            $this->createFolder($folderPath);
        }

        // try to store message to filesystem
        return $this->storeFile($mailJson, $fullFilePath);
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
     * @param $id
     * @param $filePath
     * @return \Shockwavemk\Mail\Base\Model\Storeages\StoreageInterface
     */
    public function addAttachment($id, $filePath)
    {
        // TODO: Implement addAttachment() method.
    }

    /**
     * Load binary data from storeage provider
     *
     * @param \Shockwavemk\Mail\Base\Model\Mail $mail
     * @param string $path
     * @return string
     * @throws \Exception
     * @throws dbx\Exception_BadResponseCode
     * @throws dbx\Exception_OverQuota
     * @throws dbx\Exception_RetryLater
     * @throws dbx\Exception_ServerError
     */
    public function loadAttachment($mail, $path)
    {
        $dropboxHostTempFolderPath = $this->_dropboxStorageConfig
            ->getDropboxHostTempFolderPath();

        $folderPath = $dropboxHostTempFolderPath .
            $mail->getId() .
            DIRECTORY_SEPARATOR .
            self::ATTACHMENT_PATH;

        $filePath = $folderPath . $path;

        $attachmentFolder =
            DIRECTORY_SEPARATOR .
            $mail->getId() .
            DIRECTORY_SEPARATOR .
            self::ATTACHMENT_PATH;

        try {
            /** @var string $content */
            $content = $this->restoreFile($filePath);

        } catch(\Exception $e) {

            $handle = fopen($filePath, "w+b");
            $this->getDropboxClient()->getFile($attachmentFolder . $path, $handle);
            fclose($handle);

            /** @var string $content */
            $content = $this->restoreFile($filePath);
        }

        return $content;
    }

    /**
     * \Shockwavemk\Mail\Base\Model\Mail $mail
     *
     * @return array
     */
    public function getAttachments($mail)
    {
        // get combined files list: remote and local

        $mergedFolerFileList = $this->getMergedFolderFileList($mail);

        $attachments = [];
        foreach($mergedFolerFileList as $filePath => $fileMetaData)
        {
            /** @var \Shockwavemk\Mail\Base\Model\Mail\Attachment $attachment */
            $attachment = $this->_manager
                ->create('\Shockwavemk\Mail\Base\Model\Mail\Attachment');

            $attachment->setFilePath($filePath);
            $attachment->setMail($mail);

            // transfer all meta data into attachment object
            foreach($fileMetaData as $attributeKey => $attributeValue) {

                $attachment->setData($attributeKey, $attributeValue);

            }

            $attachments[$filePath] = $attachment;
        }

        return $attachments;
    }

    /**
     * TODO
     *
     * @param \Shockwavemk\Mail\Base\Model\Mail\Attachment $attachment
     * @return int $id
     */
    public function saveAttachment($attachment)
    {
        $binaryData = $attachment->getBinary();
        $filePath = $attachment->getFilePath();
        $mailId = $attachment->getMail()->getId();

        // store message in temporary file system spooler
        $dropboxHostTempFolderPath = $this->_dropboxStorageConfig->getDropboxHostTempFolderPath();

        $folderPath =
            $dropboxHostTempFolderPath .
            DIRECTORY_SEPARATOR .
            $mailId .
            DIRECTORY_SEPARATOR .
            self::ATTACHMENT_PATH;

        $filePath =
            $folderPath .
            DIRECTORY_SEPARATOR .
            $filePath;

        // create a folder for message if needed
        if(!is_dir($folderPath))
        {
            $this->createFolder($folderPath);
        }

        // try to store message to filesystem
        return $this->storeFile(
            $binaryData,
            $filePath
        );
    }

    /**
     * TODO
     *
     * @param \Shockwavemk\Mail\Base\Model\Mail $mail
     * @return $this
     */
    public function saveAttachments($mail)
    {
        /** @var \Shockwavemk\Mail\Base\Model\Mail\Attachment[] $attachments */
        $attachments = $mail->getAttachments();

        foreach($attachments as $attachment) {
            $this->saveAttachment($attachment);
        }

        return $this;
    }

    private function storeFile($data, $filePath)
    {
        for ($i = 0; $i < $this->_retryLimit; ++$i) {
            /* We try an exclusive creation of the file. This is an atomic operation, it avoid locking mechanism */
            $fp = @fopen($filePath, 'x');

            if (false === fwrite($fp, $data)) {
                return false;
            }
            fclose($fp);

            return $filePath;
        }

        throw new \Exception('Unable to create a file for enqueuing Message');
    }

    private function restoreFile($filePath)
    {
        for ($i = 0; $i < $this->_retryLimit; ++$i) {
            /* We try an exclusive creation of the file. This is an atomic operation, it avoid locking mechanism */
            @fopen($filePath, 'x');

            if (false === $fileData = file_get_contents ($filePath)) {
                return false;
            }
            return $fileData;
        }

        throw new \Exception('Unable to load a file for Message');
    }

    private function createFolder($folderPath)
    {
        return mkdir(
            $folderPath,
            0777,
            true
        );
    }

    /**
     * @param $id
     * @param $filePath
     * @return string
     */
    public function getFilePath($id, $filePath)
    {
        // store message in temporary file system spooler
        $dropboxHostTempFolderPath = $this->_dropboxStorageConfig
            ->getDropboxHostTempFolderPath();

        $folderPath = $dropboxHostTempFolderPath . $id;
        $fullFilePath =
            $folderPath .
            DIRECTORY_SEPARATOR .
            $filePath;

        // create a folder for message if needed
        if (!is_dir($folderPath)) {
            $this->createFolder($folderPath);
        }

        return $fullFilePath;
    }

    /**
     * @param $id
     * @return array|null
     */
    private function getMailRemoteFolderFileList($id)
    {
        $attachmentFolder =
            DIRECTORY_SEPARATOR .
            $id .
            DIRECTORY_SEPARATOR .
            self::ATTACHMENT_PATH;

        $remoteFolder = $this->getDropboxClient()
            ->getMetadataWithChildren($attachmentFolder);

        $files = [];

        if(!empty($contents = $remoteFolder['contents']) && is_array($contents)) {

            foreach($contents as $content) {

                if( is_bool($isDir = $content['is_dir'])
                    && $isDir === false
                    && !empty($path = $content['path'])
                ) {

                    $filePath = str_replace($attachmentFolder, '', $content['path']);
                    $files[$filePath] = $content;

                }

            }
        }

        return $files;
    }

    /**
     * @param $id
     * @return array
     */
    private function getMailLocalFolderFileList($id)
    {
        $spoolFolder = $this->_dropboxStorageConfig->getDropboxHostTempFolderPath() .
            $id .
            DIRECTORY_SEPARATOR .
            self::ATTACHMENT_PATH;

        // create a folder for attachments if needed
        if (!is_dir($spoolFolder)) {
            $this->createFolder($spoolFolder);
        }

        $objects = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($spoolFolder),
            RecursiveIteratorIterator::LEAVES_ONLY,
            FilesystemIterator::SKIP_DOTS
        );

        /** @var array $files */
        $files = [];

        /**
         * @var string $name
         * @var SplFileObject $object
         */
        foreach($objects as $path => $object) {
            if($object->getFilename() != '.' && $object->getFilename() != '..')
            {
                $filePath = str_replace($spoolFolder, '', $path);
                $file = [
                    'name' => $object->getFilename(),
                    'path' => $path
                ];
                $files[$filePath] = $file;
            }
        }

        return $files;
    }

    /**
     * @return string
     */
    public function getTempFilePath()
    {
        return $this->_dropboxStorageConfig->getDropboxHostTempFolderPath();
    }

    /**
     * TODO
     *
     * @param \Shockwavemk\Mail\Base\Model\Mail $mail
     * @return array
     */
    protected function getMergedFolderFileList($mail)
    {
        $remoteFolder = $this->getMailRemoteFolderFileList(
            $mail->getId()
        );

        $localFolderFileList = $this->getMailLocalFolderFileList(
            $mail->getId()
        );

        return array_merge(
            $localFolderFileList,
            $remoteFolder
        );
    }

    public function getRootLocalFolderFileList()
    {
        $spoolFolder = $this->_dropboxStorageConfig->getDropboxHostTempFolderPath();

        $objects = new IteratorIterator(
            new DirectoryIterator($spoolFolder)
        );

        /** @var array $files */
        $files = [];

        /**
         * @var string $name
         * @var SplFileObject $object
         */
        foreach($objects as $path => $object) {
            if($object->getFilename() != '.' && $object->getFilename() != '..')
            {
                $file = [
                    'name' => $object->getFilename(),
                    'path' => $object->getPathName(),
                    'modified' => $object->getMTime()
                ];
                $files[$object->getFilename()] = $file;
            }
        }

        return $files;
    }

    public function getCacheLimit()
    {
        return $this->_dropboxStorageConfig->getLocalStoreageLimit();
    }
}
