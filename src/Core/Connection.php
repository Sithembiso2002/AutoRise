<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

final class Connection
{
    private PDO $pdo;
    private string $driver;

    public function __construct(private readonly array $config)
    {
        $this->driver = $config['driver'] ?? 'mysql';
        $this->pdo    = $this->connect();
    }

    private function connect(): PDO
    {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];

        if ($this->driver === 'sqlite') {
            $pdo = new PDO('sqlite:' . $this->config['database'], null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA busy_timeout = 5000');
            return $pdo;
        }

        if ($this->driver === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset'] ?? 'utf8mb4'
            );
            return new PDO($dsn, $this->config['username'], $this->config['password'], $options);
        }

        throw new RuntimeException("Unsupported DB driver: {$this->driver}");
    }

    public function pdo(): PDO       { return $this->pdo; }
    public function driver(): string { return $this->driver; }
    public function isSqlite(): bool { return $this->driver === 'sqlite'; }
    public function isMysql(): bool  { return $this->driver === 'mysql'; }

    private function run(string $sql, array $bind = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bind);
        return $stmt;
    }

    public function select(string $sql, array $bind = []): array
    {
        return $this->run($sql, $bind)->fetchAll();
    }

    public function selectOne(string $sql, array $bind = []): ?array
    {
        $row = $this->run($sql, $bind)->fetch();
        return $row === false ? null : $row;
    }

    public function scalar(string $sql, array $bind = []): mixed
    {
        return $this->run($sql, $bind)->fetchColumn();
    }

    public function execute(string $sql, array $bind = []): int
    {
        return $this->run($sql, $bind)->rowCount();
    }

    public function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql  = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $cols),
            implode(', ', array_map(fn($c) => ':' . $c, $cols))
        );
        $this->run($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $bind = []): int
    {
        $sets = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
        return $this->execute("UPDATE {$table} SET {$sets} WHERE {$where}", $data + $bind);
    }

    public function transaction(callable $fn, int $attempts = 3): mixed
    {
        for ($i = 1; ; $i++) {
            try {
                $this->pdo->beginTransaction();
                $result = $fn($this);
                $this->pdo->commit();
                return $result;
            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) $this->pdo->rollBack();
                if ($i >= $attempts || !$this->isRetryable($e)) throw $e;
                usleep(50_000 * $i);
            }
        }
    }

    private function isRetryable(Throwable $e): bool
    {
        $m = $e->getMessage();
        return str_contains($m, 'Deadlock') || str_contains($m, 'database is locked');
    }
}