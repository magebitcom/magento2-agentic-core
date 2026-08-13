<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Total;

use Magebit\AgenticCore\Model\Total\TypeLabel;
use PHPUnit\Framework\TestCase;

class TypeLabelTest extends TestCase
{
    /**
     * @var TypeLabel
     */
    private TypeLabel $label;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->label = new TypeLabel();
    }

    /**
     * @return void
     */
    public function testTurnsASnakeCasedTypeIntoALabel(): void
    {
        $this->assertSame('Items base amount', $this->label->for('items_base_amount'));
        $this->assertSame('Total', $this->label->for('total'));
    }

    /**
     * @return void
     */
    public function testPrefersTheGivenTitle(): void
    {
        $this->assertSame('Shipping & Handling', $this->label->orFallback('Shipping & Handling', 'fulfillment'));
    }

    /**
     * @return void
     */
    public function testFallsBackWhenTheTitleIsEmptyOrMissing(): void
    {
        $this->assertSame('Fulfillment', $this->label->orFallback('', 'fulfillment'));
        $this->assertSame('Fulfillment', $this->label->orFallback(null, 'fulfillment'));
        $this->assertSame('Fulfillment', $this->label->orFallback('   ', 'fulfillment'));
    }
}
