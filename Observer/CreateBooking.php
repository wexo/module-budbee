<?php

namespace Wexo\Budbee\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order\Shipment;
use Wexo\Budbee\Model\Api;
use Wexo\Budbee\Model\AppendParcel;
use Wexo\Budbee\Model\CreateOrder;
use Wexo\Budbee\Model\Config;
use Wexo\Budbee\Model\AddTrackingUrl;

class CreateBooking implements ObserverInterface
{
    /**
     * @param Api $api
     * @param Json $json
     * @param CreateOrder $createOrder
     * @param Config $config
     * @param AddTrackingUrl $trackingUrl
     * @param AppendParcel $parcel
     */
    public function __construct(
        private readonly Api $api,
        private readonly Json $json,
        private readonly CreateOrder $createOrder,
        private readonly Config $config,
        private readonly AddTrackingUrl $trackingUrl,
        private readonly AppendParcel $parcel
    ) {
    }

    /**
     * Creates the order in Budbee and appends packages to home deliveries
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer): void
    {
        if (!$this->config->getIsEnabled()) {
            return;
        }

        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();

        if (str_contains($order->getShippingMethod(), 'budbee')) {
            $shippingData = $this->json->unserialize($order->getData('wexo_shipping_data'));

            if (str_contains($order->getShippingMethod(), 'budbee_budbeebox')) {
                if (!isset($shippingData['budbee']['box'])) {
                    $shippingData = $this->createOrder->box($order, $shipment);
                } else {
                    throw new LocalizedException(__('Box delivery is already shipped'));
                }
                $shippingDataParcels = $shippingData['budbee']['box']['parcels'];
                $parcel = $shippingDataParcels[array_key_first($shippingDataParcels)];
                $trackingData = $this->json->unserialize($this->api->getParcelTrackingUrl($parcel['packageId']));

                $this->trackingUrl->addToShipment($shipment, $trackingData['url']);

                $shippingData['budbee']['parcel'][] = [
                    'labelUrl' => $parcel['label'],
                    'trackingUrl' => $trackingData['url'],
                    'shipmentId' => $parcel['shipmentId']
                ];
            }

            if (str_contains($order->getShippingMethod(), 'budbee_budbeehome')) {
                // If the budbee order is not found on the Magento order, create it.
                if(!isset($shippingData['budbee']['order'])) {
                    $shippingData = $this->createOrder->home($order);
                }

                $parcel = $this->parcel->append($order, $shipment, $shippingData);

                $trackingData = $this->json->unserialize($this->api->getParcelTrackingUrl($parcel['packageId']));

                $this->trackingUrl->addToShipment($shipment, $trackingData['url']);

                $shippingData['budbee']['parcel'][] = [
                    'labelUrl' => $parcel['label'],
                    'trackingUrl' => $trackingData['url'],
                    'shipmentId' => $parcel['shipmentId']
                ];
            }

            $order->setData('wexo_shipping_data', $this->json->serialize($shippingData));
        }
    }
}
