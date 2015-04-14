<?php
namespace LazyRecord\Testing;
use LazyRecord\ConnectionManager;
use LazyRecord\ConfigLoader;
use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\ClassUtils;
use LazyRecord\SeedBuilder;
use LazyRecord\Result;
use LazyRecord\SqlBuilder\SqlBuilder;
use LazyRecord\Testing\BaseTestCase;
use PHPUnit_Framework_TestCase;

abstract class ModelTestCase extends BaseTestCase
{
    public $schemaHasBeenBuilt = false;

    public $schemaClasses = array( );

    public function setUp()
    {
        $annnotations = $this->getAnnotations();

        $connManager = ConnectionManager::getInstance();
        $dataSourceConfig = self::createDataSourceConfig($this->driver);
        if ($dataSourceConfig) {
            $connManager->addDataSource('default', $dataSourceConfig);
        } else {
            $this->markTestSkipped("{$this->driver} database configuration is missing.");
        }

        try {
            $dbh = $connManager->getConnection('default');
        } catch (PDOException $e) {
            $this->markTestSkipped('Can not connect to database, test skipped: ' . $e->getMessage() );
            return;
        }

        $driver = ConnectionManager::getInstance()->getQueryDriver('default');
        ok($driver,'QueryDriver object OK');

        // Rebuild means rebuild the database for new tests
        $rebuild = true;
        $basedata = true;
        if (isset($annnotations['method']['rebuild'][0]) && $annnotations['method']['rebuild'][0] == 'false') {
            $rebuild = false;
        }
        if (isset($annnotations['method']['basedata'][0]) && $annnotations['method']['basedata'][0] == 'false') {
            $basedata = false;
        }

        if ($rebuild) {
            $builder = SqlBuilder::create($driver , array('rebuild' => true));
            ok($builder, 'SqlBuilder OK');

            $schemas = ClassUtils::schema_classes_to_objects( $this->getModels() );
            foreach( $schemas as $schema ) {
                $sqls = $builder->build($schema);
                ok($sqls);
                foreach( $sqls as $sql ) {
                    $dbh->query( $sql );
                }
            }
            if ($basedata) {
                $runner = new SeedBuilder($this->config, $this->logger);
                foreach($schemas as $schema) {
                    $runner->buildSchemaSeeds($schema);
                }
                if ($scripts = $this->config->getSeedScripts()) {
                    foreach($scripts as $script) {
                        $runner->buildScriptSeed($script);
                    }
                }
            }
        }
    }

    /**
     * Test cases
     */
    public function testClasses()
    {
        foreach ($this->getModels() as $class ) {
            class_ok($class);
        }
    }

}



