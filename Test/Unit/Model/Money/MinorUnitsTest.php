<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Money;

use Magebit\AgenticCore\Model\Money\MinorUnits;
use PHPUnit\Framework\TestCase;

class MinorUnitsTest extends TestCase
{
    private MinorUnits $minorUnits;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->minorUnits = new MinorUnits();
    }

    /**
     * @param float $amount
     * @param string $currency
     * @param int $expected
     * @return void
     * @dataProvider amountProvider
     */
    public function testConvertsToMinorUnits(float $amount, string $currency, int $expected): void
    {
        $this->assertSame($expected, $this->minorUnits->convert($amount, $currency));
    }

    /**
     * @return array<string, array{float, string, int}>
     */
    public static function amountProvider(): array
    {
        return [
            // Each of these truncates a unit low under a naive (int)($amount * 10 ** $exponent):
            // 19.99 * 100 is 1998.9999999999998 in binary floating point.
            'usd 19.99 truncated to 1998' => [19.99, 'USD', 1999],
            'usd 0.29 truncated to 28' => [0.29, 'USD', 29],
            'usd 1.15 truncated to 114' => [1.15, 'USD', 115],
            'usd 2.675 truncated to 267' => [2.675, 'USD', 268],
            'usd 1000.005 truncated to 100000' => [1000.005, 'USD', 100001],
            'usd zero' => [0.0, 'USD', 0],
            'usd whole amount' => [10.0, 'USD', 1000],
            'usd half rounds up' => [0.005, 'USD', 1],
            'usd extra precision rounds up' => [123.456, 'USD', 12346],
            'usd negative' => [-19.99, 'USD', -1999],
            'usd negative sub-unit' => [-0.29, 'USD', -29],
            'usd negative half rounds away from zero' => [-2.675, 'USD', -268],
            'jpy whole amount' => [1000.0, 'JPY', 1000],
            'jpy rounds down' => [1000.4, 'JPY', 1000],
            'jpy rounds up' => [1000.5, 'JPY', 1001],
            'jpy negative' => [-1000.0, 'JPY', -1000],
            'krw whole amount' => [50000.0, 'KRW', 50000],
            'vnd whole amount' => [25000.0, 'VND', 25000],
            'kwd three decimals' => [1.005, 'KWD', 1005],
            'kwd pads to three decimals' => [19.99, 'KWD', 19990],
            'kwd sub-unit rounds up' => [0.0005, 'KWD', 1],
            'kwd negative' => [-1.005, 'KWD', -1005],
            'bhd three decimals' => [12.345, 'BHD', 12345],
            'eur defaults to two' => [19.99, 'EUR', 1999],
            'unknown code defaults to two' => [19.99, 'XYZ', 1999],
            'lowercase currency code' => [19.99, 'usd', 1999],
        ];
    }

    /**
     * @return void
     */
    public function testExponentPerCurrency(): void
    {
        $this->assertSame(2, $this->minorUnits->getExponent('EUR'));
        $this->assertSame(0, $this->minorUnits->getExponent('KRW'));
        $this->assertSame(3, $this->minorUnits->getExponent('BHD'));
        $this->assertSame(MinorUnits::DEFAULT_EXPONENT, $this->minorUnits->getExponent('XYZ'));
    }
}
