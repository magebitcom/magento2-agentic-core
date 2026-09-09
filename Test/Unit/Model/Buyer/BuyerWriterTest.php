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

use Magebit\AgenticCore\Model\Buyer\BuyerIdentity;
use Magebit\AgenticCore\Model\Buyer\BuyerWriter;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\TestCase;

class BuyerWriterTest extends TestCase
{
    /**
     * @var BuyerWriter
     */
    private BuyerWriter $writer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->writer = new BuyerWriter();
    }

    /**
     * A second request that names only the phone number must not wipe the name and email the first
     * one set, so an absent field is left alone rather than written as empty.
     *
     * @return void
     */
    public function testAnAbsentFieldLeavesWhatIsAlreadyThere(): void
    {
        $address = $this->address();
        $address->setFirstname('Ada')->setEmail('ada@example.com');

        $this->writer->writeContact($address, new BuyerIdentity(null, null, null, '+15125550100'));

        $this->assertSame('Ada', $address->getFirstname());
        $this->assertSame('ada@example.com', $address->getEmail());
        $this->assertSame('+15125550100', $address->getTelephone());
    }

    /**
     * The phone belongs under `telephone`, which is the only key Magento reads it from.
     *
     * @return void
     */
    public function testThePhoneNumberLandsWhereMagentoReadsIt(): void
    {
        $address = $this->address();

        $this->writer->writeContact($address, new BuyerIdentity(null, null, null, '+15125550100'));

        $this->assertSame('+15125550100', $address->getData('telephone'));
    }

    /**
     * @return void
     */
    public function testTheQuoteCarriesTheNameAndEmail(): void
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['setCustomerFirstname', 'setCustomerLastname', 'setCustomerEmail'])
            ->getMock();

        $quote->expects($this->once())->method('setCustomerFirstname')->with('Ada');
        $quote->expects($this->once())->method('setCustomerLastname')->with('Lovelace');
        $quote->expects($this->once())->method('setCustomerEmail')->with('ada@example.com');

        $this->writer->writeCustomer(
            $quote,
            new BuyerIdentity('Ada', 'Lovelace', 'ada@example.com', null)
        );
    }

    /**
     * @return Address
     */
    private function address(): Address
    {
        /** @var Address $address */
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        return $address;
    }
}
