<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavemk\Mail\Base\Model;

use \Dropbox as dbx;

class UploadEmailStoreageData
{
    /**
     * TODO
     */
    public function __construct(
    ) {

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

        $dbxClient = new dbx\Client($accessToken, "PHP-Example/1.0");
        $accountInfo = $dbxClient->getAccountInfo();
        print_r($accountInfo);

        $f = fopen("working-draft.txt", "rb");
        $result = $dbxClient->uploadFile("/working-draft.txt", dbx\WriteMode::add(), $f);
        fclose($f);
        print_r($result);

        return $this;
    }
}
