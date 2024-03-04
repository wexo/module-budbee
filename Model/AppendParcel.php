<?php
namespace Wexo\Budbee\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Shipment;

class AppendParcel
{
    /**
     * @param Api $api
     * @param Config $config
     */
    public function __construct(
        private readonly Api $api,
        private readonly Config $config
    ) {
    }

    /**
     * Appends a parcel to a budbee home delivery
     *
     * @param OrderInterface $order
     * @param Shipment $shipment
     * @param array $shippingData
     * @return array
     * @throws LocalizedException
     */
    public function append(OrderInterface $order, Shipment $shipment, array $shippingData): array
    {
        if (isset($shippingData['budbee']['parcel'])) {
            // Check if the shipmentId already exists on order, throw an error if it does.
            foreach ($shippingData['budbee']['parcel'] as $parcel) {
                if ($parcel['shipmentId'] === $shipment->getIncrementId()) {
                    throw new LocalizedException(
                        __('Failed to add the parcel to the order: The shipment already exists')
                    );
                }
            }

            if ($this->getIsLastPossibleParcel($shippingData)) {
                if ($this->remainingItemsFoundInOrder($order)) {
                    throw new LocalizedException(
                        __('This is the last possible shipment to a budbee home delivery. The rest of the order items must to be included')
                    );
                }
            }
        }

        return $this->api->appendParcelToOrder(
            $shippingData['budbee']['order']['id'],
            $shipment->getIncrementId()
        );
    }

    /**
     * Checks if this is the last possible parcel for budbee home delivery
     *
     * @param array $shippingData
     * @return bool
     */
    public function getIsLastPossibleParcel(array $shippingData): bool
    {
        return count($shippingData['budbee']['parcel']) === ($this->config->getMaxBudbeehomeDeliveries() - 1);
    }

    /**
     * Checks if there are any remaining items in the order
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function remainingItemsFoundInOrder(OrderInterface $order): bool
    {
        $remainingItems = array_filter($order->getItems(), function ($orderItem) {
            return $orderItem->getQtyShipped() < $orderItem->getQtyOrdered();
        });

        if (!empty($remainingItems)) {
            return true;
        }

        return false;
    }
}
