<?php

namespace Wexo\Budbee\Model\Data;

use Magento\Sales\Model\Order;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Shipping\Block\Adminhtml\View as ShippingView;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class GetBudbeeDataFromOrder
{
    public function __construct(
        private readonly Json $json
    ) {
    }

    /**
     * @param ShippingView|OrderView $order
     * @return array
     */
    public function get(ShippingView|OrderView $subject): array
    {
        $order = ($subject instanceof ShippingView)
            ? $subject->getShipment()->getOrder()
            : $subject->getOrder();

        if (!in_array($order->getStatus(), [
            ORDER::STATE_COMPLETE,
            ORDER::STATE_PROCESSING,
            ORDER::STATE_CLOSED
        ])) {
            return [];
        }

        try {
            $shippingData = $this->json->unserialize($order->getWexoShippingData());
            $parcel = $shippingData['budbee']['parcel'][0]['labelUrl'] ?? null;
            $isHomeDelivery = isset($shippingData['budbee']['order']['homeDelivery']);
        } catch (\InvalidArgumentException) {
            return [];
        }

        if ($parcel && $subject instanceof ShippingView || $isHomeDelivery) {
            return $shippingData;
        }

        return [];
    }
}
