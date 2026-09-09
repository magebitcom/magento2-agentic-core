<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Test\Unit\Model\Validation\Stub;

/**
 * Stands in for a generated specification interface: a non-nullable getter is a required field,
 * value constants name what a field may hold, and CONSTRAINTS carries the rest of the schema.
 */
interface OrderInterface
{
    public const KEY_ID = 'id';
    public const KEY_STATUS = 'status';
    public const KEY_EMAIL = 'email';
    public const KEY_NOTE = 'note';
    public const KEY_QUANTITY = 'quantity';
    public const KEY_ITEMS = 'items';
    public const KEY_IMAGES = 'images';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SHIPPED = 'shipped';

    public const CONSTRAINTS = [
        'id' => ['maxLength' => 8],
        'email' => ['format' => 'email'],
        'note' => ['pattern' => '^[A-Z]{2}-'],
        'quantity' => ['minimum' => 1],
        'items' => ['minItems' => 1],
        'images' => ['items' => ['format' => 'uri']],
    ];

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @return string|null
     */
    public function getEmail(): ?string;

    /**
     * @return string|null
     */
    public function getNote(): ?string;

    /**
     * @return int|null
     */
    public function getQuantity(): ?int;

    /**
     * @return \Magebit\AgenticCore\Test\Unit\Model\Validation\Stub\OrderItemInterface[]|null
     */
    public function getItems(): ?array;

    /**
     * A list of plain strings, so whatever the schema said about its entries has nowhere to live
     * but this list's own rules.
     *
     * @return string[]|null
     */
    public function getImages(): ?array;
}
