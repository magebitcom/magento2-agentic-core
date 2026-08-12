<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Buyer;

use Magebit\AgenticCore\Model\Buyer\BuyerResolver;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BuyerResolverTest extends TestCase
{
    private BuyerResolver $resolver;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->resolver = new BuyerResolver();
    }

    /**
     * @return void
     */
    public function testPrefersTheCustomerRecordOverBothAddresses(): void
    {
        $identity = $this->resolver->resolve($this->quote(
            ['firstname' => 'Customer', 'lastname' => 'Record', 'email' => 'customer@example.com'],
            ['firstname' => 'Billing', 'lastname' => 'Name', 'email' => 'billing@example.com'],
            ['firstname' => 'Shipping', 'lastname' => 'Name', 'email' => 'shipping@example.com']
        ));

        $this->assertSame('Customer', $identity->firstName);
        $this->assertSame('Record', $identity->lastName);
        $this->assertSame('customer@example.com', $identity->email);
    }

    /**
     * @return void
     */
    public function testFallsBackToBillingBeforeShipping(): void
    {
        $identity = $this->resolver->resolve($this->quote(
            [],
            ['firstname' => 'Billing', 'telephone' => '111'],
            ['firstname' => 'Shipping', 'telephone' => '222']
        ));

        $this->assertSame('Billing', $identity->firstName);
        $this->assertSame('111', $identity->phoneNumber);
    }

    /**
     * @return void
     */
    public function testFallsBackToShippingWhenBillingIsBlank(): void
    {
        $identity = $this->resolver->resolve($this->quote(
            [],
            [],
            ['firstname' => 'Shipping', 'lastname' => 'Recipient', 'telephone' => '222']
        ));

        $this->assertSame('Shipping', $identity->firstName);
        $this->assertSame('Recipient', $identity->lastName);
        $this->assertSame('222', $identity->phoneNumber);
    }

    /**
     * Magento hands back an empty string as readily as null, and an empty string is a value these
     * payloads reject.
     *
     * @return void
     */
    public function testTreatsBlankStringsAsAbsent(): void
    {
        $identity = $this->resolver->resolve($this->quote(
            ['firstname' => '', 'email' => '   '],
            ['firstname' => 'Billing', 'email' => 'billing@example.com'],
            []
        ));

        $this->assertSame('Billing', $identity->firstName);
        $this->assertSame('billing@example.com', $identity->email);
    }

    /**
     * @return void
     */
    public function testReportsEmptyWhenNothingIsSet(): void
    {
        $identity = $this->resolver->resolve($this->quote([], [], []));

        $this->assertTrue($identity->isEmpty());
        $this->assertNull($identity->firstName);
    }

    /**
     * A buyer with only an email is still a buyer; dropping it loses the one field most of these
     * payloads care about.
     *
     * @return void
     */
    public function testAPartialBuyerIsNotEmpty(): void
    {
        $identity = $this->resolver->resolve($this->quote(['email' => 'only@example.com'], [], []));

        $this->assertFalse($identity->isEmpty());
        $this->assertSame('only@example.com', $identity->email);
        $this->assertNull($identity->firstName);
    }

    /**
     * @param array<string, string> $customer
     * @param array<string, string> $billing
     * @param array<string, string> $shipping
     * @return Quote&MockObject
     */
    private function quote(array $customer, array $billing, array $shipping): Quote
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            // All three customer accessors are magic on Quote rather than declared.
            ->addMethods(['getCustomerFirstname', 'getCustomerLastname', 'getCustomerEmail'])
            ->onlyMethods(['getBillingAddress', 'getShippingAddress'])
            ->getMock();

        $quote->method('getCustomerFirstname')->willReturn($customer['firstname'] ?? null);
        $quote->method('getCustomerLastname')->willReturn($customer['lastname'] ?? null);
        $quote->method('getCustomerEmail')->willReturn($customer['email'] ?? null);
        $quote->method('getBillingAddress')->willReturn($this->address($billing));
        $quote->method('getShippingAddress')->willReturn($this->address($shipping));

        return $quote;
    }

    /**
     * @param array<string, string> $data
     * @return Address&MockObject
     */
    private function address(array $data): Address
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFirstname', 'getLastname', 'getEmail', 'getTelephone'])
            ->getMock();

        $address->method('getFirstname')->willReturn($data['firstname'] ?? null);
        $address->method('getLastname')->willReturn($data['lastname'] ?? null);
        $address->method('getEmail')->willReturn($data['email'] ?? null);
        $address->method('getTelephone')->willReturn($data['telephone'] ?? null);

        return $address;
    }
}
