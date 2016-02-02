<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavedesign\Mail\Dropbox\Model\Storeages;

class DropboxStoreage implements \Shockwavemk\Mail\Base\Model\Storeages\StoreageInterface
{
    /**
     * @var \Shockwavemk\Mail\Base\Model\Storeages\StoreageInterface
     */
    protected $_storage;

    /**
     * @param \Shockwavemk\Mail\Base\Model\Config $config
     * @param \Magento\Framework\Mail\MessageInterface $message
     * @param \Magento\Framework\ObjectManagerInterface $manager
     * @throws \Magento\Framework\Exception\MailException
     * @internal param null $parameters
     */
    public function __construct(
        \Shockwavemk\Mail\Base\Model\Config $config,
        \Magento\Framework\Mail\MessageInterface $message,
        \Magento\Framework\ObjectManagerInterface $manager
    )
    {

    }

    /**
     * Send a mail using this transport
     *
     * @return $id
     * @throws \Magento\Framework\Exception\MailException
     */
    public function saveMessage()
    {
        // TODO
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
}
