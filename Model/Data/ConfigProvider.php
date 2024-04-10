<?php

namespace Wexo\Budbee\Model\Data;

use Magento\Checkout\Model\ConfigProviderInterface;
use Wexo\Budbee\Model\Config;

class ConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    public function getConfig()
    {
        $payload['budbee_config'] = [
            'box_interval_is_dynamic' => $this->config->getIsIntervalDynamicBox(),
            'home_interval_is_dynamic' => $this->config->getIsIntervalDynamicHome(),
            'home_interval_label_static' => $this->config->getStaticIntervalHome(),
        ];

        return $payload;
    }
}
