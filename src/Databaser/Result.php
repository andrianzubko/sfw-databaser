<?php

namespace SFW\Databaser;

/**
 * Databaser result handling.
 */
class Result implements \IteratorAggregate
{
    /**
     *
     */
    public int $affectedRows = 0;

    /**
     *
     */
    public int $numRows = 0;

    /**
     *
     */
    protected array $rows = [];

    /**
     *
     */
    protected int $i = 0;

    /**
     *
     */
    protected array $names = [];

    /**
     *
     */
    public function __construct(\PDOStatement|false $result)
    {
        if ($result === false) {
            return;
        }

        do {
            $this->affectedRows = $result->rowCount();

            $this->rows = $result->fetchAll(\PDO::FETCH_NUM);

            $this->names = [];

            if ($this->rows) {
                foreach ($this->rows[0] as $i => $value) {
                    $meta = $result->getColumnMeta($i);

                    $this->names[$i] = $meta['name'] ?? (string) $i;
                }
            }
        } while ($result->nextRowset());

        $this->numRows = count($this->rows);
    }

    /**
     *
     */
    public function fetchAll(bool $num = false, bool $assoc = false, bool $both = false): array
    {
        $rows = [];

        if ($both || $num && $assoc) {
            foreach ($this->rows as $i => $row) {
                foreach ($row as $j => $value) {
                    $rows[$i][$this->names[$j]] = $value;

                    $rows[$i][$j] = $value;
                }
            }
        } elseif ($num) {
            $rows = $this->rows;
        } else {
            foreach ($this->rows as $i => $row) {
                foreach ($row as $j => $value) {
                    $rows[$i][$this->names[$j]] = $value;
                }
            }
        }

        return $rows;
    }

    /**
     *
     */
    public function fetchObject(): object|false
    {
        $assoc = $this->fetchAssoc();

        return $assoc === false ? false : (object) $assoc;
    }

    /**
     *
     */
    public function fetchAssoc(): array|false
    {
        $row = $this->rows[$this->i++] ?? false;

        if ($row === false) {
            return false;
        }

        $assoc = [];

        foreach ($row as $j => $value) {
            $assoc[$this->names[$j]] = $value;
        }

        return $assoc;
    }

    /**
     *
     */
    public function fetchRow(): array|false
    {
        return $this->rows[$this->i++] ?? false;
    }

    /**
     *
     */
    public function fetchColumn(int $column = 0): mixed
    {
        $row = $this->rows[$this->i++] ?? false;

        return $row === false ? false : $row[$column] ?? false;
    }

    /**
     *
     */
    public function seek(int $i = 0): void
    {
        $this->i = $i;
    }

    /**
     *
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->fetchAll());
    }
}
