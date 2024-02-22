<?php

namespace Winter\Storm\Tests\Database\Relations;

use Winter\Storm\Database\Collection;
use Winter\Storm\Database\Model;
use Winter\Storm\Tests\Database\Fixtures\Author;
use Winter\Storm\Tests\Database\Fixtures\Tag;
use Winter\Storm\Tests\Database\Fixtures\Post;
use Winter\Storm\Tests\Database\Fixtures\EventLog;
use Winter\Storm\Tests\DbTestCase;

class MorphManyModelTest extends DbTestCase
{
    public function testSetRelationValue()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $event1 = EventLog::create(['action' => "user-created"]);
        $event2 = EventLog::create(['action' => "user-updated"]);
        $event3 = EventLog::create(['action' => "user-deleted"]);
        $event4 = EventLog::make(['action' => "user-restored"]);
        Model::reguard();

        // Set by Model object
        $author->event_log = new Collection([$event1, $event2]);
        $author->save();
        $this->assertEquals($author->id, $event1->related_id);
        $this->assertEquals($author->id, $event2->related_id);
        $this->assertEquals('Winter\Storm\Tests\Database\Fixtures\Author', $event1->related_type);
        $this->assertEquals('Winter\Storm\Tests\Database\Fixtures\Author', $event2->related_type);
        $this->assertEquals([
            'user-created',
            'user-updated'
        ], $author->event_log->lists('action'));

        // Set by primary key
        $eventId = $event3->id;
        $author->event_log = $eventId;
        $author->save();
        $event3 = EventLog::find($eventId);
        $this->assertEquals($author->id, $event3->related_id);
        $this->assertEquals('Winter\Storm\Tests\Database\Fixtures\Author', $event3->related_type);
        $this->assertEquals([
            'user-deleted'
        ], $author->event_log->lists('action'));

        // Nullify
        $author->event_log = null;
        $author->save();
        $event3 = EventLog::find($eventId);
        $this->assertNull($event3->related_type);
        $this->assertNull($event3->related_id);
        $this->assertNull($event3->related);

        // Deferred in memory
        $author->event_log = $event4;
        $this->assertEquals($author->id, $event4->related_id);
        $this->assertEquals('Winter\Storm\Tests\Database\Fixtures\Author', $event4->related_type);
        $this->assertEquals([
            'user-restored'
        ], $author->event_log->lists('action'));
    }

    public function testSetRelationValueLaravelRelation()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $event1 = EventLog::create(['action' => "user-created"]);
        $event2 = EventLog::create(['action' => "user-updated"]);
        $event3 = EventLog::create(['action' => "user-deleted"]);
        $event4 = EventLog::make(['action' => "user-restored"]);
        Model::reguard();

        // Set by Model object
        $author->auditLogs = new Collection([$event1, $event2]);
        $author->save();
        $this->assertEquals($author->id, $event1->related_id);
        $this->assertEquals($author->id, $event2->related_id);
        $this->assertEquals('Winter\Storm\Tests\Database\Fixtures\Author', $event1->related_type);
        $this->assertEquals('Winter\Storm\Tests\Database\Fixtures\Author', $event2->related_type);
        $this->assertEquals([
            'user-created',
            'user-updated'
        ], $author->auditLogs->lists('action'));

        // Set by primary key
        $eventId = $event3->id;
        $author->auditLogs = $eventId;
        $author->save();
        $event3 = EventLog::find($eventId);
        $this->assertEquals($author->id, $event3->related_id);
        $this->assertEquals('Winter\Storm\Tests\Database\Fixtures\Author', $event3->related_type);
        $this->assertEquals([
            'user-deleted'
        ], $author->auditLogs->lists('action'));

        // Nullify
        $author->auditLogs = null;
        $author->save();
        $event3 = EventLog::find($eventId);
        $this->assertNull($event3->related_type);
        $this->assertNull($event3->related_id);
        $this->assertNull($event3->related);

        // Deferred in memory
        $author->auditLogs = $event4;
        $this->assertEquals($author->id, $event4->related_id);
        $this->assertEquals('Winter\Storm\Tests\Database\Fixtures\Author', $event4->related_type);
        $this->assertEquals([
            'user-restored'
        ], $author->auditLogs->lists('action'));
    }

    public function testGetRelationValue()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie']);
        $event1 = EventLog::create(['action' => "user-created", 'related_id' => $author->id, 'related_type' => 'Winter\Storm\Tests\Database\Fixtures\Author']);
        $event2 = EventLog::create(['action' => "user-updated", 'related_id' => $author->id, 'related_type' => 'Winter\Storm\Tests\Database\Fixtures\Author']);
        Model::reguard();

        $this->assertEquals([$event1->id, $event2->id], $author->getRelationValue('event_log'));
    }

    public function testGetRelationValueLaravelRelation()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie']);
        $event1 = EventLog::create(['action' => "user-created", 'related_id' => $author->id, 'related_type' => 'Winter\Storm\Tests\Database\Fixtures\Author']);
        $event2 = EventLog::create(['action' => "user-updated", 'related_id' => $author->id, 'related_type' => 'Winter\Storm\Tests\Database\Fixtures\Author']);
        Model::reguard();

        $this->assertEquals([$event1->id, $event2->id], $author->getRelationValue('auditLogs'));
    }

    public function testDeferredBinding()
    {
        $sessionKey = uniqid('session_key', true);

        Model::unguard();
        $author = Author::create(['name' => 'Stevie']);
        $event = EventLog::create(['action' => "user-created"]);
        $post = Post::create(['title' => 'Hello world!']);
        $tagForAuthor = Tag::create(['name' => 'ForAuthor']);
        $tagForPost = Tag::create(['name' => 'ForPost']);
        Model::reguard();

        $eventId = $event->id;

        // Deferred add
        $author->event_log()->add($event, $sessionKey);
        $this->assertNull($event->related_id);
        $this->assertEmpty($author->event_log);

        $this->assertEquals(0, $author->event_log()->count());
        $this->assertEquals(1, $author->event_log()->withDeferred($sessionKey)->count());

        $author->tags()->add($tagForAuthor, $sessionKey, ['added_by' => 99]);
        $this->assertEmpty($author->tags);

        $this->assertEquals(0, $author->tags()->count());
        $this->assertEquals(1, $author->tags()->withDeferred($sessionKey)->count());

        $tagForPost->posts()->add($post, $sessionKey, ['added_by' => 88]);
        $this->assertEmpty($tagForPost->posts);

        $this->assertEquals(0, $tagForPost->posts()->count());
        $this->assertEquals(1, $tagForPost->posts()->withDeferred($sessionKey)->count());

        // Commit deferred
        $author->save(null, $sessionKey);
        $event = EventLog::find($eventId);
        $this->assertEquals(1, $author->event_log()->count());
        $this->assertEquals($author->id, $event->related_id);
        $this->assertEquals([
            'user-created'
        ], $author->event_log->lists('action'));

        $this->assertEquals(1, $author->tags()->count());
        $this->assertEquals([$tagForAuthor->id], $author->tags->lists('id'));
        $this->assertEquals(99, $author->tags->first()->pivot->added_by);

        $tagForPost->save(null, $sessionKey);
        $this->assertEquals(1, $tagForPost->posts()->count());
        $this->assertEquals([$post->id], $tagForPost->posts->lists('id'));
        $this->assertEquals(88, $tagForPost->posts->first()->pivot->added_by);

        // New session
        $sessionKey = uniqid('session_key', true);

        // Deferred remove
        $author->event_log()->remove($event, $sessionKey);
        $this->assertEquals(1, $author->event_log()->count());
        $this->assertEquals(0, $author->event_log()->withDeferred($sessionKey)->count());
        $this->assertEquals($author->id, $event->related_id);
        $this->assertEquals([
            'user-created'
        ], $author->event_log->lists('action'));

        $author->tags()->remove($tagForAuthor, $sessionKey);
        $this->assertEquals(1, $author->tags()->count());
        $this->assertEquals(0, $author->tags()->withDeferred($sessionKey)->count());
        $this->assertEquals([$tagForAuthor->id], $author->tags->lists('id'));
        $this->assertEquals(99, $author->tags->first()->pivot->added_by);

        $tagForPost->posts()->remove($post, $sessionKey);
        $this->assertEquals(1, $tagForPost->posts()->count());
        $this->assertEquals(0, $tagForPost->posts()->withDeferred($sessionKey)->count());
        $this->assertEquals([$post->id], $tagForPost->posts->lists('id'));
        $this->assertEquals(88, $tagForPost->posts->first()->pivot->added_by);

        // Commit deferred (model is deleted as per definition)
        $author->save(null, $sessionKey);
        $event = EventLog::find($eventId);
        $this->assertEquals(0, $author->event_log()->count());
        $this->assertNull($event);
        $this->assertEmpty($author->event_log);

        $this->assertEmpty(0, $author->tags);
        $this->assertEmpty(0, $tagForPost->posts);
    }

    public function testDeferredBindingLaravelRelation()
    {
        $sessionKey = uniqid('session_key', true);

        Model::unguard();
        $author = Author::create(['name' => 'Stevie']);
        $event = EventLog::create(['action' => "user-created"]);
        $post = Post::create(['title' => 'Hello world!']);
        $tagForAuthor = Tag::create(['name' => 'ForAuthor']);
        $tagForPost = Tag::create(['name' => 'ForPost']);
        Model::reguard();

        $eventId = $event->id;

        // Deferred add
        $author->auditLogs()->add($event, $sessionKey);
        $this->assertNull($event->related_id);
        $this->assertEmpty($author->auditLogs);

        $this->assertEquals(0, $author->auditLogs()->count());
        $this->assertEquals(1, $author->auditLogs()->withDeferred($sessionKey)->count());

        $author->tags()->add($tagForAuthor, $sessionKey, ['added_by' => 99]);
        $this->assertEmpty($author->tags);

        $this->assertEquals(0, $author->tags()->count());
        $this->assertEquals(1, $author->tags()->withDeferred($sessionKey)->count());

        $tagForPost->posts()->add($post, $sessionKey, ['added_by' => 88]);
        $this->assertEmpty($tagForPost->posts);

        $this->assertEquals(0, $tagForPost->posts()->count());
        $this->assertEquals(1, $tagForPost->posts()->withDeferred($sessionKey)->count());

        // Commit deferred
        $author->save(null, $sessionKey);
        $event = EventLog::find($eventId);
        $this->assertEquals(1, $author->auditLogs()->count());
        $this->assertEquals($author->id, $event->related_id);
        $this->assertEquals([
            'user-created'
        ], $author->auditLogs->lists('action'));

        $this->assertEquals(1, $author->tags()->count());
        $this->assertEquals([$tagForAuthor->id], $author->tags->lists('id'));
        $this->assertEquals(99, $author->tags->first()->pivot->added_by);

        $tagForPost->save(null, $sessionKey);
        $this->assertEquals(1, $tagForPost->posts()->count());
        $this->assertEquals([$post->id], $tagForPost->posts->lists('id'));
        $this->assertEquals(88, $tagForPost->posts->first()->pivot->added_by);

        // New session
        $sessionKey = uniqid('session_key', true);

        // Deferred remove
        $author->auditLogs()->remove($event, $sessionKey);
        $this->assertEquals(1, $author->auditLogs()->count());
        $this->assertEquals(0, $author->auditLogs()->withDeferred($sessionKey)->count());
        $this->assertEquals($author->id, $event->related_id);
        $this->assertEquals([
            'user-created'
        ], $author->auditLogs->lists('action'));

        $author->tags()->remove($tagForAuthor, $sessionKey);
        $this->assertEquals(1, $author->tags()->count());
        $this->assertEquals(0, $author->tags()->withDeferred($sessionKey)->count());
        $this->assertEquals([$tagForAuthor->id], $author->tags->lists('id'));
        $this->assertEquals(99, $author->tags->first()->pivot->added_by);

        $tagForPost->posts()->remove($post, $sessionKey);
        $this->assertEquals(1, $tagForPost->posts()->count());
        $this->assertEquals(0, $tagForPost->posts()->withDeferred($sessionKey)->count());
        $this->assertEquals([$post->id], $tagForPost->posts->lists('id'));
        $this->assertEquals(88, $tagForPost->posts->first()->pivot->added_by);

        // Commit deferred (model is deleted as per definition)
        $author->save(null, $sessionKey);
        $event = EventLog::find($eventId);
        $this->assertEquals(0, $author->auditLogs()->count());
        $this->assertNull($event);
        $this->assertEmpty($author->auditLogs);

        $this->assertEmpty(0, $author->tags);
        $this->assertEmpty(0, $tagForPost->posts);
    }
}
