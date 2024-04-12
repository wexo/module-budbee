<?php

namespace Wexo\Budbee\Plugin\Sales\Block\Adminhtml\Shipment;

use Magento\Sales\Model\Order;
use Magento\Shipping\Block\Adminhtml\View as ShippingView;
use Magento\Framework\Serialize\Serializer\Json;

class PrintLabel
{
    public function __construct(
        private readonly Json $json
    ) {
    }

    /**
     * @param ShippingView $subject
     * @return void
     */
    public function beforeSetLayout(ShippingView $subject): void
    {
        $order = $subject->getShipment()->getOrder();

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
        } catch (\Exception) {
            return;
        }

        if (!$parcel) {
            return;
        }

        $subject->addButton(
            'print_shipment_label',
            [
                'label' => __('Print Budbee Shipment Label'),
                'class' => __('print-shipment-label'),
                'id' => 'shipment-view-print-label',
                'onclick' => 'setLocation(\'' .
                    $subject->getUrl('wexo_budbee/printLabel/printShipmentLabel', [
                        'shipment_id' => $subject->getShipment()->getId(),
                        'come_from' => $subject->getRequest()->getParam('come_from')
                    ]) .
                    '\')'
            ]
        );
    }
}
