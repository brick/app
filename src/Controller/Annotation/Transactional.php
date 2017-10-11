<?php

namespace Brick\App\Controller\Annotation;

use Doctrine\DBAL\Connection;

/**
 * Wraps the controller into a database transaction.
 *
 * This further allows to group external database reads (user authentication, ...) within the same transaction
 * as the one used by the controller.
 *
 * The supported isolation levels are SERIALIZABLE, REPEATABLE_READ, READ_COMMITTED, READ_UNCOMMITTED.
 *
 * If the controller does not explicitly commit the transaction,
 * it will be rolled back automatically when the controller returns.
 *
 * This annotation requires the `TransactionalPlugin`.
 *
 * @Annotation
 * @Target("METHOD")
 */
class Transactional extends AbstractAnnotation
{
    /**
     * The transaction isolation level.
     *
     * @var int
     */
    private $isolationLevel = Connection::TRANSACTION_SERIALIZABLE;

    /**
     * Maps the isolation level strings to constants.
     *
     * @var array
     */
    private static $isolationLevels = [
        'READ UNCOMMITTED' => Connection::TRANSACTION_READ_UNCOMMITTED,
        'READ COMMITTED'   => Connection::TRANSACTION_READ_COMMITTED,
        'REPEATABLE READ'  => Connection::TRANSACTION_REPEATABLE_READ,
        'SERIALIZABLE'     => Connection::TRANSACTION_SERIALIZABLE
    ];

    /**
     * @param string $isolationLevel
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function setValue(string $isolationLevel) : void
    {
        if (! isset(self::$isolationLevels[$isolationLevel])) {
            throw new \RuntimeException('Invalid transaction isolation level: ' . $isolationLevel);
        }

        $this->isolationLevel = self::$isolationLevels[$isolationLevel];
    }

    /**
     * @return int
     */
    public function getIsolationLevel() : int
    {
        return $this->isolationLevel;
    }
}
