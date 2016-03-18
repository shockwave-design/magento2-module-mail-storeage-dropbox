<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavedesign\Mail\Dropbox\Model\Storeages;

use \Dropbox as dbx;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use SplFileObject;
use Symfony\Component\Finder\SplFileInfo;

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

    protected function getDropboxClient()
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
     * @param \Shockwavemk\Mail\Base\Model\Mail\Message $message
     * @param int $id
     * @return $this
     * @throws \Exception
     */
    public function saveMessage($message, $id)
    {
        // convert message to html
        $messageHtml = quoted_printable_decode($message->getBodyHtml(true));
        // try to store message to filesystem
        $this->storeFile(
            $messageHtml,
            $this->getFilePath($id, self::MESSAGE_HTML_FILE_NAME)
        );

        // convert message to json
        $messageJson = json_encode($message);
        // try to store message to filesystem
        $this->storeFile(
            $messageJson,
            $this->getFilePath($id, self::MESSAGE_FILE_NAME)
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

        // ask dropbox how many files should be available

        // look into
        $accountInfo = $this->getDropboxClient()->getAccountInfo();
        print_r($accountInfo);
    }

    /**
     * Load binary data from storeage provider
     *
     * @param $id
     * @return string
     */
    public function loadAttachment($id, $path)
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

        $folderPath = $dropboxHostTempFolderPath . DIRECTORY_SEPARATOR . $id;
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
        // get combined files list: remote and local

        $remoteFolder = $this->getRemoteFolderFileList($id);

        $localFolderFileList = $this->getLocalFolderFileList($id);


        $mergedFolerFileList = array_merge($localFolderFileList, $remoteFolder);

        $attachments = [];
        foreach($mergedFolerFileList as $fileName => $fileMetaData)
        {
            /** @var \Shockwavemk\Mail\Base\Model\Mail\Attachment $attachment */
            $attachment = $this->_manager->create('\Shockwavemk\Mail\Base\Model\Mail\Attachment');
            $attachment->setDocumentUrl('http://www.google.de');
            $attachment->setDocumentFileName($fileName);

            // transfer all meta data into attachment object
            foreach($fileMetaData as $attributeKey => $attributeValue)
            {
                $attachment->setData($attributeKey, $attributeValue);
            }

            $attachments[] = $attachment;
        }


        return $attachments;
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

    private function storeFile($data, $filePath)
    {
        for ($i = 0; $i < $this->_retryLimit; ++$i) {
            /* We try an exclusive creation of the file. This is an atomic operation, it avoid locking mechanism */
            $fp = @fopen($filePath, 'x');

            if (false === fwrite($fp, $data)) {
                return false;
            }
            return fclose($fp);
        }

        throw new \Exception('Unable to create a file for enqueuing Message');
    }

    private function restoreFile($filePath)
    {
        for ($i = 0; $i < $this->_retryLimit; ++$i) {
            /* We try an exclusive creation of the file. This is an atomic operation, it avoid locking mechanism */
            $fp = @fopen($filePath, 'x');

            if (false === $fileData = file_get_contents ($filePath)) {
                return false;
            }
            return $fileData;
        }

        throw new \Exception('Unable to load a file for Message');
    }

    private function createFolder($folderPath)
    {
        return mkdir($folderPath, 0777, true);
    }

    /**
     * @param $id
     * @param $fileName
     * @return string
     */
    public function getFilePath($id, $fileName)
    {
        // store message in temporary file system spooler
        $dropboxHostTempFolderPath = $this->_dropboxStorageConfig->getDropboxHostTempFolderPath();

        $folderPath = $dropboxHostTempFolderPath . $id;
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $fileName;

        // create a folder for message if needed
        if (!is_dir($folderPath)) {
            $this->createFolder($folderPath);
            return $filePath;
        }
        return $filePath;
    }

    /**
     * @param $id
     * @return array|null
     */
    private function getRemoteFolderFileList($id)
    {
        $attachmentFolder = DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . self::ATTACHMENT_PATH;
        $remoteFolder = $this->getDropboxClient()->getMetadataWithChildren($attachmentFolder);

        if(!empty($contents = $remoteFolder['contents']) && is_array($contents)) {

            $files = [];

            foreach($contents as $content) {

                if( is_bool($isDir = $content['is_dir'])
                    && $isDir === false
                    && !empty($path = $content['path'])
                ) {

                    $filePath = str_replace($attachmentFolder, '', $content['path']);
                    $files[$filePath] = $content;

                }

            }

            return $files;
        }

        return null;
    }

    /**
     * @param $id
     * @return array
     */
    private function getLocalFolderFileList($id)
    {
        $spoolFolder = $this->_dropboxStorageConfig->getDropboxHostTempFolderPath() . $id . DIRECTORY_SEPARATOR . self::ATTACHMENT_PATH;

        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($spoolFolder), RecursiveIteratorIterator::LEAVES_ONLY, FilesystemIterator::SKIP_DOTS);

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

        // strip known folder


        return $files;
    }
}
