<?php

namespace Wexo\Budbee\Model\MethodType;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\ObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Throwable;
use Wexo\Budbee\Model\Api;
use Wexo\Shipping\Api\Carrier\MethodTypeHandlerInterface;
use Wexo\Shipping\Model\MethodType\AbstractParcelShop;

class Budbeehome extends AbstractParcelShop implements MethodTypeHandlerInterface
{
    /**
     * @var Json
     */
    protected Json $jsonSerializer;

    /**
     * @var DataObjectHelper
     */
    protected DataObjectHelper $dataObjectHelper;

    /**
     * @var ObjectFactory
     */
    protected ObjectFactory $objectFactory;

    /**
     * @var Api
     */
    protected Api $api;

    /**
     * @var null
     */
    protected $parcelShopClass;

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
        return __('Budbee Home');
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'budbeehome';
    }

    /**
     * @param CartInterface $quote
     * @param OrderInterface $order
     * @return true
     */
    public function saveOrderInformation(CartInterface $quote, OrderInterface $order): bool
    {
        return true;
    }
}
