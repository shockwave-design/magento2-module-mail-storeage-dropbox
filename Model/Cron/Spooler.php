<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavemk\Mail\Base\Model;

use DirectoryIterator;
use \Dropbox as dbx;
use \Magento\Store\Model\ScopeInterface;

class Spooler
{
    /** @var \Shockwavemk\Mail\Base\Model\Simulation\Config $config */
    protected $_config;

    protected $_accessToken;

    protected $_dbxClient;

    /**
     * TODO
     */
    public function __construct(
        \Shockwavemk\Mail\Base\Model\Simulation\Config $config
    ) {

        $this->_accessToken = $this->_config->getValue('system/smtp/dropbox_user_id', ScopeInterface::SCOPE_STORE);
        $this->_dbxClient = new dbx\Client($this->_accessToken, "PHP-Example/1.0");
    }

    /**
     * Refresh sales tax report statistics for last day
     *
     * @return $this
     */
    public function invoke()
    {
        // TODO

        // get the list of current temporary stored mail on server

        // get config how many should be moved in one step

        // get the config in which folder data should be uploaded

        // for each file, limited by config limit

        // open file, upload file

        return $this;
    }

    /**
     * Execute a recovery if for any reason a process is sending for too long.
     *
     * @param int $timeout in second Defaults is for very slow smtp responses
     */
    public function recover($timeout = 900)
    {
        foreach (new DirectoryIterator($this->_path) as $file) {
            $file = $file->getRealPath();
            if (substr($file, -16) == '.message.sending') {
                $lockedtime = filectime($file);
                if ((time() - $lockedtime) > $timeout) {
                    rename($file, substr($file, 0, -8));
                }
            }
        }
    }
    /**
     * Sends messages using the given transport instance.
     *
     * @param $storeage        A storeage instance
     * @param string[]        $failedRecipients An array of failures by-reference
     *
     * @return int The number of sent e-mail's
     */
    public function flushQueue( $storeage, &$failedStores = null)
    {
        $directoryIterator = new DirectoryIterator($this->_path);
        /* Start the transport only if there are queued files to send */
        if (!$storeage->isStarted()) {
            foreach ($directoryIterator as $file) {
                if (substr($file->getRealPath(), -8) == '.message') {
                    $storeage->start();
                    break;
                }
            }
        }
        $failedRecipients = (array) $failedStores;
        $count = 0;
        $time = time();
        foreach ($directoryIterator as $file) {
            $file = $file->getRealPath();
            if (substr($file, -8) != '.message') {
                continue;
            }
            /* We try a rename, it's an atomic operation, and avoid locking the file */
            if (rename($file, $file.'.sending')) {
                $message = unserialize(file_get_contents($file.'.sending'));
                $count += $storeage->send($message, $failedRecipients);
                unlink($file.'.sending');
            } else {
                /* This message has just been catched by another process */
                continue;
            }
            if ($this->getMessageLimit() && $count >= $this->getMessageLimit()) {
                break;
            }
            if ($this->getTimeLimit() && (time() - $time) >= $this->getTimeLimit()) {
                break;
            }
        }
        return $count;
    }

    public function upload()
    {
        $f = fopen($path . "test.txt", "w+b");
        $fileMetadata = $this->_dbxClient->getFile("/test.txt", $f);
        fclose($f);
        print_r($fileMetadata);
    }
}
