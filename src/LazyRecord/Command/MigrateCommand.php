<?php
namespace LazyRecord\Command;
use CLIFramework\Command;

class MigrateCommand extends Command
{

    public function options($opts) 
    {
        $opts->add('new:','create new migration script.');
        $opts->add('diff:','use schema diff to generate script automatically.');
        $opts->add('status','show current migration status.');
        $opts->add('up','upgrade');
        $opts->add('down','downgrade');
        $opts->add('D|data-source:','data source id.');
    }

    public function execute() 
    {
        $optNew = $this->options->new;
        $optDiff = $this->options->diff;
        $optUp = $this->options->up;
        $optDown = $this->options->down;
        $optStatus = $this->options->status;
        $dsId = $this->options->{'data-source'} ?: 'default';

        CommandUtils::set_logger($this->logger);
        CommandUtils::init_config_loader();

        if( $optNew ) {
            $generator = new \LazyRecord\Migration\MigrationGenerator('db/migrations');
            $this->logger->info( "Creating migration script for '" . $optNew . "'" );
            list($class,$path) = $generator->generate($optNew);
            $this->logger->info( "Migration script is generated: $path" );
        }
        elseif( $optDiff ) {
            $generator = new \LazyRecord\Migration\MigrationGenerator('db/migrations');

            $this->logger->info( "Loading schema objects..." );
            $finder = new \LazyRecord\Schema\SchemaFinder;
            $finder->find();

            $this->logger->info( "Creating migration script from diff" );
            list($class,$path) = $generator->generateWithDiff('DiffMigration',$dsId,$finder->getSchemas());
            $this->logger->info( "Migration script is generated: $path" );

        }
        elseif( $optStatus ) {
            $runner = new \LazyRecord\Migration\MigrationRunner($dsId);
            $runner->load('db/migrations');
            $scripts = $runner->getMigrationScripts();
            $this->logger->info("Found " . count($scripts) . " migrations to be executed.");
            foreach( $scripts as $script ) {
                $this->logger->info( '- ' . $script , 1 );
            }
        }
        elseif( $optUp ) {
            // XXX: record the latest ran migration id,
            $runner = new \LazyRecord\Migration\MigrationRunner($dsId);
            $runner->load('db/migrations');
            $this->logger->info('Running migration scripts to upgrade...');
            $runner->runUpgrade();
            $this->logger->info('Done.');
        }

    }
}
