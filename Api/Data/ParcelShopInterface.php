<?php

namespace Wexo\Budbee\Api\Data;

interface ParcelShopInterface extends \Wexo\Shipping\Api\Data\ParcelShopInterface
{
    const NUMBER = 'number';
    const COMPANY_NAME = 'company_name';
    const STREET_NAME = 'streetname';
    const STREET_NAME2 = 'streetname2';
    const ZIP_CODE = 'zip_code';
    const CITY = 'city_name';
    const COUNTRY_CODE = 'country_code';
    const COUNTRY_CODE_ISO = 'country_code_iso3166a2';
    const TELEPHONE = 'telephone';
    const LONGITUDE = 'longitude';
    const LATITUDE = 'latitude';
    const OPENING_HOURS = 'opening_hours';
    const TIME_LABEL = 'time_label';

    /**
     * @return string
     */
    public function getTimeLabel(): string;

    /**
     * @param $string
     * @return \Wexo\Shipping\Api\Data\ParcelShopInterface
     */
    public function setTimeLabel($string): \Wexo\Shipping\Api\Data\ParcelShopInterface;
}
