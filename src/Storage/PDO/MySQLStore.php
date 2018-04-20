<?php

namespace Cart\Storage\PDO;

/**
 * Class PDOMySQLStore
 *
 * CREATE TABLE `pepeverdeCart` (
 *      `cart_id` VARCHAR(128) NOT NULL PRIMARY KEY,
 *      `cart_data` MEDIUMBLOB NOT NULL,
 *      `cart_time` INTEGER UNSIGNED NOT NULL
 * ) COLLATE utf8_bin, ENGINE = InnoDB;
 *
 */
class MySQLStore implements \Cart\Storage\Store
{
    /** @var \PDO */
    private $pdo;
    private $tableName;

    /**
     * PDOStore constructor.
     * @param \PDO $pdo
     * @param string $tablename
     * @throws \Exception
     */
    public function __construct(\PDO $pdo, $tablename = 'pepeverdeCart')
    {
        if (\PDO::ERRMODE_EXCEPTION !== $pdo->getAttribute(\PDO::ATTR_ERRMODE)) {
            throw new \InvalidArgumentException(sprintf('"%s" requires PDO error mode attribute be set to throw Exceptions (i.e. $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION))', __CLASS__));
        }

        if ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            throw new \RuntimeException(sprintf('"%s" require a MySQL PDO', __CLASS__));
        }

        $this->pdo = $pdo;
        $this->tableName = $tablename;
    }

    /**
     * Retrieve the saved state for a cart instance.
     *
     * @param string $cartId
     *
     * @return string
     * @throws \PDOException
     */
    public function get($cartId)
    {
        $selectSql = "SELECT `cart_data` FROM $this->tableName WHERE `cart_id` = :id";
        $selectStmt = $this->pdo->prepare($selectSql);
        $selectStmt->bindParam(':id', $cartId, \PDO::PARAM_STR);
        if ($selectStmt->execute()) {
            $row = $selectStmt->fetchAll();
            if ($row) {
                return $row['cart_data'];
            }
        }

        return serialize([]);
    }

    /**
     * Save the state for a cart instance.
     *
     * @param string $cartId
     * @param string $data
     * @throws \PDOException
     */
    public function put($cartId, $data)
    {
        // TODO: Implement put() method.
        $putSql = "REPLACE INTO $this->tableName VALUES (:id, :data, :time)";
        $putStmt = $this->pdo->prepare($putSql);
        $time = time();
        $putStmt->bindParam(':id', $cartId, \PDO::PARAM_STR);
        $putStmt->bindParam(':data', $data, \PDO::PARAM_STR);
        $putStmt->bindParam(':time', $time, \PDO::PARAM_STR);
        $putStmt->execute();
    }

    /**
     * Flush the saved state for a cart instance.
     *
     * @param string $cartId
     */
    public function flush($cartId)
    {
        $flushSql = "DELETE FROM $this->tableName WHERE `cart_id` = :id";
        $flushStmt = $this->pdo->prepare($flushSql);
        $flushStmt->bindParam(':id', $cartId, \PDO::PARAM_STR);
        $flushStmt->execute();
    }

    /**
     * @throws \PDOException
     */
    public function createTable()
    {
        try {
            $sql = "CREATE TABLE $this->tableName (`cart_id` VARBINARY(255) NOT NULL PRIMARY KEY, `cart_data` MEDIUMBLOB NOT NULL, `cart_time` INTEGER UNSIGNED NOT NULL) COLLATE utf8_bin, ENGINE = InnoDB";
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }
}
