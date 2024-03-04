<?php

namespace Wexo\Budbee\Api\Carrier;

use Wexo\Budbee\Api\Data\ParcelShopInterface;
use Wexo\Shipping\Api\Carrier\CarrierInterface;

interface BudbeeInterface extends CarrierInterface
{
    const TYPE_NAME = 'budbee';

    /**
     * @param string $zip
     * @param string $country_code
     * @return ParcelShopInterface[]
     */
    public function getAvailableBoxes(
        string $zip,
        string $country_code,
    ): array;
}
