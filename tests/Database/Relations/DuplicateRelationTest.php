<?php

namespace Winter\Storm\Tests\Database\Relations;

use Winter\Storm\Database\Model;
use Winter\Storm\Exception\SystemException;
use Winter\Storm\Tests\Database\Fixtures\Author;
use Winter\Storm\Tests\Database\Fixtures\DuplicateRelationNote;
use Winter\Storm\Tests\DbTestCase;

class DuplicateRelationTest extends DbTestCase
{
    /** Note that we cannot stop people getting the method-style relation in this instance */
    public function testMethodRelationWhenPropertyRelationExists()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $note = DuplicateRelationNote::create(['note' => 'This is a note']);
        Model::reguard();

        $note->author()->associate($author);
        $note->save();

        $this->assertEquals($author->id, $note->author_id);
    }

    public function testMethodPropertyWhenMethodRelationExists()
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessageMatches('/Relation "author" in model "' . preg_quote(DuplicateRelationNote::class) . '" is defined both/');

        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $note = DuplicateRelationNote::create(['note' => 'This is a note']);
        Model::reguard();

        $note->author = $author;
        $note->save();

        $this->assertEquals($author->id, $note->author_id);
    }

    public function testGetRelationDefinitionWhenBothExist()
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessageMatches('/Relation "author" in model "' . preg_quote(DuplicateRelationNote::class) . '" is defined both/');

        $note = new DuplicateRelationNote;
        $relation = $note->getRelationDefinition('author');
    }
}
