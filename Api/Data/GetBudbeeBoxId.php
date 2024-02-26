<?php
namespace Wexo\Budbee\Api\Data;

use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Serialize\Serializer\Json;

class GetBudbeeBoxId
{
    /**
     * @param Request $request
     * @param OrderFactory $orderFactory
     * @param Json $json
     */
    public function __construct(
        private readonly Request $request,
        private readonly OrderFactory $orderFactory,
        private readonly Json $json
    ) {
    }

    /**
     * Get the id of the budbee box
     *
     * @return string
     */
    public function execute(): string
    {
        $incrementId = $this->request->getParam('orderincrementid');
        $budbeeOrderId = $this->request->getParam('budbeeorderid');

        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);

        if (!$order->getData('wexo_shipping_data')) {
            return 'No shipping data found on orderid';
        }

        $shippingData = $this->json->unserialize($order->getData('wexo_shipping_data'));

        if (!isset($shippingData['budbee']['box'])) {
            return 'No budbee shipping data was found on the order. Is the incrementid correct?';
        }

        if ($shippingData['budbee']['box']['id'] !== $budbeeOrderId) {
            return 'The provided budbeeorderid does not match the one found on the order.';
        }

        if (!isset($shippingData['parcelShop']['number'])) {
            return 'No parcelshop data was found on the order';
        }

        return $this->json->serialize(['boxid' => $shippingData['parcelShop']['number']]);
    }
}
