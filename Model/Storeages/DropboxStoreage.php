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
    protected $_dropboxHostTempFolderPath;

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
        $this->_dropboxHostTempFolderPath = $this->_dropboxStorageConfig->getDropboxHostTempFolderPath();
    }

    /**
     *
     *
     * @return dbx\Client
     */
    public function getDropboxClient()
    {
        /** @var \Shockwavedesign\Mail\Dropbox\Model\Dropbox\User $dropboxUser */
        $dropboxUser = $this->_dropboxStorageConfig->getDropboxUser();
        $accessToken = $dropboxUser->getAccessToken();

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

        $this->prepareFolderPath(
            $this->getMailFolderPath($mail)
        );
        
        // try to store message to filesystem
        $this->storeFile(
            $messageHtml,
            $this->getMailLocalFilePath($mail, DIRECTORY_SEPARATOR . self::MESSAGE_HTML_FILE_NAME)
        );

        // convert message to json
        $messageJson = json_encode($mail->getMessage());
        // try to store message to filesystem
        $this->storeFile(
            $messageJson,
            $this->getMailLocalFilePath($mail, DIRECTORY_SEPARATOR . self::MESSAGE_FILE_NAME)
        );

        return $this;
    }

    /**
     * Restore a message from filesystem
     *
     * @param \Shockwavemk\Mail\Base\Model\Mail $mail
     * @return \Shockwavemk\Mail\Base\Model\Mail\Message $message
     * @throws \Exception
     * @throws \Zend_Mail_Exception
     */
    public function loadMessage($mail)
    {
        $localFilePath = $this->getMailLocalFilePath($mail, DIRECTORY_SEPARATOR . self::MESSAGE_FILE_NAME);
        $remoteFilePath = $this->getMailRemoteFilePath($mail, DIRECTORY_SEPARATOR . self::MESSAGE_FILE_NAME);

        $messageJson = $this->restoreFile($localFilePath);

        if(empty($messageJson)) {
            $this->restoreRemoteFileToLocalFile($localFilePath, $remoteFilePath);
            $messageJson = $this->restoreFile($localFilePath);
        }

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

        if(!empty($messageData->recipients)) {
            foreach($messageData->recipients as $recipient)
            {
                $message->addTo($recipient);
            }
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

        $this->prepareFolderPath(
            $this->getMailFolderPath($mail)
        );

        // try to store message to filesystem
        return $this->storeFile(
            $mailJson,
            $this->getMailLocalFilePath($mail, DIRECTORY_SEPARATOR . self::MAIL_FILE_NAME)
        );
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
        $attachmentFolder = DIRECTORY_SEPARATOR . self::ATTACHMENT_PATH;

        $localFilePath = $this->getMailLocalFilePath($mail, $attachmentFolder . $path);
        $remoteFilePath = $this->getMailRemoteFilePath($mail, $attachmentFolder . $path);

        $content = $this->restoreFile($localFilePath);
        if(empty($content)) {
            $this->restoreRemoteFileToLocalFile($localFilePath, $remoteFilePath);
            $content = $this->restoreFile($localFilePath);
        }

        return $content;
    }

    /**
     * Returns attachments for a given mail
     * 
     * \Shockwavemk\Mail\Base\Model\Mail $mail
     *
     * @return \Shockwavemk\Mail\Base\Model\Mail\Attachment[]
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
     * Save an attachment binary to a file in host temp folder
     *
     * @param \Shockwavemk\Mail\Base\Model\Mail\Attachment $attachment
     * @return int $id
     */
    public function saveAttachment($attachment)
    {
        $binaryData = $attachment->getBinary();
        $mail = $attachment->getMail();

        $folderPath = $this->getMailFolderPath($mail) .
            DIRECTORY_SEPARATOR .
            self::ATTACHMENT_PATH;

        // create a folder for message if needed
        $this->prepareFolderPath($folderPath);

        // try to store message to filesystem
        return $this->storeFile(
            $binaryData,
            $this->getMailLocalFilePath(
                $mail,
                DIRECTORY_SEPARATOR .
                    self::ATTACHMENT_PATH .
                    $attachment->getFilePath()
            )
        );
    }

    /**
     * Save all attachments of a given mail
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

    /**
     * TODO
     * 
     * @param $data
     * @param $filePath
     * @return bool
     * @throws \Exception
     */
    protected function storeFile($data, $filePath)
    {
        // create a folder for message if needed
        if(!is_dir(dirname($filePath)))
        {
            $this->createFolder(dirname($filePath));
        }

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

    /**
     * Load binary file data from a given file path
     * 
     * @param $filePath
     * @return null|string
     */
    private function restoreFile($filePath)
    {
        try {
            for ($i = 0; $i < $this->_retryLimit; ++$i) {
                /* We try an exclusive creation of the file. This is an atomic operation, it avoid locking mechanism */
                @fopen($filePath, 'x');

                if (false === $fileData = file_get_contents ($filePath)) {
                    return null;
                }
                return $fileData;
            }
        } catch(\Exception $e) {
            return null;
        }
    }

    private function createFolder($folderPath)
    {
        return mkdir(
            $folderPath,
            0777,
            true
        );
    }
    
    protected function prepareFolderPath($folderPath)
    {
        // create a folder for message if needed
        if (!is_dir($folderPath)) {
            $this->createFolder($folderPath);
        }
    }

    /**
     * @param \Shockwavemk\Mail\Base\Model\Mail $mail
     * @return string
     */
    protected function getMailFolderPath($mail)
    {
        // store message in temporary file system spooler
        $dropboxHostTempFolderPath = $this->_dropboxStorageConfig
            ->getDropboxHostTempFolderPath();

        $folderPath = $dropboxHostTempFolderPath . $mail->getId();
        return $folderPath;
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
                $shortFilePath = str_replace($spoolFolder, '', $object->getPathName());

                $files[] = [
                    'name' => $object->getFilename(),
                    'localPath' => $object->getPathName(),
                    'remotePath' => $shortFilePath,
                    'modified' => $object->getMTime()
                ];
            }
        }

        return $files;
    }

    public function getCacheLimit()
    {
        return $this->_dropboxStorageConfig->getLocalStoreageLimit();
    }

    public function getUploadLimit()
    {
        return $this->_dropboxStorageConfig->getUploadLimit();
    }

    /**
     * @param \Shockwavemk\Mail\Base\Model\Mail $mail
     * @param $path
     * @return string
     */
    protected function getMailLocalFilePath($mail, $path)
    {
        $dropboxHostTempFolderPath = $this->_dropboxStorageConfig
            ->getDropboxHostTempFolderPath();

        $folderPath = $dropboxHostTempFolderPath .
            $mail->getId();

        $filePath = $folderPath . $path;
        return $filePath;
    }

    /**
     * @param \Shockwavemk\Mail\Base\Model\Mail $mail
     * @param $path
     * @return string
     */
    protected function getMailRemoteFilePath($mail, $path)
    {
        $attachmentFolder =
            DIRECTORY_SEPARATOR .
            $mail->getId();

        $remoteFilePath = $attachmentFolder . $path;
        return $remoteFilePath;
    }

    /**
     * @param $localFilePath
     * @param $remoteFilePath
     * @return array|null
     * @throws dbx\Exception_BadResponseCode
     * @throws dbx\Exception_OverQuota
     * @throws dbx\Exception_RetryLater
     * @throws dbx\Exception_ServerError
     */
    protected function restoreRemoteFileToLocalFile($localFilePath, $remoteFilePath)
    {
        // create a folder for message if needed
        if(!is_dir(dirname($localFilePath)))
        {
            $this->createFolder(dirname($localFilePath));
        }

        $handle = fopen($localFilePath, "w+b");
        $result = $this->getDropboxClient()->getFile($remoteFilePath, $handle);
        fclose($handle);

        return $result;
    }

    /**
     * @param $localFilePath
     * @param $remoteFilePath
     * @return array|null
     * @throws dbx\Exception_BadResponseCode
     * @throws dbx\Exception_OverQuota
     * @throws dbx\Exception_RetryLater
     * @throws dbx\Exception_ServerError
     */
    public function storeLocalFileToRemoteFile($localFilePath, $remoteFilePath)
    {
        $handle = fopen($localFilePath, "rb");
        $result = $this->getDropboxClient()->uploadFile($remoteFilePath, dbx\WriteMode::force(), $handle);
        fclose($handle);

        return $result;
    }

    public function getLocalFileListForPath($localPath)
    {
        $files = [];
        $objects = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($localPath),
            RecursiveIteratorIterator::LEAVES_ONLY,
            FilesystemIterator::SKIP_DOTS
        );

        foreach($objects as $path => $object) {
            if($object->getFilename() != '.' && $object->getFilename() != '..')
            {
                $remoteFilePath = str_replace($this->_dropboxHostTempFolderPath, '/', $object->getPathName());

                $file = [
                    'name' => $object->getFilename(),
                    'localPath' => $object->getPathName(),
                    'remotePath' => $remoteFilePath,
                    'modified' => $object->getMTime()
                ];
                $files[$object->getFilename()] = $file;
            }
        }

        return $files;
    }

    public function deleteLocalFiles($localPath)
    {
        $it = new RecursiveDirectoryIterator(
            $localPath,
            RecursiveDirectoryIterator::SKIP_DOTS
        );

        $files = new RecursiveIteratorIterator(
            $it,
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($localPath);
    }

    public function getCronLimit()
    {
        return $this->_dropboxStorageConfig->getCronLimit();
    }
}
