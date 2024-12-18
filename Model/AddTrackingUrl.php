<?php
namespace Wexo\Budbee\Model;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Sales\Model\OrderRepository;
use Wexo\Budbee\Api\Carrier\BudbeeInterface;
use Wexo\Budbee\Model\Carrier\Budbee;

class AddTrackingUrl
{
    /**
     * @param TrackFactory $trackFactory
     * @param ShipmentRepository $shipmentRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        private readonly TrackFactory $trackFactory,
        private readonly ShipmentRepository $shipmentRepository,
        private readonly OrderRepository $orderRepository
    ) {
    }

    /**
     * @param Shipment $shipment
     * @param string $trackingNumber
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function addToShipment(Shipment $shipment, string $trackingUrl): void
    {
        $order = $shipment->getOrder();
        $trackingNumber = $this->getTrackingNumberFromUrl($trackingUrl);
        try {
            $track = $this->trackFactory->create();
            $track->addData(
                [
                    'number' => $trackingNumber,
                    'carrier_code' => BudbeeInterface::TYPE_NAME,
                    'title' => BudbeeInterface::TYPE_NAME
                ]
            );
            $shipment->addTrack($track);
            $this->shipmentRepository->save($shipment);
            $this->orderRepository->save($order);
        } catch (Exception $e) {
            $order->addCommentToStatusHistory(
                'Adding Budbee tracking failed for shipment: ' . $trackingNumber
            )->setIsCustomerNotified(false);
            $this->orderRepository->save($order);

            throw new LocalizedException(
                __(
                    'Could not add Budbee tracking to shipment: ' .
                    $shipment->getIncrementId()
                )
            );
        }
    }

    /**
     * Grabs the tracking value from the url
     *
     * @param string $trackingUrl
     * @return string
     */
    public function getTrackingNumberFromUrl(string $trackingUrl): string
    {
        $parts = explode('/', rtrim($trackingUrl, '/'));
        return end($parts);
    }
}
