<?php

namespace Winter\Storm\Tests\Database\Concerns;

use Winter\Storm\Tests\Database\Fixtures\Author;
use Winter\Storm\Tests\Database\Fixtures\Country;
use Winter\Storm\Tests\Database\Fixtures\EventLog;
use Winter\Storm\Tests\Database\Fixtures\Meta;
use Winter\Storm\Tests\Database\Fixtures\Phone;
use Winter\Storm\Tests\Database\Fixtures\Post;
use Winter\Storm\Tests\Database\Fixtures\Role;
use Winter\Storm\Tests\Database\Fixtures\Tag;
use Winter\Storm\Tests\Database\Fixtures\User;
use Winter\Storm\Tests\DbTestCase;

class HasRelationshipsTest extends DbTestCase
{
    public function testHasRelation()
    {
        $author = new Author();

        // Array style
        $this->assertTrue($author->hasRelation('user'));
        $this->assertTrue($author->hasRelation('country'));
        $this->assertTrue($author->hasRelation('posts'));
        $this->assertTrue($author->hasRelation('phone'));
        $this->assertTrue($author->hasRelation('roles'));
        $this->assertTrue($author->hasRelation('event_log'));
        $this->assertTrue($author->hasRelation('meta'));
        $this->assertTrue($author->hasRelation('tags'));

        // Laravel style
        $this->assertTrue($author->hasRelation('contactNumber'));
        $this->assertTrue($author->hasRelation('messages'));
        $this->assertTrue($author->hasRelation('scopes'));
        $this->assertTrue($author->hasRelation('executiveAuthors'));
        $this->assertTrue($author->hasRelation('info'));
        $this->assertTrue($author->hasRelation('auditLogs'));

        $this->assertFalse($author->hasRelation('invalid'));
    }

    public function testGetRelationType()
    {
        $author = new Author();

        // Array style
        $this->assertEquals('belongsTo', $author->getRelationType('user'));
        $this->assertEquals('belongsTo', $author->getRelationType('country'));
        $this->assertEquals('hasMany', $author->getRelationType('posts'));
        $this->assertEquals('hasOne', $author->getRelationType('phone'));
        $this->assertEquals('belongsToMany', $author->getRelationType('roles'));
        $this->assertEquals('morphMany', $author->getRelationType('event_log'));
        $this->assertEquals('morphOne', $author->getRelationType('meta'));
        $this->assertEquals('morphToMany', $author->getRelationType('tags'));

        // Laravel style
        $this->assertEquals('hasOne', $author->getRelationType('contactNumber'));
        $this->assertEquals('hasMany', $author->getRelationType('messages'));
        $this->assertEquals('belongsToMany', $author->getRelationType('scopes'));
        $this->assertEquals('belongsToMany', $author->getRelationType('executiveAuthors'));
        $this->assertEquals('morphOne', $author->getRelationType('info'));
        $this->assertEquals('morphMany', $author->getRelationType('auditLogs'));

        $this->assertNull($author->getRelationType('invalid'));
    }

    public function testGetRelationDefinition()
    {
        $author = new Author();

        // Array style
        $this->assertEquals([User::class, 'delete' => true], $author->getRelationDefinition('user'));
        $this->assertEquals([Country::class], $author->getRelationDefinition('country'));
        $this->assertEquals([Post::class], $author->getRelationDefinition('posts'));
        $this->assertEquals([Phone::class], $author->getRelationDefinition('phone'));
        $this->assertEquals([
            Role::class,
            'table' => 'database_tester_authors_roles'
        ], $author->getRelationDefinition('roles'));
        $this->assertEquals([EventLog::class, 'name' => 'related', 'delete' => true, 'softDelete' => true], $author->getRelationDefinition('event_log'));
        $this->assertEquals([Meta::class, 'name' => 'taggable'], $author->getRelationDefinition('meta'));
        $this->assertEquals([
            Tag::class,
            'name'  => 'taggable',
            'table' => 'database_tester_taggables',
            'pivot' => ['added_by']
        ], $author->getRelationDefinition('tags'));

        // Laravel style
        $this->assertEquals([
            Phone::class,
            'key' => 'author_id',
            'otherKey' => 'id',
            'delete' => false,
            'push' => true,
        ], $author->getRelationDefinition('contactNumber'));
        $this->assertEquals([
            Post::class,
            'key' => 'author_id',
            'otherKey' => 'id',
            'delete' => false,
            'push' => true,
        ], $author->getRelationDefinition('messages'));
        $this->assertEquals([
            Role::class,
            'table' => 'database_tester_authors_roles',
            'key' => 'author_id',
            'otherKey' => 'id',
            'push' => true,
        ], $author->getRelationDefinition('scopes'));
        $this->assertEquals([
            Meta::class,
            'type' => 'taggable_type',
            'id' => 'taggable_id',
            'delete' => false,
            'push' => true,
        ], $author->getRelationDefinition('info'));
        $this->assertEquals([
            EventLog::class,
            'type' => 'related_type',
            'id' => 'related_id',
            'delete' => true,
            'push' => true,
        ], $author->getRelationDefinition('auditLogs'));
        $this->assertEquals([
            Tag::class,
            'table' => 'database_tester_taggables',
            'key' => 'taggable_id',
            'otherKey' => 'tag_id',
            'parentKey' => 'id',
            'relatedKey' => 'id',
            'inverse' => false,
            'push' => true,
            'pivot' => ['added_by']
        ], $author->getRelationDefinition('labels'));

        $this->assertNull($author->getRelationDefinition('invalid'));
    }
}
