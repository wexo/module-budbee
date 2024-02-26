<?php

namespace Wexo\Budbee\Plugin\Sales\Block\Adminhtml\Shipment;

use Magento\Shipping\Block\Adminhtml\View as ShippingView;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class PrintLabel
{
    /**
     * @var ShipmentRepositoryInterface
     */
    protected ShipmentRepositoryInterface $shipmentRepository;

    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository
    ) {
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * @param ShippingView $subject
     * @return void
     */
    public function beforeSetLayout(ShippingView $subject): void
    {
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
