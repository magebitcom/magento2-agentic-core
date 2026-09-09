<?php

/**
 * This file is part of the Magebit_AgenticCore package.
 *
 * @copyright Copyright (c) 2026 Magebit, Ltd. (https://magebit.com/)
 * @author    Magebit <info@magebit.com>
 * @license   MIT
 */

declare(strict_types=1);

namespace Magebit\AgenticCore\Model\Webhook;

use Psr\Log\LoggerInterface;

/**
 * Sends whatever deliveries are due for one caller. Meant to be run on a schedule, but the backoff
 * is what paces retries, so running it more often than the shortest wait achieves nothing.
 *
 * A failure is logged rather than thrown: the next run tries again, and a cron job that throws is
 * reported as broken when the delivery target is what is actually down.
 */
class DueDeliveries
{
    /**
     * @param Dispatcher $dispatcher
     * @param LoggerInterface $logger
     * @param string $scope Partitions this caller's rows in the shared queue
     */
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly LoggerInterface $logger,
        private readonly string $scope = ''
    ) {
    }

    /**
     * @return int How many deliveries went out
     */
    public function execute(): int
    {
        try {
            $delivered = $this->dispatcher->dispatchDue($this->scope);

            if ($delivered > 0) {
                $this->logger->info(sprintf('Delivered %d webhook(s).', $delivered));
            }

            return $delivered;
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf('Error dispatching webhooks: %s', $exception->getMessage()),
                ['exception' => $exception]
            );

            return 0;
        }
    }
}
