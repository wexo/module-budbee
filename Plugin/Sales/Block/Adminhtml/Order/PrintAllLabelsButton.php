<?php

namespace Wexo\Budbee\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;
use Wexo\Budbee\Model\Data\GetBudbeeDataFromOrder;

class PrintAllLabelsButton
{
    public function __construct(
        private readonly GetBudbeeDataFromOrder $budbeeDataFromOrder
    ) {
    }

    /**
     * @param OrderView $subject
     * @return void
     */
    public function beforeSetLayout(OrderView $subject): void
    {
        if (!$this->budbeeDataFromOrder->get($subject)) {
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
