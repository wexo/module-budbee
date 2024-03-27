<?php

namespace Wexo\Budbee\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Checks if the module is enabled
     *
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->scopeConfig->getValue(
            'carriers/budbee/active',
            ScopeInterface::SCOPE_STORE
        ) === '1';
    }

    /**
     * Returns the api key
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->scopeConfig->getValue(
            'carriers/budbee/api_key',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Returns the api secret
     *
     * @return string
     */
    public function getApiSecret(): string
    {
        return $this->scopeConfig->getValue(
            'carriers/budbee/api_secret',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get the collection id
     *
     * @return string
     */
    public function getCollectionId(): string
    {
        return $this->scopeConfig->getValue(
            'carriers/budbee/collection_id',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsProductionMode(): bool
    {
        return $this->scopeConfig->getValue(
            'carriers/budbee/production_mode',
            ScopeInterface::SCOPE_STORE
        ) == 1;
    }

    /**
     * Returns the title that is prepended to home deliveries method
     *
     * @return string
     */
    public function getBudbeehomePrependTitle(): string
    {
        return trim(
                $this->scopeConfig->getValue(
                    'carriers/budbee/budbeehome_prepend_title',
                    ScopeInterface::SCOPE_STORE
                )
            ) . ' ' ?? '';
    }

    /**
     * The maximum amount of allowed parcels
     *
     * @return int
     */
    public function getMaxBudbeehomeDeliveries(): int
    {
        return 5;
    }


    /**
     * Returns the store name
     *
     * @return string
     */
    public function getStoreName(): string
    {
        return $this->scopeConfig->getValue(
                'general/store_information/name',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
    }

    /**
     * Returns the store country
     *
     * @return string
     */
    public function getStoreCountry(): string
    {
        return $this->scopeConfig->getValue(
                'general/store_information/country_id',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
    }

    /**
     * Returns the possible delivery types
     *
     * @return string
     */
    public function getDeliveryTypes(): string
    {
        return $this->scopeConfig->getValue(
            'carriers/budbee/delivery_types',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    /**
     * Checks if all countries are allowed
     *
     * @return bool
     */
    public function getAllowAllCountries(): bool
    {
        return $this->scopeConfig->getValue(
            'carriers/budbee/sallowspecific',
            ScopeInterface::SCOPE_STORE
        ) == 0;
    }

    /**
     * Returns the specifically allowed countries
     *
     * @return string
     */
    public function getAllowSpecificCountries(): string
    {
        return $this->scopeConfig->getValue(
            'carriers/budbee/specificcountry',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    /**
     * Checks if dynamic interval is set for home deliveries
     *
     * @return bool
     */
    public function getIsIntervalDynamicHome(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            'carriers/budbee/dynamic_interval_home',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Checks if static interval is set for home deliveries
     *
     * @return string
     */
    public function getStaticIntervalHome(): string
    {
        return (string) $this->scopeConfig->getValue(
            'carriers/budbee/static_interval_home',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Checks if dynamic interval is set for box deliveries
     *
     * @return bool
     */
    public function getIsIntervalDynamicBox(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            'carriers/budbee/dynamic_interval_box',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Checks if static interval is set for box deliveries
     *
     * @return string
     */
    public function getStaticIntervalBox(): string
    {
        return (string) $this->scopeConfig->getValue(
            'carriers/budbee/static_interval_box',
            ScopeInterface::SCOPE_STORE
        );
    }
}
