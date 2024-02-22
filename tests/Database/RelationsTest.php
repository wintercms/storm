<?php

namespace Winter\Storm\Tests\Database;

use Winter\Storm\Database\Model;
use Winter\Storm\Tests\Database\Fixtures\Author;
use Winter\Storm\Tests\Database\Fixtures\MigratesForTest;
use Winter\Storm\Tests\Database\Fixtures\Phone;
use Winter\Storm\Tests\DbTestCase;

/**
 * This tests the underlying methods for relationship management.
 *
 * The test cases that were previously here that tested related models directly have been moved into the `Relations`
 * sub-folder.
 */
class RelationsTest extends DbTestCase
{
    use MigratesForTest;

    public function testGetRelations()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $phone = Phone::create(['number' => '0404040404', 'author_id' => $author->id]);
        Model::reguard();

        $authorModel = Author::find($author->id);
        $this->assertEmpty($authorModel->getRelations());

        $authorModel = Author::with('phone')->find($author->id);
        $this->assertNotEmpty($authorModel->getRelations());
        $this->assertArrayHasKey('phone', $authorModel->getRelations());

        $authorModel = Author::with('contactNumber')->find($author->id);
        $this->assertNotEmpty($authorModel->getRelations());
        $this->assertArrayHasKey('contactNumber', $authorModel->getRelations());
    }

    public function testGetRelationMethods()
    {
        $author = new Author();
        $this->assertCount(6, $author->getRelationMethods());
        $this->assertEquals([
            'contactNumber',
            'messages',
            'scopes',
            'executiveAuthors',
            'info',
            'auditLogs',
        ], $author->getRelationMethods());
    }
}
