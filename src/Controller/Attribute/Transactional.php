<?php

declare(strict_types=1);

namespace Brick\App\Controller\Attribute;

use Attribute;
use Doctrine\DBAL\TransactionIsolationLevel;
use LogicException;

/**
 * Wraps the controller into a database transaction.
 *
 * This further allows to group external database reads (user authentication, ...) within the same transaction
 * as the one used by the controller.
 *
 * The supported isolation levels are SERIALIZABLE, REPEATABLE_READ, READ COMMITTED, READ UNCOMMITTED.
 *
 * If the controller does not explicitly commit the transaction,
 * it will be rolled back automatically when the controller returns.
 *
 * This attribute requires the `TransactionalPlugin`.
 */
#[Attribute]
final class Transactional
{
    /**
     * The transaction isolation level.
     */
    public int $isolationLevel = TransactionIsolationLevel::SERIALIZABLE;

    /**
     * Maps the isolation level strings to constants.
     */
    private const ISOLATION_LEVELS = [
        'READ UNCOMMITTED' => TransactionIsolationLevel::READ_UNCOMMITTED,
        'READ COMMITTED'   => TransactionIsolationLevel::READ_COMMITTED,
        'REPEATABLE READ'  => TransactionIsolationLevel::REPEATABLE_READ,
        'SERIALIZABLE'     => TransactionIsolationLevel::SERIALIZABLE
    ];

    public function __construct(int|null $isolationLevel = null)
    {
        if ($isolationLevel !== null) {
            if (! isset(self::ISOLATION_LEVELS[$isolationLevel])) {
                throw new LogicException('Invalid transaction isolation level: ' . $isolationLevel);
            }

            $this->isolationLevel = self::ISOLATION_LEVELS[$isolationLevel];
        }
    }
}
