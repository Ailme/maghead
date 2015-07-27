<?php
namespace LazyRecord\Command\DbCommand;
use CLIFramework\Command;
use LazyRecord\Command\BaseCommand;
use LazyRecord\ConfigLoader;
use LazyRecord\DSN\DSNParser;
use SQLBuilder\Driver\PDODriverFactory;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Query\CreateDatabaseQuery;
use SQLBuilder\Universal\Query\DropDatabaseQuery;
use PDO;

class DropCommand extends BaseCommand
{

    public function brief() 
    {
        return 'create database bases on the current config.';
    }

    public function execute()
    {
        $configLoader = $this->getConfigLoader(true);
        $dsId = $this->getCurrentDataSourceId();
        $ds = $configLoader->getDataSource($dsId);

        $dsnParser = new DSNParser;
        $dsn = $dsnParser->parse($ds['dsn']);

        $dbName = $dsn->getAttribute('dbname');

        $dsn->removeAttribute('dbname');

        $this->logger->debug("Connection DSN: " . $dsn);

        $pdo = new PDO($dsn, @$ds['user'], @$ds['pass'], @$ds['connection_options']);

        $q = new DropDatabaseQuery($dbName);
        $q->ifExists();


        // Create query Driver object
        $queryDriver = PDODriverFactory::create($pdo);
        $sql = $q->toSql($queryDriver, new ArgumentArray);
        $this->logger->info($sql);

        if ($pdo->query($sql) === false) {
            list($statusCode, $errorCode, $message) = $pdo->errorInfo();
            $this->logger->error("$statusCode:$errorCode $message");
            return false;
        }
        $this->logger->info('Database dropped successfully.');
    }

}



