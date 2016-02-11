<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavedesign\Mail\Dropbox\Model\Dropbox;

use Shockwavedesign\Mail\Dropbox\Model\ResourceModel\Dropbox\User as ResourceUser;
use Shockwavedesign\Mail\Dropbox\Model\ResourceModel\Dropbox\User\Collection;

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

/**
 * User model
 *
 * @method \Shockwavedesign\Mail\Dropbox\Model\ResourceModel\Dropbox\User _getResource()
 * @method \Shockwavedesign\Mail\Dropbox\Model\ResourceModel\Dropbox\User getResource()
 * @method string getAccessToken()
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class User extends \Magento\Framework\Model\AbstractModel
{
    public function __construct(
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ResourceUser $resource,
        Collection $resourceCollection,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\ObjectManagerInterface $manager,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }
}
