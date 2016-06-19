<?php

namespace LazyRecord\Command;

use CLIFramework\Command;
use LazyRecord\ConfigLoader;

class InitConfCommand extends Command
{
    public function options($opts)
    {
        $opts->add('driver:', 'pdo driver type');
        $opts->add('database:', 'database name');
        $opts->add('username:', 'username');
        $opts->add('password:', 'password');
    }

    public function execute()
    {
        $logger = $this->getLogger();

        $configFile = 'db/config/database.yml';
        if (file_exists($configFile)) {
            $logger->info("Config file $configFile already exists.");

            return;
        }

        $driver = $this->options->driver ?: $this->ask('Database driver [sqlite]', array('sqlite', 'pgsql', 'mysql', null)) ?: 'sqlite';
        $dbName = $this->options->database ?: $this->ask('Database name [:memory:]') ?: ':memory:';

        $logger->info("Using $driver driver");
        $logger->info("Using database $dbName");
        $logger->info("Using DSN: $driver:$dbName");

        $user = '';
        $password = '';
        if ($driver != 'sqlite') {
            $user = $this->options->username ?: $this->ask('Database user');
            $password = $this->options->password ?: $this->ask('Database password');
        }
        $logger->info('Creating config file skeleton...');
        $content = <<<EOS
---
bootstrap:
  - tests/bootstrap.php
schema:
#  Customize your schema class loader
#
#  loader: custom_schema_loader.php

#  Customize your schema paths
#  paths:
#    - tests
data_source:
  default: master
  nodes:
    master:
      driver: $driver
      database: $dbName
      user: $user
      pass: $password
EOS;
        if (file_put_contents($configFile, $content) !== false) {
            $logger->info("Config file is generated: $configFile");
            $logger->info('Please run build-conf to compile php format config file.');
        }


        $this->logger->info("Building config from $configFile");
        $dir = dirname($configFile);
        ConfigLoader::compile($configFile, true);

        // make master config link
        $loader = ConfigLoader::getInstance();
        $cleanup = [$loader->symbolFilename, '.lazy.php', '.lazy.yml'];
        foreach ($cleanup as $symlink) {
            if (file_exists($symlink)) {
                $this->logger->debug('Cleaning up symbol link: '.$symlink);
                unlink($symlink);
            }
        }

        $this->logger->info('Creating symbol link: '.$loader->symbolFilename.' -> '.$configFile);
        if (cross_symlink($configFile, $loader->symbolFilename) === false) {
            $this->logger->error('Config linking failed.');
        }
        $this->logger->info('Done');
    }
}
