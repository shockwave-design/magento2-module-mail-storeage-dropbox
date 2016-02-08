<?php
/**
 * Copyright 2016 Shockwave-Design - J. & M. Kramer, all rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Shockwavedesign\Mail\Dropbox\Model\Config\Source;

use Shockwavemk\Mail\Base\Model\Config;

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
                ['label' => __('Disabled'), 'value' => 'disabled']
            ];
        }

        $selection = array();
        foreach ($this->config->getDropboxUsers() as $transportType)
        {
            $selection[] = ['label' => __($transportType['label']), 'value' => $transportType['value']];
        }

        return $selection;
    }
}
