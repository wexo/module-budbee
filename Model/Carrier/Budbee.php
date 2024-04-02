<?php

namespace Wexo\Budbee\Model\Carrier;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Asset\Repository;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Wexo\Budbee\Api\Carrier\BudbeeInterface;
use Wexo\Budbee\Model\Api;
use Wexo\Budbee\Model\Config;
use Wexo\Budbee\Model\Data\ParcelShop;
use Wexo\Shipping\Api\Carrier\MethodTypeHandlerInterface;
use Wexo\Shipping\Api\Data\RateInterface;
use Wexo\Shipping\Model\Carrier\AbstractCarrier;
use Wexo\Shipping\Model\RateManagement;
use Magento\Framework\Escaper;

class Budbee extends AbstractCarrier implements BudbeeInterface
{
    private const DELIVERY_TYPE_HOME = 1;
    private const DELIVERY_TYPE_BOX = 2;
    public $_code = self::TYPE_NAME;

    /**
     * @var Api
     */
    private $budbeeApi;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Json
     */
    private $json;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param RateManagement $rateManagement
     * @param MethodFactory $methodFactory
     * @param ResultFactory $resultFactory
     * @param Api $budbeeApi
     * @param Config $config
     * @param Repository $assetRepository
     * @param Json $json
     * @param StoreManagerInterface $storeManager
     * @param MethodTypeHandlerInterface|null $defaultMethodTypeHandler
     * @param Escaper $escaper
     * @param array $methodTypeHandlers
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface       $scopeConfig,
        ErrorFactory               $rateErrorFactory,
        LoggerInterface            $logger,
        RateManagement             $rateManagement,
        MethodFactory              $methodFactory,
        ResultFactory              $resultFactory,
        Api                        $budbeeApi,
        Config                     $config,
        Repository                 $assetRepository,
        Json                       $json,
        StoreManagerInterface      $storeManager,
        private readonly Escaper   $escaper,
        MethodTypeHandlerInterface $defaultMethodTypeHandler = null,
        array                      $methodTypeHandlers = [],
        array                      $data = []
    ) {
        $this->budbeeApi = $budbeeApi;
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $rateManagement,
            $methodFactory,
            $resultFactory,
            $assetRepository,
            $storeManager,
            $defaultMethodTypeHandler,
            $methodTypeHandlers,
            $data
        );
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->json = $json;
    }

    /**
     * Type name that links to the Rate model
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return static::TYPE_NAME;
    }

    /**
     * @param RateRequest $request
     * @return bool|Result|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function collectRates(RateRequest $request): Result|bool|null
    {
        if (!$this->config->getIsEnabled()) {
            return false;
        }

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->resultFactory->create();
        $rates = $this->rateManagement->getRates($this, true);
        $items = $request->getAllItems();
        if (empty($items)) {
            return $result;
        }

        /** @var Quote $quote */
        $quote = reset($items)->getQuote();

        /** @var RateInterface $rate */
        foreach ($rates as $rate) {
            if ($rate->getConditions() && !$rate->getConditions()->validate($quote->getShippingAddress())) {
                continue;
            }

            if (!$this->config->getAllowAllCountries() &&
                !in_array(
                    $this->config->getStoreCountry(),
                    explode(',',$this->config->getAllowSpecificCountries())
                )
            ) {
                continue;
            }

            $storeId = $this->storeManager->getStore()->getId();
            if ($rate->getStoreId() && !in_array($storeId, explode(',', $rate->getStoreId()))) {
                continue;
            }

            if ($rate->getCustomerGroups()
                && !in_array($quote->getCustomerGroupId(), explode(',', $rate->getCustomerGroups()))) {
                continue;
            }

            if ($rate->getMethodType() === 'budbeehome') {
                if (!in_array(self::DELIVERY_TYPE_HOME, explode(',', $this->config->getDeliveryTypes()))) {
                    continue;
                }
                $requestData = $request->getData();
                if (!$this->budbeeApi->getIsPostcodeValidated($requestData['dest_country_id'], $requestData['dest_postcode'])) {
                    continue;
                }

                $deliveryWindows = $this->budbeeApi->getNextDeliveryWindows(
                    $requestData['dest_country_id'],
                    $requestData['dest_postcode']
                );

                if (!$deliveryWindows) {
                    continue;
                }

                foreach ($deliveryWindows as $key => $interval) {
                    if ($key >= 1) {
                        continue;
                    }
                    $method = $this->methodFactory->create();
                    $method->setData('carrier', $this->_code);
                    $method->setData('carrier_title', $this->getTitle());
                    $method->setData('method', $this->makeMethodCode($rate) . '-' . $key);
                    $method->setData('method_title', $this->getHomeMethodTitle($interval));
                    $method->setPrice(
                        $request->getFreeShipping() && $rate->getAllowFree() ? 0 : $rate->getPrice()
                    );

                    $result->append($method);
                }
                continue;
            }

            try {
                if (!in_array(self::DELIVERY_TYPE_BOX, explode(',', $this->config->getDeliveryTypes()))) {
                    continue;
                }
                $requestData = $request->getData();
                if (!$this->budbeeApi->getIsPostcodeValidated($requestData['dest_country_id'], $requestData['dest_postcode'])) {
                    continue;
                }

                $availableLockers = $this->budbeeApi->getAvailableLockers(
                    $requestData['dest_postcode'],
                    $requestData['dest_country_id']
                );

                if (!$availableLockers) {
                    continue;
                }

                $deliveryWindows = $this->budbeeApi->getNextDeliveryWindows(
                    $requestData['dest_country_id'],
                    $requestData['dest_postcode']
                );

                $deliveryWindowTitle = $deliveryWindows[array_key_first($deliveryWindows)] ?? [];
                /** @var Method $method */
                $method = $this->methodFactory->create();
                $method->setData('carrier', $this->_code);
                $method->setData('carrier_title', $this->getTitle());
                $method->setData('method', $this->makeMethodCode($rate));
                $method->setData('method_title', $rate->getTitle() . $this->getBoxMethodTitle($deliveryWindowTitle));
                $method->setPrice(
                    $request->getFreeShipping() && $rate->getAllowFree() ? 0 : $rate->getPrice()
                );
                $result->append($method);
            } catch (\Exception $exception) {
                $this->_logger->error(
                    'Budbee CollectRates Exception Occured',
                    [
                        'message' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString()
                    ]
                );
            }
        }

        return $result;
    }

    /**
     * Get the method title for budbee home deliveries
     *
     * @param array $interval
     * @return string
     */
    public function getHomeMethodTitle(array $interval): string
    {
        $homeTitle = $this->config->getBudbeehomePrependTitle() . PHP_EOL;
        if ($this->config->getIsIntervalDynamicHome()) {
            $homeTitle .= $interval['delivery']['date'] . PHP_EOL;
            $homeTitle .= $this->escaper->escapeHtml(__('in the period')) . PHP_EOL;
            $homeTitle .= $interval['delivery']['start'] . '-' . $interval['delivery']['stop'];
        } else {
            $homeTitle .= $this->config->getStaticIntervalHome();
        }
        return $homeTitle;
    }

    /**
     * Get the method title for budbee box deliveries
     *
     * @param array $interval
     * @return string
     */
    public function getBoxMethodTitle(array $interval): string
    {
        $boxTitle = PHP_EOL;
        if (!$interval) {
            return $boxTitle;
        }
        if ($this->config->getIsIntervalDynamicBox()) {
            $boxTitle .= $interval['delivery']['date'] . PHP_EOL;
            $boxTitle .= $this->escaper->escapeHtml(__('in the period')) . PHP_EOL;
            $boxTitle .= $interval['delivery']['start'] . '-' . $interval['delivery']['stop'];
        } else {
            $boxTitle .= $this->config->getStaticIntervalBox();
        }
        return $boxTitle;
    }

    /**
     * @inheritDoc
     */
    public function getAvailableBoxes(
        string $zip,
        string $country_code,
    ): array
    {
        if (empty($zip)) {
            return [];
        }
        try {
            $availableBoxes = $this->budbeeApi->getAvailableLockers(
                $zip,
                $country_code,
            );
        } catch (Exception $e) {
            return [];
        }

        if (empty($availableBoxes) || !$availableBoxes) {
            return [];
        }

        return array_map(function($box) {
            $openingHours = [];
            foreach ($box['openingHours']['periods'] as $openHour) {
                if (isset($openHour['open']) && isset($openHour['close'])) {
                    $opensAt = new \DateTime($openHour['open']['time']);
                    $closesAt = new \DateTime($openHour['close']['time']);
                    $openingHours[] = [
                        'opens_at' => $opensAt->format('H:i:s'),
                        'closes_at' => $closesAt->format('H:i:s'),
                        'day' => $this->escaper->escapeHtml(__($openHour['open']['day']))
                    ];
                }
            }
            $parcelShopObject = new ParcelShop();
            $parcelShopObject
                ->setNumber($box['id'])
                ->setCity($box['address']['city'])
                ->setCompanyName($box['name'])
                ->setCountryCode($box['address']['country'])
                ->setStreetName($box['address']['street'])
                ->setZipCode($box['address']['postalCode'])
                ->setLatitude($box['address']['coordinate']['latitude'])
                ->setLongitude($box['address']['coordinate']['longitude'])
                ->setOpeningHours($this->json->serialize($openingHours));

            return $parcelShopObject;
        }, $availableBoxes);
    }


    /**
     * @param ShippingMethodInterface $shippingMethod
     * @param Rate $rate
     * @param string|null $typeHandler
     * @return string
     * @throws LocalizedException
     */
    public function getImageUrl(ShippingMethodInterface $shippingMethod, Rate $rate, $typeHandler): string
    {
        return $this->assetRepository->createAsset('Wexo_Budbee::images/budbee.svg', [
            'area' => Area::AREA_FRONTEND
        ])->getUrl();
    }

    /**
     * @return true
     */
    public function isTrackingAvailable(): bool
    {
        return true;
    }
}
