<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Traits\ReferenceTrait;

/**
 * State carries meta information about all load entities, including original set of data,
 * relations, state and number of active references (in cases when entity become unclaimed).
 */
final class State implements StateInterface
{
    use ReferenceTrait;

    // Different entity states in a pool
    public const NEW              = 0;
    public const LOADED           = 1;
    public const SCHEDULED_INSERT = 2;
    public const SCHEDULED_UPDATE = 3;
    public const SCHEDULED_DELETE = 4;

    /** @var int */
    private $state;

    /** @var array */
    private $data;

    private $relations = [];

    /**
     * Relations already visited during dependency resolution.
     *
     * @var array
     */
    private $visited = [];


    /**
     * @invisible
     * @var array
     */
    private $handlers = [];

    /**
     * @param int   $state
     * @param array $data
     */
    public function __construct(int $state, array $data)
    {
        $this->state = $state;
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function onChange(callable $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * @param int $state
     */
    public function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    public function setData(array $data)
    {
        $this->data = $data + $this->data;
        foreach ($this->handlers as $handler) {
            call_user_func($handler, $this);
        }
    }

    public function getKey(string $key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }


    // todo: store original set of relations (YEEEEAH BOYYYY)
    public function setRelation(string $name, $context)
    {
        $this->relations[$name] = $context;
        unset($this->data[$name]);
    }

    public function getRelation(string $name)
    {
        return $this->relations[$name] ?? null;
    }


    /**
     * Return true if relation branch was already visited.
     *
     * @param string $branch
     * @return bool
     */
    public function visited(string $branch): bool
    {
        return isset($this->visited[$branch]);
    }

    /**
     * Indicate that relation branch has been visited.
     *
     * @param string $branch
     */
    public function markVisited(string $branch)
    {
        $this->visited[$branch] = true;
    }

    /**
     * Reset all visited branches.
     */
    public function resetVisited()
    {
        $this->visited = [];
    }

    public function __destruct()
    {
        $this->data = [];
        $this->handlers = [];
        $this->relations = [];
        $this->visited = [];
    }
}