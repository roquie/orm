<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Config;

use Spiral\Core\Container\Autowire;
use Spiral\Core\InjectableConfig;
use Spiral\ORM\Exception\ConfigException;
use Spiral\ORM\Loader;
use Spiral\ORM\Relation;

class RelationConfig extends InjectableConfig
{
    public const LOADER   = 'loader';
    public const RELATION = 'relation';
    public const SCHEMA   = 'schema';

    protected $config = [];

    public function getLoader($type): Autowire
    {
        if (!isset($this->config[$type][self::LOADER])) {
            throw new ConfigException("Unable to get relation loader `{$type}`.");
        }

        return new Autowire($this->config[$type][self::LOADER]);
    }

    public function getRelation($type): Autowire
    {
        if (!isset($this->config[$type][self::RELATION])) {
            throw new ConfigException("Unable to get relation `{$type}`.");
        }

        return new Autowire($this->config[$type][self::RELATION]);
    }

    public function getSchema($type): Autowire
    {
        if (!isset($this->config[$type][self::SCHEMA])) {
            throw new ConfigException("Unable to get relation schema `{$type}`.");
        }

        return new Autowire($this->config[$type][self::SCHEMA]);
    }

    public static function createDefault()
    {
        return new static([
            Relation::HAS_ONE    => [
                RelationConfig::LOADER   => Loader\Relation\HasOneLoader::class,
                RelationConfig::RELATION => Relation\HasOneRelation::class
            ],
            Relation::BELONGS_TO => [
                RelationConfig::LOADER   => Loader\Relation\BelongsToLoader::class,
                RelationConfig::RELATION => Relation\BelongsToRelation::class
            ],
            Relation::REFERS_TO  => [
                RelationConfig::LOADER   => Loader\Relation\BelongsToLoader::class,
                RelationConfig::RELATION => Relation\RefersToRelation::class
            ],
            Relation::HAS_MANY   => [
                RelationConfig::LOADER   => Loader\Relation\HasManyLoader::class,
                RelationConfig::RELATION => Relation\HasManyRelation::class
            ]
        ]);
    }
}