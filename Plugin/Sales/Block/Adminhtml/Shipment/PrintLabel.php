<?php

namespace Wexo\Budbee\Plugin\Sales\Block\Adminhtml\Shipment;

use Magento\Shipping\Block\Adminhtml\View as ShippingView;
use Wexo\Budbee\Model\Data\GetBudbeeDataFromOrder;

class PrintLabel
{
    public function __construct(
        private readonly GetBudbeeDataFromOrder $budbeeDataFromOrder
    ) {
    }

    /**
     * @param ShippingView $subject
     * @return void
     */
    public function beforeSetLayout(ShippingView $subject): void
    {
        if (!$this->budbeeDataFromOrder->get($subject)) {
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
