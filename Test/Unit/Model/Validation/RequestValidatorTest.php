<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Validation;

use Magebit\AgenticCore\Model\Validation\ConstraintChecker;
use Magebit\AgenticCore\Model\Validation\RequestValidator;
use Magebit\AgenticCore\Test\Unit\Model\Validation\Stub\OrderInterface;
use PHPUnit\Framework\TestCase;

class RequestValidatorTest extends TestCase
{
    /**
     * @var RequestValidator
     */
    private RequestValidator $validator;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->validator = new RequestValidator(new ConstraintChecker());
    }

    /**
     * @return void
     */
    public function testABodyMatchingTheInterfacePasses(): void
    {
        $this->assertTrue($this->validator->validate($this->order(), OrderInterface::class)->isValid());
    }

    /**
     * @return void
     */
    public function testAMissingRequiredFieldIsReported(): void
    {
        $order = $this->order();
        unset($order['status']);

        $errors = $this->validator->validate($order, OrderInterface::class)->getErrors();

        $this->assertArrayHasKey('status', $errors);
    }

    /**
     * A nullable getter is an optional field, so leaving it out cannot be an error.
     *
     * @return void
     */
    public function testAnOptionalFieldMayBeLeftOut(): void
    {
        $order = $this->order();
        unset($order['email'], $order['note'], $order['quantity'], $order['items']);

        $this->assertTrue($this->validator->validate($order, OrderInterface::class)->isValid());
    }

    /**
     * @return void
     */
    public function testAValueOutsideTheInterfacesListIsReported(): void
    {
        $order = $this->order();
        $order['status'] = 'invented';

        $errors = $this->validator->validate($order, OrderInterface::class)->getErrors();

        $this->assertArrayHasKey('status', $errors);
        $this->assertStringContainsString('pending', $errors['status']);
    }

    /**
     * @return void
     */
    public function testTheWrongTypeIsReported(): void
    {
        $order = $this->order();
        $order['quantity'] = 'two';

        $this->assertArrayHasKey(
            'quantity',
            $this->validator->validate($order, OrderInterface::class)->getErrors()
        );
    }

    /**
     * Everything below this point is a rule the schema declared and the generator carried onto the
     * interface, rather than anything the validator knows about these fields.
     *
     * @dataProvider brokenRuleProvider
     * @param string $field Field to spoil
     * @param mixed $value Value that breaks its rule
     * @return void
     */
    public function testARuleTheSchemaDeclaredIsEnforced(string $field, mixed $value): void
    {
        $order = $this->order();
        $order[$field] = $value;

        $this->assertArrayHasKey(
            $field,
            $this->validator->validate($order, OrderInterface::class)->getErrors()
        );
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function brokenRuleProvider(): array
    {
        return [
            'longer than maxLength' => ['id', 'far-too-long'],
            'not an email' => ['email', 'not-an-address'],
            'does not match pattern' => ['note', 'no prefix'],
            'below minimum' => ['quantity', 0],
            'fewer than minItems' => ['items', []],
        ];
    }

    /**
     * @return void
     */
    public function testARuleBrokenInsideAListIsReportedAtItsPosition(): void
    {
        $order = $this->order();
        $order['items'] = [['sku' => 'ok'], ['sku' => 'far-too-long']];

        $this->assertArrayHasKey(
            'items.1.sku',
            $this->validator->validate($order, OrderInterface::class)->getErrors()
        );
    }

    /**
     * A list of plain strings gets no element type to build, so a rule on its entries can only be
     * carried on the list itself. Unenforced, an entry the schema rejects passes.
     *
     * @return void
     */
    public function testARuleOnAListsEntriesIsEnforcedAtItsPosition(): void
    {
        $order = $this->order();
        $order['images'] = ['https://example.com/a.png', 'not-a-url'];

        $errors = $this->validator->validate($order, OrderInterface::class)->getErrors();

        $this->assertArrayHasKey('images.1', $errors);
        $this->assertArrayNotHasKey('images.0', $errors);
    }

    /**
     * @return array<string, mixed>
     */
    private function order(): array
    {
        return [
            'id' => 'ord_1',
            'status' => OrderInterface::STATUS_PENDING,
            'email' => 'buyer@example.com',
            'note' => 'EN-leave at door',
            'quantity' => 2,
            'items' => [['sku' => 'ok']],
            'images' => ['https://example.com/a.png'],
        ];
    }
}
