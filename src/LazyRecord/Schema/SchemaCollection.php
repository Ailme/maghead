<?php
namespace LazyRecord\Schema;
use ArrayAccess;
use IteratorAggregate;
use Countable;
use ArrayIterator;
use LazyRecord\Schema\DeclareSchema;
use InvalidArgumentException;

class SchemaCollection implements IteratorAggregate, ArrayAccess, Countable
{
    protected $schemas = array();

    public function __construct(array $args) 
    {
        $this->schemas = $args;
    }

    public function filter(callable $cb) {
        return new self(array_filter($this->schemas, $cb));
    }

    public function map(callable $cb) {
        $this->schemas = array_map($this->schemas, $cb);
    }

    public function evaluate() 
    {
        $schemas = array_map(function($a) {
            if (is_string($a)) {
                return new $a;
            } elseif (is_object($a)) {
                return $a;
            } else {
                throw new InvalidArgumentException("Invalid schema class argument");
            }
            return $a;
        }, $this->schemas);
        return new self($schemas);
    }

    public function expandDependency()
    {
        $expands = array();
        foreach($this->schemas as $schema) {
            $expands = array_merge($expands, $this->expandSchemaDependency($schema));
        }
        $expands = array_unique($expands);
        return new self($expands);
    }

    public function expandSchemaDependency(DeclareSchema $schema) {
        $expands = array();
        $refs = $schema->getReferenceSchemas();
        foreach($refs as $refClass => $v) {
            // $refSchema = new $refClass;
            // $expand = array_merge($expand, $this->expandSchemaDependency($refSchema), array($refClass));
            $expands[] = $refClass;
        }
        $expands[] = get_class($schema);
        return $expands;
    }


    public function declareable() {
        return $this->filter(function($schema) {
            return is_subclass_of($schema, 'LazyRecord\Schema\DeclareSchema', true);
        });
    }

    /*
        foreach ($classes as $class) {
        }
    */

    public function getSchemas() 
    {
        return $this->schemas;
    }

    public function getClasses() 
    {
        return array_map(function($a) { return get_class($a); }, $this->schemas);
    }

    public function getBuildableSchemas() 
    {
        $list = array();
        foreach ($this->schemas as $schema) {
            // skip abstract classes.
            if (
              ! is_subclass_of($schema, 'LazyRecord\Schema\DeclareSchema',true)
              || is_a($schema, 'LazyRecord\Schema\DynamicSchemaDeclare',true)
              || is_a($schema, 'LazyRecord\Schema\MixinDeclareSchema',true)
              || is_a($schema, 'LazyRecord\Schema\MixinSchemaDeclare',true)
              || is_subclass_of($schema, 'LazyRecord\Schema\MixinDeclareSchema',true)
            ) { 
                continue; 
            }

            // Skip abstract class files...
            $rf = new ReflectionClass($schema);
            if ($rf->isAbstract()) {
                continue;
            }
            $list[] = $schema;
        }
        return $list;
    }



    static public function evaluateArray(array $classes) 
    {
        $schemas = array_map(function($a) {
            return is_string($a) ? new $a : $a;
        }, $classes);
        return new self($schemas);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->schemas);
    }

    public function offsetSet($name,$value)
    {
        if ($name) {
            $this->schemas[$name] = $value;
        } else {
            $this->schemas[] = $value;
        }
    }
    
    public function offsetExists($name)
    {
        return isset($this->schemas[ $name ]);
    }
    
    public function offsetGet($name)
    {
        return $this->schemas[ $name ];
    }
    
    public function offsetUnset($name)
    {
        unset($this->schemas[$name]);
    }

    public function count()
    {
        return count($this->schemas);
    }
}



