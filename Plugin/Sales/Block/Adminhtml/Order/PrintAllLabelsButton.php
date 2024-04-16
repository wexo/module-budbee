<?php

namespace Wexo\Budbee\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;
use Magento\Sales\Model\Order;

class PrintAllLabelsButton
{
    public function __construct(
        private readonly Json $json
    ) {
    }

    /**
     * @param OrderView $subject
     * @return void
     */
    public function beforeSetLayout(OrderView $subject): void
    {
        $order = $subject->getOrder();

        if (!in_array($order->getStatus(), [
            ORDER::STATE_COMPLETE,
            ORDER::STATE_PROCESSING,
            ORDER::STATE_CLOSED
        ])) {
            return;
        }

        try {
            $shippingData = $this->json->unserialize($order->getWexoShippingData());
            $parcel = $shippingData['budbee']['parcel'][0]['labelUrl'] ?? null;
            $isHomeDelivery = isset($shippingData['budbee']['order']['homeDelivery']);
        } catch (\InvalidArgumentException) {
            return;
        }

        if (!$parcel || !$isHomeDelivery) {
            return;
        }

        $subject->addButton(
            'order_print_shipment_labels',
            [
                'label' => __('Print Budbee Shipment Labels'),
                'class' => __('print-shipment-labels'),
                'id' => 'order-view-print-shipment-labels',
                'onclick' => 'setLocation(\'' .
                    $subject->getUrl('wexo_budbee/printLabel/printAllShipmentLabels') .
                    '\')'
            ]
        );
    }
}
