<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_rh');
define('DB_USER', 'root'); // En local XAMPP ; en prod utiliser un utilisateur dédié (ex. gestion_rh_user)
define('DB_PASS', ''); // Mot de passe root si vous en avez défini un
define('DB_CHARSET', 'utf8mb4');


/**
 * Centralized database wrapper that enforces prepared statements.
 * - Only prepare() and exec() (for DDL) are exposed; query() is intentionally not available.
 * - No raw SQL fragments must be built from user input; use placeholders and bound parameters only.
 */
class SafeDB {
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Prepare a statement. All dynamic values must be passed via execute($params), never concatenated into $sql.
     *
     * @param string $sql SQL with ? placeholders (no user input in the string)
     * @param array $options Driver options
     * @return PDOStatement
     */
    public function prepare($sql, array $options = []) {
        return $this->pdo->prepare($sql, $options);
    }

    /**
     * Run a prepared statement in one call. Use for SELECT/INSERT/UPDATE/DELETE with bound parameters.
     * Do not pass user input into $sql; use only ? placeholders and $params.
     *
     * @param string $sql SQL with ? placeholders
     * @param array $params Ordered list of values to bind
     * @return PDOStatement
     */
    public function run($sql, array $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Execute a single DDL or non-SELECT statement (e.g. CREATE TABLE). Do not use with user input.
     *
     * @param string $sql Static SQL only (no user-controlled fragments)
     * @return int|false
     */
    public function exec($sql) {
        return $this->pdo->exec($sql);
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollBack() {
        return $this->pdo->rollBack();
    }
}


class Database {
    private static $instance = null;
    /** @var PDO */
    private $conn;
    /** @var SafeDB */
    private $wrapper;

    private function __construct() {
        try {
            // DSN with utf8mb4 for full Unicode support (including emoji)
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            // Secure PDO mode: exceptions, real prepared statements, multi-statement execution disabled
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            if (defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
                $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;
            }
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->wrapper = new SafeDB($this->conn);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Returns the SafeDB wrapper (no query() method; use prepare/run only).
     */
    public function getConnection() {
        return $this->wrapper;
    }
}


/**
 * Get the application database handle. Use only prepare()->execute() or run($sql, $params).
 * Do not build SQL from user input.
 */
function getDB() {
    return Database::getInstance()->getConnection();
}
