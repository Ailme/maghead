<?php
namespace AuthorBooks\Tests;
use SQLBuilder\Raw;
use LazyRecord\Testing\ModelProfileTestCase;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\BookSchema;
use AuthorBooks\Model\Tag;
use AuthorBooks\Model\TagSchema;
use DateTime;
use XHProfRuns_Default;

/**
 * @group profile
 */
class TagModelProfileTest extends ModelProfileTestCase
{
    public function getModels()
    {
        return [new TagSchema];
    }

    /**
     * @group profile
     * @rebuild true
     */
    public function testProfileCodeGenOverrideCreate()
    {
        $tag = new Tag;
        for ($i = 0 ; $i < $this->N; $i++) {
            $tag->create(array('title' => uniqid()));
        }
    }
}
