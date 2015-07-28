<?php
namespace LazyRecord;
use CLIFramework\Application;

class Console extends Application
{
    const name = 'LazyRecord';
    const VERSION = "2.1.6";

    public function brief()
    {
        return 'LazyRecord ORM';
    }

    public function init()
    {
        parent::init();

        /**
         * Command for initialize related file structure
         */
        $this->command('init',    'LazyRecord\Command\InitCommand');

        /**
         * Command for building config file.
         */
        $this->command('build-conf', 'LazyRecord\\Command\\BuildConfCommand');
        $this->command('conf',       'LazyRecord\\Command\\BuildConfCommand');

        /**
         * schema command.
         */
        $this->command('schema'); // the schema command builds all schema files and shows a diff after building new schema

        // XXX: move list to the subcommand of schema command, eg:
        //    $ lazy schema list
        //    $ lazy schema build
        //
        $this->command('list-schema'    , 'LazyRecord\\Command\\ListSchemaCommand');
        $this->command('build-schema'   , 'LazyRecord\\Command\\BuildSchemaCommand');
        $this->command('clean-schema'   , 'LazyRecord\\Command\\CleanSchemaCommand');

        $this->command('build-basedata' , 'LazyRecord\\Command\\BuildBaseDataCommand');

        $this->command('sql'            , 'LazyRecord\\Command\\BuildSqlCommand');

        $this->command('diff');
        $this->command('migrate');
        $this->command('meta');
        $this->command('version');
        $this->command('db');
        $this->command('data-source');
    }

    public static function getInstance() 
    {
        static $self;
        if( $self )
            return $self;
        return $self = new self;
    }
}
