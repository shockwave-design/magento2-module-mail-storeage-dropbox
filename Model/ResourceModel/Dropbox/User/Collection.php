<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavedesign\Mail\Dropbox\Model\ResourceModel\Dropbox\User;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Shockwavedesign\Mail\Dropbox\Model\Dropbox\User', 'Shockwavedesign\Mail\Dropbox\Model\ResourceModel\Dropbox\User');
    }
}
