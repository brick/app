<?php

namespace Brick\App\Session\Storage;

/**
 * PDO storage engine for storing sessions in the database.
 *
 * Important: if you already have a PDO instance for your application, you must not share it
 * with this storage. Instead, create a separate PDO connection dedicated to the session storage.
 *
 * The database must support transactions.
 *
 * Sample SQL table structure:
 *
 *   CREATE TABLE session(
 *     s_id CHAR(40) BINARY NOT NULL,
 *     s_key VARCHAR(100) BINARY NOT NULL,
 *     s_value BLOB NOT NULL,
 *     s_last_access INTEGER UNSIGNED NOT NULL,
 *     PRIMARY KEY(s_id, s_key),
 *     KEY(s_last_access)
 *   );
 *
 * This is just an example, and you may need to adjust the values.
 */
class PdoStorage implements SessionStorage
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private static $defaultOptions = [
        'table-name'         => 'session',       // The table name.
        'id-column'          => 's_id',          // The column containing the session id.
        'key-column'         => 's_key',         // The column containing the data key.
        'value-column'       => 's_value',       // The column containing the data value.
        'last-access-column' => 's_last_access', // The column containing the last access timestamp.
        'last-access-grace'  => 60               // The imprecision allowed for the last access timestamp, in seconds.
    ];

    /**
     * Class constructor.
     *
     * @param \PDO  $pdo
     * @param array $options
     */
    public function __construct(\PDO $pdo, array $options = [])
    {
        $this->pdo = $pdo;
        $this->options = $options + self::$defaultOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function read($id, $key, & $lockContext)
    {
        if ($lockContext) {
            $this->pdo->beginTransaction();
        }

        $query = sprintf(
            'SELECT %s, %s FROM %s WHERE %s = ? AND %s = ? FOR UPDATE',
            $this->options['value-column'],
            $this->options['last-access-column'],
            $this->options['table-name'],
            $this->options['id-column'],
            $this->options['key-column']
        );

        $statement = $this->pdo->prepare($query);
        $statement->execute([$id, $key]);

        $data = $statement->fetch(\PDO::FETCH_NUM);
        $statement->closeCursor();

        if ($data === false) {
            return null;
        }

        // Only update the last access time if it's older than the imprecision allowed.
        if (time() - $data[1] > $this->options['last-access-grace']) {
            $this->touch($id, $key);
        }

        return $data[0];
    }

    /**
     * Updates a record with the current timestamp.
     *
     * @param string $id
     * @param string $key
     *
     * @return void
     */
    private function touch($id, $key)
    {
        $query = sprintf('
            UPDATE %s SET %s = ? WHERE %s = ? AND %s = ?',
            $this->options['table-name'],
            $this->options['last-access-column'],
            $this->options['id-column'],
            $this->options['key-column']
        );

        $statement = $this->pdo->prepare($query);
        $statement->execute([time(), $id, $key]);
    }

    /**
     * {@inheritdoc}
     */
    public function write($id, $key, $value, $lockContext)
    {
        $this->updateRecord($id, $key, $value) || $this->insertRecord($id, $key, $value);

        if ($lockContext) {
            $this->pdo->commit();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unlock($lockContext)
    {
        $this->pdo->rollBack();
    }

    /**
     * Updates the record with the given id and key.
     *
     * The CASE statement is here to ensure that if two updates occur within the same second,
     * the record will still be updated and the method will return true.
     *
     * @param string $id
     * @param string $key
     * @param string $value
     *
     * @return boolean Whether the record exists and was updated.
     */
    private function updateRecord($id, $key, $value)
    {
        $query = sprintf(
            'UPDATE %s SET %s = ?, %s = CASE WHEN %s = ? THEN %s + 1 ELSE ? END WHERE %s = ? AND %s = ?',
            $this->options['table-name'],
            $this->options['value-column'],
            $this->options['last-access-column'],
            $this->options['last-access-column'],
            $this->options['last-access-column'],
            $this->options['id-column'],
            $this->options['key-column']
        );

        $statement = $this->pdo->prepare($query);
        $statement->execute([$value, $time = time(), $time, $id, $key]);

        return $statement->rowCount() != 0;
    }

    /**
     * Creates a new record with the given id and key.
     *
     * @param string $id
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    private function insertRecord($id, $key, $value)
    {
        $query = sprintf(
            'INSERT INTO %s (%s, %s, %s, %s) VALUES(?, ?, ?, ?)',
            $this->options['table-name'],
            $this->options['id-column'],
            $this->options['key-column'],
            $this->options['value-column'],
            $this->options['last-access-column']
        );

        $statement = $this->pdo->prepare($query);
        $statement->execute([$id, $key, $value, time()]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id, $key)
    {
        $query = sprintf(
            'DELETE FROM %s WHERE %s = ? AND %s = ?',
            $this->options['table-name'],
            $this->options['id-column'],
            $this->options['key-column']
        );

        $statement = $this->pdo->prepare($query);
        $statement->execute([$id, $key]);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($id)
    {
        $query = sprintf(
            'DELETE FROM %s WHERE %s = ?',
            $this->options['table-name'],
            $this->options['id-column']
        );

        $statement = $this->pdo->prepare($query);
        $statement->execute([$id]);
    }

    /**
     * {@inheritdoc}
     */
    public function expire($lifetime)
    {
        $query = sprintf(
            'DELETE FROM %s WHERE %s < ?',
            $this->options['table-name'],
            $this->options['last-access-column']
        );

        $statement = $this->pdo->prepare($query);
        $statement->execute([time() - $lifetime]);
    }

    /**
     * {@inheritdoc}
     */
    public function updateId($oldId, $newId)
    {
        $query = sprintf(
            'UPDATE %s SET %s = ?, %s = ? WHERE %s = ?',
            $this->options['table-name'],
            $this->options['id-column'],
            $this->options['last-access-column'],
            $this->options['id-column']
        );

        $this->executeQuery($query, [
            $newId,
            time(),
            $oldId
        ]);

        return true;
    }

    /**
     * @param string $query
     * @param array  $parameters
     *
     * @return boolean
     */
    private function executeQuery($query, array $parameters)
    {
        $statement = $this->pdo->prepare($query);
        $statement->execute($parameters);

        return $statement->rowCount() != 0;
    }
}
