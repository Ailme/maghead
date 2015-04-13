<?php
namespace LazyRecord\Schema;
use CLIFramework\Logger;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\DynamicSchemaDeclare;
use LazyRecord\Schema\MixinSchemaDeclare;
use LazyRecord\ConfigLoader;
use LazyRecord\ClassUtils;

use ReflectionObject;
use ReflectionMethod;

class SchemaUtils
{
    static public function printSchemaClasses(Logger $logger, array $classes) {
        $logger->info('Found schema classes:');
        foreach( $classes as $class ) {
            $logger->info($logger->formatter->format($class, 'green') , 1);
        }
    }

    /**
     * Filter non-dynamic schema declare classes.
     *
     * @param string[] $classes class list.
     */
    static public function filterBuildableSchemas(array $schemas)
    {
        $list = array();
        foreach ($schemas as $schema) {
            // skip abstract classes.
            if (   $schema instanceof DynamicSchemaDeclare 
                || $schema instanceof MixinSchemaDeclare 
                || (! $schema instanceof SchemaDeclare && ! $schema instanceof DeclareSchema)
            ) { continue; }

            $rf = new ReflectionObject($schema);
            if ($rf->isAbstract()) {
                continue;
            }
            $list[] = $schema;
        }
        return $list;
    }


    /**
     *
     * @param ConfigLoader $loader
     * @param Logger $logger
     */
    static public function findSchemasByConfigLoader(ConfigLoader $loader, Logger $logger = null)
    {
        $finder = new SchemaFinder;
        $finder->paths = $loader->getSchemaPaths();
        $finder->find();

        // load class from class map
        if ($classMap = $loader->getClassMap()) {
            foreach ($classMap as $file => $class) {
                if (! is_integer($file) && is_string($file)) {
                    require $file;
                }
            }
        }
        return $finder->getSchemas();
    }


    /**
     * Returns schema objects
     *
     * @return array schema objects
     */
    static public function findSchemasByArguments(ConfigLoader $loader, array $args, Logger $logger = null)
    {
        if (count($args) && ! file_exists($args[0])) {
            $classes = array();
            // it's classnames
            foreach ($args as $class) {
                // call class loader to load
                if (class_exists($class,true)) {
                    $classes[] = $class;
                } else {
                    if ($logger) {
                        $logger->warn( "$class not found." );
                    } else {
                        echo ">>> $class not found.\n";
                    }
                }
            }
            return ClassUtils::schema_classes_to_objects($classes);
        } else {
            $finder = new SchemaFinder;
            if (count($args) && file_exists($args[0])) {
                $finder->setPaths($args);
                foreach ($args as $file) {
                    if (is_file($file) ) {
                        require_once $file;
                    }
                }
            } 
            // load schema paths from config
            else if( $paths = $loader->getSchemaPaths() ) {
                $finder->setPaths($paths);
            }
            $finder->find();

            // load class from class map
            if ($classMap = $loader->getClassMap()) {
                foreach ($classMap as $file => $class ) {
                    if (! is_integer($file) && is_string($file)) {
                        require $file;
                    }
                }
            }
            return $finder->getSchemas();
        }
    }
}



