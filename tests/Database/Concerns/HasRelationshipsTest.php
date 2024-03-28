<?php

namespace Winter\Storm\Tests\Database\Concerns;

use Winter\Storm\Database\Relations\BelongsTo;
use Winter\Storm\Database\Relations\BelongsToMany;
use Winter\Storm\Database\Relations\HasMany;
use Winter\Storm\Database\Relations\HasOne;
use Winter\Storm\Database\Relations\MorphMany;
use Winter\Storm\Database\Relations\MorphOne;
use Winter\Storm\Database\Relations\MorphToMany;
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

    public function testGetDefinedRelations()
    {
        $author = new Author();
        $defined = $author->getDefinedRelations();

        $this->assertCount(16, $defined);
        foreach ([
            'user',
            'country',
            'user_soft',
            'posts',
            'phone',
            'roles',
            'event_log',
            'meta',
            'tags',
            'contactNumber',
            'messages',
            'scopes',
            'executiveAuthors',
            'info',
            'labels',
            'auditLogs',
        ] as $expected) {
            $this->assertArrayHasKey($expected, $defined);
        }

        $this->assertInstanceOf(BelongsTo::class, $defined['user']);
        $this->assertInstanceOf(BelongsTo::class, $defined['country']);
        $this->assertInstanceOf(BelongsTo::class, $defined['user_soft']);
        $this->assertInstanceOf(HasMany::class, $defined['posts']);
        $this->assertInstanceOf(HasOne::class, $defined['phone']);
        $this->assertInstanceOf(BelongsToMany::class, $defined['roles']);
        $this->assertInstanceOf(MorphMany::class, $defined['event_log']);
        $this->assertInstanceOf(MorphOne::class, $defined['meta']);
        $this->assertInstanceOf(MorphToMany::class, $defined['tags']);
        $this->assertInstanceOf(HasOne::class, $defined['contactNumber']);
        $this->assertInstanceOf(HasMany::class, $defined['messages']);
        $this->assertInstanceOf(BelongsToMany::class, $defined['scopes']);
        $this->assertInstanceOf(BelongsToMany::class, $defined['executiveAuthors']);
        $this->assertInstanceOf(MorphOne::class, $defined['info']);
        $this->assertInstanceOf(MorphToMany::class, $defined['labels']);
        $this->assertInstanceOf(MorphMany::class, $defined['auditLogs']);
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
            'count' => false,
        ], $author->getRelationDefinition('contactNumber'));
        $this->assertEquals([
            Post::class,
            'key' => 'author_id',
            'otherKey' => 'id',
            'delete' => false,
            'push' => true,
            'count' => false,
        ], $author->getRelationDefinition('messages'));
        $this->assertEquals([
            Role::class,
            'table' => 'database_tester_authors_roles',
            'key' => 'author_id',
            'otherKey' => 'id',
            'push' => true,
            'detach' => true,
            'count' => false,
        ], $author->getRelationDefinition('scopes'));
        $this->assertEquals([
            Meta::class,
            'type' => 'taggable_type',
            'id' => 'taggable_id',
            'delete' => false,
            'push' => true,
            'count' => false,
        ], $author->getRelationDefinition('info'));
        $this->assertEquals([
            EventLog::class,
            'type' => 'related_type',
            'id' => 'related_id',
            'delete' => true,
            'push' => true,
            'count' => false,
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
            'count' => false,
            'pivot' => ['added_by'],
            'detach' => true,
        ], $author->getRelationDefinition('labels'));

        $this->assertNull($author->getRelationDefinition('invalid'));
    }
}
