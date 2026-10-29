<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class DbException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0, public readonly array $payload = [])
    {
        parent::__construct($message, $status);
    }
}

/**
 * MySQL / MariaDB data layer (PDO).
 *
 * The fluent API is the one the whole application was written against:
 *
 *   Db::from('pages')->eq('slug', 'home')->first();
 *   Db::from('pages')->insert([...]);                // returns the inserted rows
 *   Db::from('pages')->eq('id', $id)->update([...]); // returns the updated rows
 *   Db::rpc('dashboard_overview', ['p_from' => ..., 'p_to' => ...]);
 *
 * Rows come back the way the templates expect them: JSON columns decoded to
 * arrays, TINYINT(1) columns as booleans, DATETIME columns as ISO-8601 UTC
 * strings, DECIMAL columns as numbers. On the way in, arrays are JSON-encoded,
 * booleans become 0 / 1 and ISO timestamps are converted to UTC DATETIME.
 */
final class Db
{
    private static ?PDO $pdo = null;
    /** @var array<string,array<string,string>> table => column => kind (json|bool|datetime|date|decimal|uuid|int|text) */
    private static array $columns = [];
    public static int $queries = 0;

    public static function from(string $table): Query
    {
        return new Query($table);
    }

    /** Server-side procedures used to live in PostgreSQL; they are PHP methods now. */
    public static function rpc(string $function, array $args = []): mixed
    {
        $fn = preg_replace('/[^a-zA-Z0-9_]/', '', $function);
        if (!method_exists(Rpc::class, $fn)) {
            throw new DbException('Unknown database procedure: ' . $fn);
        }
        return Rpc::$fn(...array_values($args));
    }

    public static function configured(): bool
    {
        return (string)env('DB_NAME', '') !== '' && (string)env('DB_USER', '') !== '';
    }

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        if (!self::configured()) {
            throw new DbException('The database is not configured. Set DB_HOST, DB_NAME, DB_USER and DB_PASS in .env (or open /install).');
        }
        self::$pdo = self::connect(
            (string)env('DB_HOST', '127.0.0.1'),
            (int)env('DB_PORT', 3306),
            (string)env('DB_NAME', ''),
            (string)env('DB_USER', ''),
            (string)env('DB_PASS', ''),
            (string)env('DB_SOCKET', '')
        );
        return self::$pdo;
    }

    /** Open a connection with explicit credentials (used by the installer and tools). */
    public static function connect(string $host, int $port, string $name, string $user, string $pass, string $socket = ''): PDO
    {
        $dsn = $socket !== ''
            ? 'mysql:unix_socket=' . $socket . ';dbname=' . $name . ';charset=utf8mb4'
            : 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
                PDO::ATTR_TIMEOUT            => 8,
            ]);
            // everything is stored and compared in UTC; the dashboard converts for display
            $pdo->exec("SET time_zone = '+00:00', sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION,ERROR_FOR_DIVISION_BY_ZERO'");
        } catch (PDOException $e) {
            Log::error('Database connection error: ' . $e->getMessage());
            throw new DbException('Database connection failed: ' . self::friendly($e->getMessage()), (int)$e->getCode());
        }
        return $pdo;
    }

    /** Use an already opened connection for the rest of the request (installer). */
    public static function use(PDO $pdo): void
    {
        self::$pdo = $pdo;
        self::$columns = [];
    }

    /** Lightweight connectivity test used by tools/check.php */
    public static function ping(): bool
    {
        try {
            self::pdo()->query('SELECT 1')->fetch();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    // ------------------------------------------------------------------ raw access

    /** @return array<int,array> */
    public static function select(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function selectOne(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** Execute a statement and return the affected row count. */
    public static function exec(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        self::$queries++;
        try {
            $st = self::pdo()->prepare($sql);
            $i = 1;
            foreach ($params as $k => $v) {
                $type = match (true) {
                    is_int($v) => PDO::PARAM_INT,
                    is_bool($v) => PDO::PARAM_INT,
                    $v === null => PDO::PARAM_NULL,
                    default => PDO::PARAM_STR,
                };
                $st->bindValue(is_int($k) ? $i++ : $k, is_bool($v) ? (int)$v : $v, $type);
            }
            $st->execute();
            return $st;
        } catch (PDOException $e) {
            Log::error('Database error: ' . $e->getMessage() . ' [' . $sql . ']');
            throw new DbException(self::friendly($e->getMessage()), (int)$e->getCode());
        }
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $fn();
        }
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function quoteIdent(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    public static function uuid(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }

    public static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    // ------------------------------------------------------------------ column metadata

    /**
     * Column kinds of a table, read once from SHOW FULL COLUMNS and cached.
     * @return array<string,string>
     */
    public static function columns(string $table): array
    {
        if (isset(self::$columns[$table])) {
            return self::$columns[$table];
        }
        $cached = Cache::get('db.columns.' . $table);
        if (is_array($cached) && $cached !== []) {
            return self::$columns[$table] = $cached;
        }
        $kinds = [];
        foreach (self::select('SHOW FULL COLUMNS FROM ' . self::quoteIdent($table)) as $col) {
            $type = strtolower((string)$col['Type']);
            $comment = strtolower((string)($col['Comment'] ?? ''));
            $kinds[(string)$col['Field']] = match (true) {
                $type === 'json' || str_contains($comment, 'json') => 'json',
                $type === 'tinyint(1)' => 'bool',
                str_starts_with($type, 'datetime') || str_starts_with($type, 'timestamp') => 'datetime',
                $type === 'date' => 'date',
                str_starts_with($type, 'decimal') || str_starts_with($type, 'double') || str_starts_with($type, 'float') => 'decimal',
                $type === 'char(36)' => 'uuid',
                str_contains($type, 'int') => 'int',
                default => 'text',
            };
        }
        if ($kinds === []) {
            throw new DbException('Unknown table: ' . $table);
        }
        Cache::set('db.columns.' . $table, $kinds, 3600);
        return self::$columns[$table] = $kinds;
    }

    public static function hasTable(string $table): bool
    {
        try {
            return self::value('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$table]) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /** Convert a fetched row to the shape the application expects. */
    public static function hydrate(string $table, array $row): array
    {
        $kinds = self::columns($table);
        foreach ($row as $k => $v) {
            if ($v === null || !isset($kinds[$k])) continue;
            switch ($kinds[$k]) {
                case 'json':
                    $decoded = json_decode((string)$v, true);
                    $row[$k] = json_last_error() === JSON_ERROR_NONE ? $decoded : $v;
                    break;
                case 'bool':
                    $row[$k] = (bool)$v;
                    break;
                case 'datetime':
                    $row[$k] = self::toIso((string)$v);
                    break;
                case 'decimal':
                    $f = (float)$v;
                    $row[$k] = floor($f) === $f ? (int)$f : $f;
                    break;
                case 'int':
                    $row[$k] = is_numeric($v) ? (int)$v : $v;
                    break;
            }
        }
        return $row;
    }

    /** Convert a value coming from the application to what the column stores. */
    public static function dehydrate(string $table, string $column, mixed $v): mixed
    {
        $kind = self::columns($table)[$column] ?? 'text';
        if ($v === null) return null;
        switch ($kind) {
            case 'json':
                return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            case 'bool':
                return (is_string($v) ? filter_var($v, FILTER_VALIDATE_BOOL) : (bool)$v) ? 1 : 0;
            case 'datetime':
                return is_string($v) ? self::toSql($v) : $v;
            case 'date':
                return is_string($v) && $v !== '' ? substr(self::toSql($v), 0, 10) : ($v === '' ? null : $v);
            case 'int':
                return is_bool($v) ? (int)$v : (is_numeric($v) ? (int)$v : $v);
            default:
                if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (is_bool($v)) return $v ? 1 : 0;
                return $v;
        }
    }

    /** 'Y-m-d H:i:s' (UTC) -> ISO-8601 with offset, the format the old API returned. */
    public static function toIso(string $sql): string
    {
        if ($sql === '' || str_starts_with($sql, '0000')) return $sql;
        return str_replace(' ', 'T', substr($sql, 0, 19)) . '+00:00';
    }

    /** Any ISO-8601 / RFC 2822 / SQL datetime string -> 'Y-m-d H:i:s' in UTC. */
    public static function toSql(string $value): string
    {
        $value = trim($value);
        if ($value === '') return $value;
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) return $value;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value . ' 00:00:00';
        try {
            $d = new \DateTimeImmutable($value);
            return $d->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return $value;
        }
    }

    private static function friendly(string $message): string
    {
        // strip the SQLSTATE noise PDO prepends, keep the server's sentence
        return trim((string)preg_replace('/^SQLSTATE\[[A-Z0-9]+\](\s*\[\d+\])?:?\s*/', '', $message));
    }
}

/** Fluent query builder producing parameterised MySQL. */
final class Query
{
    private array $where = [];   // [sql, params]
    private array $columns = ['*'];
    /** @var array<int,array{alias:string,table:string,columns:array,fk:string}> */
    private array $embeds = [];
    private array $order = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(private readonly string $table)
    {
    }

    // ------------------------------------------------------------------ selection

    /**
     * Column list. Supports the embed syntax used by the models:
     *   select('*,category:project_categories(id,slug,name)')
     * which attaches the related row (through <alias>_id) as $row['category'].
     */
    public function select(string $columns): self
    {
        $this->columns = [];
        $this->embeds = [];
        foreach ($this->splitTopLevel($columns) as $part) {
            $part = trim($part);
            if ($part === '') continue;
            if (preg_match('/^([a-z_]+):([a-z_]+)\(([^)]*)\)$/i', $part, $m)) {
                $this->embeds[] = ['alias' => $m[1], 'table' => $m[2], 'columns' => array_map('trim', explode(',', $m[3])), 'fk' => $m[1] . '_id'];
                continue;
            }
            $this->columns[] = $part;
        }
        if ($this->columns === []) $this->columns = ['*'];
        return $this;
    }

    // ------------------------------------------------------------------ filters

    private function col(string $c): string
    {
        return Db::quoteIdent($this->table) . '.' . Db::quoteIdent($c);
    }

    private function cmp(string $c, string $op, mixed $v): self
    {
        if ($v === null) {
            $this->where[] = [$this->col($c) . ($op === '<>' ? ' IS NOT NULL' : ' IS NULL'), []];
            return $this;
        }
        $this->where[] = [$this->col($c) . ' ' . $op . ' ?', [Db::dehydrate($this->table, $c, $v)]];
        return $this;
    }

    public function eq(string $c, mixed $v): self { return $this->cmp($c, '=', $v); }
    public function neq(string $c, mixed $v): self { return $this->cmp($c, '<>', $v); }
    public function gt(string $c, mixed $v): self { return $this->cmp($c, '>', $v); }
    public function gte(string $c, mixed $v): self { return $this->cmp($c, '>=', $v); }
    public function lt(string $c, mixed $v): self { return $this->cmp($c, '<', $v); }
    public function lte(string $c, mixed $v): self { return $this->cmp($c, '<=', $v); }

    /** is(col, null) -> IS NULL, is(col, true/false) -> = 1 / 0 */
    public function is(string $c, mixed $v): self
    {
        if ($v === null) {
            $this->where[] = [$this->col($c) . ' IS NULL', []];
            return $this;
        }
        return $this->cmp($c, '=', (bool)$v);
    }

    /** Pattern with * or % wildcards (case-insensitive under utf8mb4_unicode_ci). */
    public function like(string $c, string $pattern): self
    {
        $this->where[] = [$this->col($c) . ' LIKE ?', [str_replace('*', '%', $pattern)]];
        return $this;
    }

    public function ilike(string $c, string $pattern): self
    {
        return $this->like($c, $pattern);
    }

    public function in(string $c, array $values): self
    {
        if ($values === []) {
            $this->where[] = ['1 = 0', []];
            return $this;
        }
        $params = array_map(fn($v) => Db::dehydrate($this->table, $c, $v), array_values($values));
        $this->where[] = [$this->col($c) . ' IN (' . implode(',', array_fill(0, count($params), '?')) . ')', $params];
        return $this;
    }

    /**
     * OR group written as "col.op.value,col.op.value" (e.g. "name.ilike.*x*,email.ilike.*x*").
     * Supported ops: eq, neq, gt, gte, lt, lte, like, ilike, is.
     */
    public function or(string $expression): self
    {
        $parts = [];
        $params = [];
        foreach ($this->splitTopLevel($expression) as $term) {
            $term = trim($term);
            if ($term === '') continue;
            $seg = explode('.', $term, 3);
            if (count($seg) !== 3 || !preg_match('/^[a-z_]+$/i', $seg[0])) continue;
            [$c, $op, $val] = $seg;
            $col = $this->col($c);
            switch ($op) {
                case 'like':
                case 'ilike':
                    $parts[] = $col . ' LIKE ?';
                    $params[] = str_replace('*', '%', $val);
                    break;
                case 'is':
                    if ($val === 'null') { $parts[] = $col . ' IS NULL'; }
                    else { $parts[] = $col . ' = ?'; $params[] = $val === 'true' ? 1 : 0; }
                    break;
                default:
                    $sqlOp = ['eq' => '=', 'neq' => '<>', 'gt' => '>', 'gte' => '>=', 'lt' => '<', 'lte' => '<='][$op] ?? null;
                    if ($sqlOp === null) continue 2;
                    $parts[] = $col . ' ' . $sqlOp . ' ?';
                    $params[] = Db::dehydrate($this->table, $c, $val);
            }
        }
        if ($parts) {
            $this->where[] = ['(' . implode(' OR ', $parts) . ')', $params];
        }
        return $this;
    }

    public function order(string $column, bool $asc = true, bool $nullsLast = true): self
    {
        $col = $this->col($column);
        // MySQL sorts NULL first in ASC and last in DESC
        if ($asc && $nullsLast) $this->order[] = $col . ' IS NULL';
        if (!$asc && !$nullsLast) $this->order[] = $col . ' IS NOT NULL';
        $this->order[] = $col . ($asc ? ' ASC' : ' DESC');
        return $this;
    }

    public function limit(int $n): self { $this->limit = $n; return $this; }
    public function offset(int $n): self { $this->offset = $n; return $this; }

    // ------------------------------------------------------------------ SQL assembly

    /** @return array{0:string,1:array} */
    private function whereSql(): array
    {
        if (!$this->where) return ['', []];
        $sql = [];
        $params = [];
        foreach ($this->where as [$s, $p]) {
            $sql[] = $s;
            $params = array_merge($params, $p);
        }
        return [' WHERE ' . implode(' AND ', $sql), $params];
    }

    private function selectSql(): string
    {
        $cols = [];
        foreach ($this->columns as $c) {
            $cols[] = $c === '*' ? Db::quoteIdent($this->table) . '.*' : $this->col($c);
        }
        // embeds need the foreign key even when it was not requested
        foreach ($this->embeds as $e) {
            if (!in_array('*', $this->columns, true) && !in_array($e['fk'], $this->columns, true)) {
                $cols[] = $this->col($e['fk']);
            }
        }
        return implode(', ', $cols);
    }

    private function tailSql(): string
    {
        $sql = '';
        if ($this->order) $sql .= ' ORDER BY ' . implode(', ', $this->order);
        if ($this->limit !== null) $sql .= ' LIMIT ' . (int)$this->limit;
        if ($this->offset !== null) $sql .= ' OFFSET ' . (int)$this->offset;
        return $sql;
    }

    // ------------------------------------------------------------------ reads

    /** @return array<int,array> */
    public function get(): array
    {
        [$where, $params] = $this->whereSql();
        $rows = Db::select('SELECT ' . $this->selectSql() . ' FROM ' . Db::quoteIdent($this->table) . $where . $this->tailSql(), $params);
        return $this->attachEmbeds(array_map(fn($r) => Db::hydrate($this->table, $r), $rows));
    }

    public function first(): ?array
    {
        $this->limit = 1;
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    /** @return array{0:array,1:int} rows and exact total count (ignores limit / offset) */
    public function getWithCount(): array
    {
        return [$this->get(), $this->count()];
    }

    public function count(): int
    {
        [$where, $params] = $this->whereSql();
        return (int)Db::value('SELECT COUNT(*) FROM ' . Db::quoteIdent($this->table) . $where, $params);
    }

    /** Attach belongs-to rows requested through select('alias:table(cols)'). */
    private function attachEmbeds(array $rows): array
    {
        foreach ($this->embeds as $e) {
            $ids = array_values(array_unique(array_filter(array_column($rows, $e['fk']), fn($v) => $v !== null && $v !== '')));
            $related = [];
            if ($ids) {
                $cols = in_array('*', $e['columns'], true) ? '*' : implode(', ', array_map([Db::class, 'quoteIdent'], array_unique(array_merge(['id'], $e['columns']))));
                $sql = 'SELECT ' . $cols . ' FROM ' . Db::quoteIdent($e['table']) . ' WHERE `id` IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
                foreach (Db::select($sql, $ids) as $r) {
                    $r = Db::hydrate($e['table'], $r);
                    $related[(string)$r['id']] = in_array('*', $e['columns'], true) ? $r : array_intersect_key($r, array_flip($e['columns']));
                }
            }
            foreach ($rows as &$row) {
                $row[$e['alias']] = $related[(string)($row[$e['fk']] ?? '')] ?? null;
            }
            unset($row);
        }
        return $rows;
    }

    // ------------------------------------------------------------------ writes

    /** Insert one row (assoc) or many rows (list). Returns the inserted rows. */
    public function insert(array $data): array
    {
        if ($data === []) return [];
        $rows = array_is_list($data) ? $data : [$data];
        return $this->insertRows($rows, '');
    }

    /** Insert rows, silently skipping those that violate a unique key. Returns the rows actually inserted. */
    public function insertIgnore(array $data): array
    {
        if ($data === []) return [];
        $rows = array_is_list($data) ? $data : [$data];
        return $this->insertRows($rows, 'IGNORE');
    }

    /**
     * Insert or update on duplicate key (all provided columns are overwritten,
     * matching the "merge-duplicates" behaviour the models rely on).
     */
    public function upsert(array $data, ?string $onConflict = null): array
    {
        if ($data === []) return [];
        $rows = array_is_list($data) ? $data : [$data];
        $keys = $onConflict ? array_map('trim', explode(',', $onConflict)) : ['id'];
        return $this->insertRows($rows, 'UPSERT', $keys);
    }

    private function insertRows(array $rows, string $mode, array $conflictKeys = []): array
    {
        $kinds = Db::columns($this->table);
        $uuidPk = ($kinds['id'] ?? '') === 'uuid';
        $inserted = [];
        Db::transaction(function () use ($rows, $mode, $conflictKeys, $uuidPk, $kinds, &$inserted) {
            foreach ($rows as $row) {
                if (!is_array($row)) continue;
                if ($uuidPk && empty($row['id'])) {
                    $row['id'] = Db::uuid();
                }
                $cols = [];
                $params = [];
                foreach ($row as $c => $v) {
                    if (!isset($kinds[$c])) continue; // unknown column: ignore quietly like the old API never did, but safer
                    $cols[] = Db::quoteIdent($c);
                    $params[] = Db::dehydrate($this->table, (string)$c, $v);
                }
                if ($cols === []) continue;
                $sql = 'INSERT ' . ($mode === 'IGNORE' ? 'IGNORE ' : '') . 'INTO ' . Db::quoteIdent($this->table)
                    . ' (' . implode(', ', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
                if ($mode === 'UPSERT') {
                    $updates = [];
                    foreach (array_keys($row) as $c) {
                        if (!isset($kinds[$c]) || in_array($c, $conflictKeys, true)) continue;
                        $updates[] = Db::quoteIdent((string)$c) . ' = VALUES(' . Db::quoteIdent((string)$c) . ')';
                    }
                    $sql .= $updates ? ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates) : '';
                    if (!$updates) $sql = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $sql);
                }
                $st = Db::run($sql, $params);
                $affected = $st->rowCount();
                if ($mode === 'IGNORE' && $affected === 0) continue;
                if ($uuidPk) {
                    $inserted[] = ['id', $row['id']];
                } elseif (isset($kinds['id'])) {
                    $inserted[] = ['id', (int)Db::pdo()->lastInsertId()];
                } elseif ($conflictKeys && isset($row[$conflictKeys[0]])) {
                    $inserted[] = [$conflictKeys[0], $row[$conflictKeys[0]]];
                }
            }
        });
        return $this->fetchByKeys($inserted);
    }

    /** @param array<int,array{0:string,1:mixed}> $keys */
    private function fetchByKeys(array $keys): array
    {
        if ($keys === []) return [];
        $byCol = [];
        foreach ($keys as [$c, $v]) $byCol[$c][] = $v;
        $out = [];
        foreach ($byCol as $c => $vals) {
            $q = new self($this->table);
            $q->columns = $this->columns;
            $q->embeds = $this->embeds;
            $rows = $q->in($c, $vals)->get();
            $index = [];
            foreach ($rows as $r) $index[(string)$r[$c]] = $r;
            foreach ($vals as $v) {
                if (isset($index[(string)$v])) $out[] = $index[(string)$v];
            }
        }
        return $out;
    }

    public function update(array $data): array
    {
        if (!$this->where) {
            throw new DbException('Refusing to update without a filter.');
        }
        $kinds = Db::columns($this->table);
        $sets = [];
        $params = [];
        foreach ($data as $c => $v) {
            if (!isset($kinds[$c])) continue;
            $sets[] = Db::quoteIdent((string)$c) . ' = ?';
            $params[] = Db::dehydrate($this->table, (string)$c, $v);
        }
        if ($sets === []) return $this->get();
        // remember which rows match now: the update may change a filtered column
        $pk = isset($kinds['id']) ? 'id' : null;
        $ids = $pk ? array_column($this->selectKeys($pk), $pk) : [];
        [$where, $wparams] = $this->whereSql();
        Db::exec('UPDATE ' . Db::quoteIdent($this->table) . ' SET ' . implode(', ', $sets) . $where, array_merge($params, $wparams));
        if ($pk === null) return [];
        return $ids ? $this->fetchByKeys(array_map(fn($id) => [$pk, $id], $ids)) : [];
    }

    public function delete(): array
    {
        if (!$this->where) {
            throw new DbException('Refusing to delete without a filter.');
        }
        $rows = $this->get();
        [$where, $params] = $this->whereSql();
        Db::exec('DELETE FROM ' . Db::quoteIdent($this->table) . $where, $params);
        return $rows;
    }

    private function selectKeys(string $pk): array
    {
        [$where, $params] = $this->whereSql();
        return Db::select('SELECT ' . $this->col($pk) . ' AS ' . Db::quoteIdent($pk) . ' FROM ' . Db::quoteIdent($this->table) . $where, $params);
    }

    /** Split on commas that are not inside parentheses. */
    private function splitTopLevel(string $s): array
    {
        $out = [];
        $depth = 0;
        $cur = '';
        foreach (str_split($s) as $ch) {
            if ($ch === '(') $depth++;
            elseif ($ch === ')') $depth--;
            if ($ch === ',' && $depth === 0) {
                $out[] = $cur;
                $cur = '';
                continue;
            }
            $cur .= $ch;
        }
        $out[] = $cur;
        return $out;
    }
}
