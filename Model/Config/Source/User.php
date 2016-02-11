<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavedesign\Mail\Dropbox\Model\Config\Source;

class User implements \Magento\Framework\Option\ArrayInterface
{
    protected $config;

    public function __construct(
        \Shockwavedesign\Mail\Dropbox\Model\Config $config
    )
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        if(empty($this->config->getDropboxUsers()))
        {
            return [
                ['label' => __('No user saved'), 'value' => '0']
            ];
        }

        $selection = array();
        foreach ($this->config->getDropboxUsers() as $dropboxUser)
        {
            $selection[] = ['label' => __($dropboxUser['display_name']), 'value' => $dropboxUser['entity_id']];
        }

        return $selection;
    }
}
