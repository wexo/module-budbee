<?php
namespace Wexo\Budbee\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order\Shipment;

class CreateOrder
{
    public function __construct(
        private readonly Json $json,
        private readonly Api $api
    ) {
    }

    /**
     * Create home delivery order in Budbee
     *
     * @param OrderInterface $order
     * @return array
     * @throws LocalizedException
     */
    public function home(OrderInterface $order): array
    {
        $orderData = $this->api->createHomeDeliveryOrder($order);
        $shippingData = $this->json->unserialize($order->getData('wexo_shipping_data'));
        $shippingData['budbee']['order'] = $this->json->unserialize($orderData);
        $order->setData('wexo_shipping_data', $this->json->serialize($shippingData));

        return $shippingData;
    }

    /**
     * Create box delivery order in Budbee
     *
     * @param OrderInterface $order
     * @param Shipment $shipment
     * @return array
     * @throws LocalizedException
     */
    public function box(OrderInterface $order, Shipment $shipment): array
    {
        $orderData = $this->api->createBoxDeliveryOrder($order, $shipment);
        $shippingData = $this->json->unserialize($order->getData('wexo_shipping_data'));
        $shippingData['budbee']['box'] = $this->json->unserialize($orderData);
        $order->setData('wexo_shipping_data', $this->json->serialize($shippingData));

        return $shippingData;
    }
}
