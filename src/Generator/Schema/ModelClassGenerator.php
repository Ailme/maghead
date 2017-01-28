<?php

namespace Maghead\Schema\Factory;

use ClassTemplate\ClassFile;
use Maghead\Schema\DeclareSchema;

class ModelClassGenerator
{
    public static function create(DeclareSchema $schema)
    {
        $cTemplate = new ClassFile($schema->getModelClass());
        $cTemplate->extendClass('\\'.$schema->getBaseModelClass());

        return $cTemplate;
    }
}
