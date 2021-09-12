<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\RootNode;
use Cycle\ORM\Relation;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\Database\Query\SelectQuery;

/**
 * Wrap JoinableLoader with subquery
 */
final class SubQueryLoader extends JoinableLoader
{
    protected array $options = [
        'load' => true,
        'using' => null,
        'as' => null,
    ];

    private JoinableLoader $loader;

    public function __construct(ORMInterface $orm, JoinableLoader $loader, array $options)
    {
        parent::__construct($orm, $loader->name, $loader->getTarget(), $loader->schema);

        $this->loader = $loader->withContext($this, [
            'method' => self::SUBQUERY,
        ]);
        $this->options = $options;
        $this->options['as'] = 'sq_' . $options['as'];
        $this->columns = $loader->columns;
        $this->parent = $loader->parent;
    }

    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        $alias = $this->options['as'];
        $lAlias = $this->loader->getAlias();
        $queryColumns = $query->getColumns();

        $body = $this->loader->getSource()->getDatabase()->select()->from(
            sprintf('%s AS %s', $this->loader->getSource()->getTable(), $lAlias)
        )->columns($queryColumns);
        $body = $this->loader->configureQuery($body);
        $bodyColumns = array_slice($body->getColumns(), count($queryColumns));
        $body = $body->columns($bodyColumns);

        $aliases = [];
        // Move columns to parent query
        foreach ($bodyColumns as $column) {
            preg_match('/^([^\\s]+)\\.([^\\s]+) AS ([^\\s]+)$/i', $column, $matches);
            [, $table, $column, $as] = $matches;
            $queryColumns[] = "$alias.$as AS $as";
            if ($table === $lAlias) {
                $aliases[$column] = $as;
            }
        }

        $query = $query->columns($queryColumns);
        $parentKeys = (array)$this->schema[Relation::INNER_KEY];
        $parentPrefix = $this->parent->getAlias() . '.';
        $on = [];
        foreach ((array)$this->schema[Relation::OUTER_KEY] as $i => $key) {
            $field = $alias . '.' . $aliases[$this->fieldAlias($key)];
            $on[$field] = $parentPrefix . $this->parent->fieldAlias($parentKeys[$i]);
        }
        return $query->join($this->getJoinMethod(), $body, $alias)->on($on);
    }

    protected function initNode(): RootNode
    {
        throw new \RuntimeException('You shouldn\'t run this method.');
    }
}
