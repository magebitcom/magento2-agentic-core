<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Quote;

use Magebit\AgenticCore\Model\Quote\RegionResolver;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use PHPUnit\Framework\TestCase;

class RegionResolverTest extends TestCase
{
    /**
     * The wire carries a human-readable region, so the resolver has to accept the full name.
     *
     * @return void
     */
    public function testAFullRegionNameResolves(): void
    {
        $this->assertSame(12, $this->resolver(byName: ['US:California' => 12])->resolve('US', 'California'));
    }

    /**
     * The spec allows an abbreviation just as readily, and a code lookup is the cheaper of the two.
     *
     * @return void
     */
    public function testARegionCodeResolves(): void
    {
        $this->assertSame(12, $this->resolver(byCode: ['US:CA' => 12])->resolve('US', 'CA'));
    }

    /**
     * @return void
     */
    public function testTheCodeIsTriedBeforeTheName(): void
    {
        $resolver = $this->resolver(byCode: ['US:CA' => 12], byName: ['US:CA' => 99]);

        $this->assertSame(12, $resolver->resolve('US', 'CA'));
    }

    /**
     * Guessing an id for a region the country does not have would attach the address to the wrong
     * place, so an unknown region resolves to nothing and the caller reports it.
     *
     * @return void
     */
    public function testAnUnknownRegionResolvesToNothing(): void
    {
        $this->assertNull($this->resolver()->resolve('US', 'Atlantis'));
    }

    /**
     * @return void
     */
    public function testABlankRegionResolvesToNothing(): void
    {
        $this->assertNull($this->resolver(byName: ['US:' => 12])->resolve('US', '   '));
    }

    /**
     * Most countries have no regions at all, so demanding one would make them unbuyable.
     *
     * @return void
     */
    public function testOnlyConfiguredCountriesRequireARegion(): void
    {
        $resolver = $this->resolver(required: ['US']);

        $this->assertTrue($resolver->isRequiredFor('US'));
        $this->assertFalse($resolver->isRequiredFor('DE'));
    }

    /**
     * @param array<string, int> $byCode Keyed "country:code"
     * @param array<string, int> $byName Keyed "country:name"
     * @param string[] $required Countries the store requires a region for
     * @return RegionResolver
     */
    private function resolver(array $byCode = [], array $byName = [], array $required = []): RegionResolver
    {
        $factory = $this->createMock(RegionFactory::class);
        $factory->method('create')->willReturnCallback(
            function () use ($byCode, $byName): Region {
                $region = $this->getMockBuilder(Region::class)
                    ->disableOriginalConstructor()
                    ->onlyMethods(['loadByCode', 'loadByName', 'getId'])
                    ->getMock();

                $found = null;

                $region->method('loadByCode')->willReturnCallback(
                    function (string $code, string $countryId) use ($byCode, $region, &$found): Region {
                        $found = $byCode[$countryId . ':' . $code] ?? null;

                        return $region;
                    }
                );
                $region->method('loadByName')->willReturnCallback(
                    function (string $name, string $countryId) use ($byName, $region, &$found): Region {
                        $found = $byName[$countryId . ':' . $name] ?? null;

                        return $region;
                    }
                );
                // By reference: an arrow function would capture the null this starts as.
                $region->method('getId')->willReturnCallback(function () use (&$found): ?int {
                    return $found;
                });

                return $region;
            }
        );

        $directory = $this->createMock(DirectoryHelper::class);
        $directory->method('isRegionRequired')->willReturnCallback(
            static fn (string $countryId): bool => in_array($countryId, $required, true)
        );

        return new RegionResolver($factory, $directory);
    }
}
