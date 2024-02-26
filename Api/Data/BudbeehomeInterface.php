<?php

namespace Wexo\Budbee\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface BudbeehomeInterface extends ExtensibleDataInterface
{
    const NUMBER = 'number';
    const DESCRIPTION = 'description';
    const CUTOFF_DATETIME_UTC = 'cutoff_datetime_utc';
    const DATETIME_UTC = 'datetime_utc';
    const EARLIEST_POSSIBLE_DELIVERY = 'earliest_possible_delivery';
    const LAST_POSSIBLE_DELIVERY = 'last_possible_delivery';
    const DATETIME_LOCAL = 'datetime_local';
    const TEXT_LOCAL = 'text_local';

    /**
     * @return string|null
     */
    public function getNumber();

    /**
     * @param string $string
     * @return BudbeehomeInterface
     */
    public function setNumber(string $string): BudbeehomeInterface;

    /**
     * @return string|null
     */
    public function getDescription();

    /**
     * @param string $string
     * @return BudbeehomeInterface
     */
    public function setDescription(string $string): BudbeehomeInterface;

    /**
     * @return string|null
     */
    public function getCutoffDatetimeUtc();

    /**
     * @param string $string
     * @return BudbeehomeInterface
     */
    public function setCutoffDatetimeUtc(string $string): BudbeehomeInterface;

    /**
     * @return string|null
     */
    public function getDatetimeUtc();

    /**
     * @param string $string
     * @return BudbeehomeInterface
     */
    public function setDatetimeUtc(string $string): BudbeehomeInterface;

    /**
     * @return string|null
     */
    public function getEarliestPossibleDelivery();

    /**
     * @param string $string
     * @return BudbeehomeInterface
     */
    public function setEarliestPossibleDelivery(string $string): BudbeehomeInterface;

    /**
     * @return string|null
     */
    public function getLastPossibleDelivery();

    /**
     * @param string $string
     * @return BudbeehomeInterface
     */
    public function setLastPossibleDelivery(string $string): BudbeehomeInterface;

    /**
     * @return string|null
     */
    public function getDatetimeLocal();

    /**
     * @param string $string
     * @return BudbeehomeInterface
     */
    public function setDatetimeLocal(string $string): BudbeehomeInterface;

    /**
     * @return string|null
     */
    public function getTextLocal();

    /**
     * @param string $string
     * @return BudbeehomeInterface
     */
    public function setTextLocal(string $string): BudbeehomeInterface;
}
