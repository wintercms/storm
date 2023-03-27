<?php

namespace Winter\Storm\Tests\Database\Relations;

use DbTestCase;
use Winter\Storm\Database\Model;
use Winter\Storm\Tests\Database\Fixtures\Models\Post;
use Winter\Storm\Tests\Database\Fixtures\Models\Author;
use Winter\Storm\Tests\Database\Fixtures\Models\EventLog;
use Winter\Storm\Tests\Database\Fixtures\Traits\CreatesModelTables;

class MorphToTest extends DbTestCase
{
    use CreatesModelTables;

    public function setUp() : void
    {
        parent::setUp();

        $this->createModelTables();
    }

    public function testSetRelationValue()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $post1 = Post::create(['title' => "First post", 'description' => "Yay!!"]);
        $post2 = Post::make(['title' => "Second post", 'description' => "Woohoo!!"]);
        $event = EventLog::create(['action' => "user-created"]);
        Model::reguard();

        // Set by Model object
        $event->related = $author;
        $event->save();
        $this->assertEquals($author->id, $event->related_id);
        $this->assertEquals('Stevie', $event->related->name);

        // Set by primary key
        $event->related = [$post1->id, get_class($post1)];
        $this->assertEquals($post1->id, $event->related_id);
        $this->assertEquals('First post', $event->related->title);

        // Nullify
        $event->related = null;
        $this->assertNull($event->related_id);
        $this->assertNull($event->related);

        // Deferred in memory
        $event->related = $post2;
        $this->assertEquals('Second post', $event->related->title);
        $this->assertNull($event->related_id);
        $event->save();
        $this->assertEquals($post2->id, $event->related_id);
    }

    public function testGetRelationValue()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie']);
        $event = EventLog::make(['action' => "user-created", 'related_id' => $author->id, 'related_type' => get_class($author)]);
        Model::reguard();

        $this->assertEquals([$author->id, get_class($author)], $event->getRelationValue('related'));
    }
}
