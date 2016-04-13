<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavedesign\Mail\Dropbox\Model\Cron;

use \Dropbox as dbx;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Store\Model\ScopeInterface;
use Shockwavedesign\Mail\Dropbox\Model\Storeages\DropboxStoreage;

class Uploader
{
    /** @var DropboxStoreage $_dropboxStoreage */
    protected $_dropboxStoreage;

    protected $_manager;

    /** @var bool */
    protected $_debugMode;

    /**
     * TODO
     * @param DropboxStoreage $dropboxStoreage
     * @param \Magento\Framework\ObjectManagerInterface $manager
     */
    public function __construct(
        DropboxStoreage $dropboxStoreage,
        \Magento\Framework\ObjectManagerInterface $manager
    ) {
        $this->_manager = $manager;
        $this->_dropboxStoreage = $dropboxStoreage;
        $this->_debugMode = true;
    }

    /**
     * Refresh sales tax report statistics for last day
     *
     * @return $this
     */
    public function invoke()
    {
        // get the list of current temporary stored mail on server
        $rootLocalFolderList = $this->_dropboxStoreage->getRootLocalFolderFileList();

        usort($rootLocalFolderList, function($a, $b) {
            return $a['modified'] <=> $b['modified'];
        });

        // get config how many should be moved in one step

        $cacheLimit = $this->_dropboxStoreage->getCacheLimit();
        $uploadLimit = $this->_dropboxStoreage->getUploadLimit();
        $cronLimit = $this->_dropboxStoreage->getCronLimit();

        $localFolderListSize = count($rootLocalFolderList);
        $uploadRequestCount = 0;

        foreach($rootLocalFolderList as $index => $folder) {

            if($this->_debugMode) {
                echo 'Process folder [' . $folder['localPath'] . ']' . "\n";
            }

            if($index > $cronLimit + $cacheLimit) {
                if($this->_debugMode) {
                    echo 'Limit reached "' . ($cronLimit + $cacheLimit) . '""' . "\n";
                }

                break;
            }

            $localFiles = $this->_dropboxStoreage->getLocalFileListForPath(
                $folder['localPath']
            );

            $isFolderStored = true;

            foreach($localFiles as $localFile) {
                $metaData = $this->_dropboxStoreage->getDropboxClient()->getMetadata(
                    $localFile['remotePath']
                );

                if(is_null($metaData)) {
                    if($this->_debugMode) {
                        echo 'No metaData for file [' . $localFile['remotePath'] . '] / [' . $localFile['remotePath'] . ']' . "\n";
                    }

                    $isFolderStored = false;

                    if($uploadRequestCount < $uploadLimit) {

                        $uploadRequestCount++;
                        $uploadResult = $this->_dropboxStoreage->storeLocalFileToRemoteFile(
                            $localFile['localPath'],
                            $localFile['remotePath']
                        );
                    }
                }
            }

            // delete local file if index < size - cachelimit
            $canBeDeleted = $index < ($localFolderListSize - $cacheLimit) && $isFolderStored;
            if($canBeDeleted === true) {
                if($this->_debugMode) {
                    echo 'Delete local files recursive [' . $folder['localPath'] . ']' . "\n";
                }

                $this->_dropboxStoreage->deleteLocalFiles(
                    $folder['localPath']
                );
            }
        }

        // get the config in which folder data should be uploaded

        // for each file, limited by config limit

        // open file, upload file

        return $this;
    }

    protected function legacy()
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
