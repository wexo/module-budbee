<?php

namespace Wexo\Budbee\Model\MethodType;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\ObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Wexo\Budbee\Api\Data\ParcelShopInterface;
use Wexo\Budbee\Model\Api;
use Wexo\Shipping\Api\Carrier\MethodTypeHandlerInterface;
use Wexo\Shipping\Model\MethodType\AbstractParcelShop;

class ParcelShop extends AbstractParcelShop implements MethodTypeHandlerInterface
{

    /**
     * @var Json
     */
    private Json $jsonSerializer;
    /**
     * @var DataObjectHelper
     */
    private DataObjectHelper $dataObjectHelper;
    /**
     * @var ObjectFactory
     */
    private ObjectFactory $objectFactory;
    /**
     * @var null
     */
    private $parcelShopClass;
    /**
     * @var Api
     */
    private Api $api;

    public function __construct(
        Json $jsonSerializer,
        DataObjectHelper $dataObjectHelper,
        ObjectFactory $objectFactory,
        Api $api,
        $parcelShopClass = null
    ) {
        parent::__construct(
            $jsonSerializer,
            $dataObjectHelper,
            $objectFactory,
            $parcelShopClass
        );
        $this->jsonSerializer = $jsonSerializer;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->objectFactory = $objectFactory;
        $this->parcelShopClass = $parcelShopClass;
        $this->api = $api;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return __('Budbee Box');
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'parcelshop';
    }

    /**
     * @param CartInterface $quote
     * @param OrderInterface $order
     * @throws LocalizedException
     */
    public function saveOrderInformation(CartInterface $quote, OrderInterface $order)
    {
        $shippingData = $this->jsonSerializer->unserialize($order->getData('wexo_shipping_data'));

        if (!isset($shippingData['parcelShop'])) {
            throw new LocalizedException(__('Service Point must be set!'));
        }

        /** @var ParcelShopInterface $parcelShop */
        $parcelShop = $this->objectFactory->create($this->parcelShopClass, []);
        $this->dataObjectHelper->populateWithArray($parcelShop, $shippingData['parcelShop'], $this->parcelShopClass);

        if (!$parcelShop->getNumber()) {
            throw new LocalizedException(__('Service Point number was not found!'));
        }
    }
}
