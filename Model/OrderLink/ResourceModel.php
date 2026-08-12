<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\OrderLink;

use Magebit\AgenticCore\Api\Data\OrderLinkInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ResourceModel extends AbstractDb
{
    private const TABLE_NAME = 'agentic_checkout_order_link';

    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, OrderLinkInterface::ENTITY_ID);
    }
}
