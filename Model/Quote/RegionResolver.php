<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Quote;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;

/**
 * Turns the region an address arrived with into the row id Magento needs. A wire address carries a name
 * or an abbreviation; placing an order needs neither of those but the id behind them.
 */
class RegionResolver
{
    /**
     * @param RegionFactory $regionFactory
     * @param DirectoryHelper $directoryHelper
     */
    public function __construct(
        private readonly RegionFactory $regionFactory,
        private readonly DirectoryHelper $directoryHelper
    ) {
    }

    /**
     * @param string $countryId
     * @param string $region Name or abbreviation, as the address supplied it
     * @return int|null Null when the country has no such region, which the caller must report rather
     *                  than guess around
     */
    public function resolve(string $countryId, string $region): ?int
    {
        $region = trim($region);

        if ($countryId === '' || $region === '') {
            return null;
        }

        return $this->loadId($countryId, $region, byCode: true)
            ?? $this->loadId($countryId, $region, byCode: false);
    }

    /**
     * Only the countries the store lists have regions at all, so requiring one everywhere would make the
     * rest unbuyable.
     *
     * @param string $countryId
     * @return bool
     */
    public function isRequiredFor(string $countryId): bool
    {
        return $this->directoryHelper->isRegionRequired($countryId);
    }

    /**
     * A fresh model per attempt: loading twice into one instance leaves the first load's data behind.
     *
     * @param string $countryId
     * @param string $region
     * @param bool $byCode
     * @return int|null
     */
    private function loadId(string $countryId, string $region, bool $byCode): ?int
    {
        /** @var Region $model */
        $model = $this->regionFactory->create();

        if ($byCode) {
            $model->loadByCode($region, $countryId);
        } else {
            $model->loadByName($region, $countryId);
        }

        $id = $model->getId();

        return is_numeric($id) ? (int) $id : null;
    }
}
