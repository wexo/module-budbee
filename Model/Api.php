<?php

namespace Wexo\Budbee\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Shipment;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Api
{
    const API_URL_PROD = 'https://api.budbee.com';
    const API_URL_STAGING = 'https://api.staging.budbee.com';
    const API_URI_BOXES = 'boxes';
    const API_URI_POSTCODE = 'postalcodes/validate';
    const API_URI_INTERVALS = 'intervals';
    const API_URI_MULTIPLE_ORDERS = 'multiple/orders';
    const API_URI_PARCELS = 'parcels';
    const API_URI_TRACKING = 'tracking-url';

    const HEADER_POSTCODE_VALIDATE = 'application/vnd.budbee.postalcodes-v2+json';
    const HEADER_DELIVERY_INTERVAL = 'application/vnd.budbee.intervals-v2+json';
    const HEADER_BOXES_ALL = 'application/vnd.budbee.boxes-v1+json';
    const HEADER_ORDER_CREATE = 'application/vnd.budbee.multiple.orders-v2+json';
    const HEADER_ORDER_PARCEL_TRACKINGURL = 'application/vnd.budbee.parcels-v1+json';

    /**
     * The url to the budbee api
     *
     * @var string
     */
    private string $apiUrl;

    /**
     * Api constructor.
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     * @param Config $config
     * @param DateTimeFactory $dateTimeFactory
     * @param TimezoneInterface $timezone
     * @param Request $request
     */
    public function __construct(
        private readonly Json                  $jsonSerializer,
        private readonly LoggerInterface       $logger,
        private readonly Config                $config,
        private readonly DateTimeFactory       $dateTimeFactory,
        private readonly TimezoneInterface     $timezone,
        private readonly Request               $request
    ) {
        $this->apiUrl = $this->config->getIsProductionMode() ? self::API_URL_PROD : self::API_URL_STAGING;
    }

    /**
     * Validates the postcode and country the order is being created for
     *
     * @param $country_id
     * @param $zip
     * @return bool
     */
    public function getIsPostcodeValidated($country_id, $zip): bool
    {
        $requestUrl = implode(
            '/',
            [
                $this->apiUrl,
                self::API_URI_POSTCODE,
                $country_id,
                $zip
            ]
        );

        try {
            $this->request->makeRequest($requestUrl, self::HEADER_POSTCODE_VALIDATE);
        } catch (LocalizedException) {
            return false;
        }

        return true;
    }

    /**
     * Retrieves a set of possible delivery times for the input days. It goes from current date and to the amount set
     *
     * @param $country_id
     * @param $zip
     * @param int $daysAmount
     * @return array
     * @throws LocalizedException
     */
    public function getNextDeliveryWindows($country_id, $zip, int $daysAmount = 7): array
    {
        $requestUrl = implode(
            '/',
            [
                $this->apiUrl,
                self::API_URI_INTERVALS,
                $country_id,
                $zip,
                $this->getDateInterval($daysAmount)
            ]
        );

        $result = $this->request->makeRequest($requestUrl, self::HEADER_DELIVERY_INTERVAL);

        if (isset($result['status_code']) && $result['status_code'] !== 200) {
            return [];
        }

        $deliveryWindows = $this->jsonSerializer->unserialize($result['body']);

        foreach ($deliveryWindows as $key => $window) {
            foreach ($window['delivery'] as $type => $unixMilis) {
                if ($unixMilis <= 0) {
                    continue;
                }

                $deliveryWindows[$key]['delivery'][$type] = $this->timezone->date(
                    $unixMilis / 1000
                )->format('H:i');
            }
            $deliveryWindows[$key]['delivery']['date'] = $this->timezone->date(
                $window['delivery']['start'] / 1000
            )->format('d. M, Y');
        }

        return $deliveryWindows;
    }

    /**
     * Returns an interval of dates from current date to date set in amount
     *
     * @param int $daysAmount
     * @return string
     */
    private function getDateInterval(int $daysAmount): string
    {
        $dateTime = $this->dateTimeFactory->create();
        $currentDateTime = $this->timezone->date()->format('Y-m-d');

        $futureDateTime = $dateTime->date('Y-m-d', strtotime(
            $currentDateTime . ' + ' .$daysAmount. ' days')
        );

        return implode(
            '/',
            [
                $currentDateTime,
                $futureDateTime
            ]
        );
    }

    /**
     * Finds all available lockers in provided zip and country
     *
     * @param $zip
     * @param $countryCode
     * @return array
     * @throws LocalizedException
     */
    public function getAvailableLockers($zip, $countryCode): array
    {
        $requestUrl = implode(
            '/',
            [
                $this->apiUrl,
                self::API_URI_BOXES,
                self::API_URI_POSTCODE,
                $countryCode,
                $zip
            ]
        );

        $result = $this->request->makeRequest($requestUrl, self::HEADER_BOXES_ALL);

        if ($result['status_code'] !== 200) {
            return [];
        }

        return $this->jsonSerializer->unserialize($result['body'])['lockers'] ?? [];
    }

    /**
     * Creates an order in Budbee for box delivery
     * https://developer.budbee.com/#Create-Box-Delivery-Order
     *
     * @param OrderInterface $order
     * @param Shipment $shipment
     * @return string
     * @throws LocalizedException
     */
    public function createBoxDeliveryOrder(OrderInterface $order, Shipment $shipment): string
    {
        $shippingData = $this->jsonSerializer->unserialize($order->getData('wexo_shipping_data'));
        $budbee = isset($shippingData['parcelShop']) ? $shippingData['parcelShop'] : false;
        if (!$budbee) {
            $this->logger->error(
                __METHOD__.' :: No Budbee parcelshop data',
                [
                    'shipping_data' => $shippingData
                ]
            );
            throw new LocalizedException(__('No Budbee Object found on Order, check the logs for more details'));
        }
        $orderItems = [];
        foreach ($order->getItems() as $orderItem) {
            $orderItems[] = [
                'name' => $orderItem->getName(),
                'reference' => $orderItem->getSku(),
                'quantity' => (int) $orderItem->getQtyOrdered(),
                'unitPrice' => (int) round($orderItem->getPrice() * 100),
                'currency' => $order->getOrderCurrencyCode()
            ];
        }
        $billingAddress = $order->getBillingAddress();
        $body = [
            'collectionId' => (int) $this->config->getCollectionId(),
            'cart' => [
                'cartId' => $order->getIncrementId(),
                'articles' => $orderItems
            ],
            'delivery' => [
                'name' => $billingAddress->getFirstname(),
                'telephoneNumber' => $billingAddress->getTelephone(),
                'email' => $billingAddress->getEmail(),
                'address' => [
                    'street' => array_first($billingAddress->getStreet()),
                    'postalCode' => $billingAddress->getPostcode(),
                    'city' => $billingAddress->getCity(),
                    'country' => $billingAddress->getCountryId()
                ]
            ],
            'productCodes' => [
                "DLVBOX"
            ],
            'boxDelivery' => [
                'selectedBox' => (string) $budbee['number']
            ],
            'parcels' => [
                [
                    'shipmentId' => $shipment->getIncrementId(),
                    'packageId' => ''
                ]
            ],
            'additionalServices' => [
                'fraudDetection' => false
            ]
        ];

        $requestUrl = implode(
            '/',
            [
                $this->apiUrl,
                self::API_URI_MULTIPLE_ORDERS
            ]
        );

        $response = $this->request->makeRequest($requestUrl, self::HEADER_ORDER_CREATE, 'POST', $body);

        return $response['body'];
    }

    /**
     * Creates an order in Budbee for home delivery
     * https://developer.budbee.com/#Create-Box-Delivery-Order
     *
     * @param OrderInterface $order
     * @return string
     * @throws LocalizedException
     */
    public function createHomeDeliveryOrder(OrderInterface $order): string
    {
        $billingAddress = $order->getBillingAddress();

        if (!$this->getIsPostcodeValidated($billingAddress->getCountryId(), $billingAddress->getPostcode())) {
            throw new LocalizedException(__(__FUNCTION__ . ' - The postcode could not be validated'));
        }

        $orderItems = [];
        foreach ($order->getItems() as $orderItem) {
            $orderItems[] = [
                'name' => $orderItem->getName(),
                'reference' => $orderItem->getSku(),
                'quantity' => (int) $orderItem->getQtyOrdered(),
                'unitPrice' => (int) round($orderItem->getPrice() * 100),
                'currency' => $order->getOrderCurrencyCode()
            ];
        }

        $body = [
            'collectionId' => (int) $this->config->getCollectionId(),
            'cart' => [
                'cartId' => $order->getIncrementId(),
                'articles' => $orderItems
            ],
            'delivery' => [
                'name' => $billingAddress->getFirstname(),
                'telephoneNumber' => $billingAddress->getTelephone(),
                'email' => $billingAddress->getEmail(),
                'address' => [
                    'street' => array_first($billingAddress->getStreet()),
                    'postalCode' => $billingAddress->getPostcode(),
                    'city' => $billingAddress->getCity(),
                    'country' => $billingAddress->getCountryId()
                ],
                'outsideDoor' => true
            ],
            'requireSignature' => false
        ];

        $requestUrl = implode(
            '/',
            [
                $this->apiUrl,
                self::API_URI_MULTIPLE_ORDERS
            ]
        );

        $response = $this->request->makeRequest($requestUrl, self::HEADER_ORDER_CREATE, 'POST', $body);

        return $response['body'];
    }

    /**
     * Appends a parcel to a budbee home order
     *
     * @param string $budbeeOrderId
     * @param int $shipmentId
     * @return mixed|null
     * @throws LocalizedException
     */
    public function appendParcelToOrder(string $budbeeOrderId, int $shipmentId): mixed
    {
        $postBody = [
            [
                'shipmentId' => $shipmentId,
                'packageId' => '',
            ]
        ];

        $requestUrl = implode(
            '/',
            [
                $this->apiUrl,
                self::API_URI_MULTIPLE_ORDERS,
                $budbeeOrderId,
                self::API_URI_PARCELS
            ]
        );

        $response = $this->request->makeRequest($requestUrl, self::HEADER_ORDER_CREATE, 'POST', $postBody);

        $parcel = $this->jsonSerializer->unserialize($response['body']);

        return array_first($parcel);
    }

    /***
     * @param Shipment $shipment
     * @return false|mixed
     * @throws LocalizedException
     */
    public function getShipmentLabel(Shipment $shipment): mixed
    {
        $order = $shipment->getOrder();
        $shippingData = $this->jsonSerializer->unserialize($order->getData('wexo_shipping_data'));
        if (!isset($shippingData['budbee']['parcel'])) {
            throw new LocalizedException(__('No labels exist on this order. Have any shipments been mande?'));
        }
        foreach ($shippingData['budbee']['parcel'] as $parcel) {
            if ($parcel['shipmentId'] === $shipment->getIncrementId()) {
                return $parcel['labelUrl'];
            }
        }
        return false;
    }

    /**
     * Get budbee tracking url linked to parcel
     *
     * @param string $parcelId
     * @return string
     * @throws LocalizedException
     */
    public function getParcelTrackingUrl(string $parcelId): string
    {
        $requestUrl = implode(
            '/',
            [
                $this->apiUrl,
                self::API_URI_PARCELS,
                $parcelId,
                self::API_URI_TRACKING
            ]
        );

        $response = $this->request->makeRequest($requestUrl, self::HEADER_ORDER_PARCEL_TRACKINGURL);

        return $response['body'];
    }
}
