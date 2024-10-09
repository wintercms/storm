<?php

namespace Winter\Storm\Tests\Database\Relations;

use Winter\Storm\Database\Model;
use Winter\Storm\Tests\Database\Fixtures\Author;
use Winter\Storm\Tests\Database\Fixtures\Post;
use Winter\Storm\Tests\Database\Fixtures\Meta;
use Winter\Storm\Tests\DbTestCase;

class MorphOneTest extends DbTestCase
{
    public function testSetRelationValue()
    {
        Model::unguard();
        $post = Post::create(['title' => "First post", 'description' => "Yay!!"]);
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $meta1 = Meta::create([
            'meta_title' => 'Question',
            'meta_description' => 'Industry',
            'meta_keywords' => 'major',
            'canonical_url' => 'http://google.com/search/jobs',
            'redirect_url' => 'http://google.com',
            'robot_index' => 'index',
            'robot_follow' => 'follow',
        ]);
        $meta2 = Meta::create([
            'meta_title' => 'Comment',
            'meta_description' => 'Social',
            'meta_keywords' => 'startup',
            'canonical_url' => 'http://facebook.com/search/users',
            'redirect_url' => 'http://facebook.com',
            'robot_index' => 'index',
            'robot_follow' => 'follow',
        ]);
        $meta3 = Meta::make([
            'meta_title' => 'Answer',
            'meta_description' => 'Employment',
            'meta_keywords' => 'minor',
            'canonical_url' => 'http://yahoo.com/search/stats',
            'redirect_url' => 'http://yahoo.com',
            'robot_index' => 'index',
            'robot_follow' => 'follow',
        ]);
        Model::reguard();

        // Set by Model object
        $post->meta = $meta1;
        $post->save();
        $this->assertEquals($post->id, $meta1->taggable_id);
        $this->assertEquals(get_class($post), $meta1->taggable_type);
        $this->assertEquals('Question', $post->meta->meta_title);

        // Double check
        $meta1 = Meta::find($meta1->id);
        $this->assertEquals($post->id, $meta1->taggable_id);
        $this->assertEquals(get_class($post), $meta1->taggable_type);

        // Set by primary key
        $metaId = $meta2->id;
        $author->meta = $metaId;
        $author->save();
        $meta2 = Meta::find($metaId);
        $this->assertEquals($author->id, $meta2->taggable_id);
        $this->assertEquals(get_class($author), $meta2->taggable_type);
        $this->assertEquals('Comment', $author->meta->meta_title);

        // Nullify
        $author->meta = null;
        $author->save();
        $meta = Meta::find($metaId);
        $this->assertNull($meta->taggable_type);
        $this->assertNull($meta->taggable_id);
        $this->assertNull($meta->taggable);

        // Deferred in memory
        $author->meta = $meta3;
        $this->assertEquals('Answer', $author->meta->meta_title);
        $this->assertEquals($author->id, $meta3->taggable_id);
    }

    public function testSetRelationValueLaravelRelation()
    {
        Model::unguard();
        $post = Post::create(['title' => "First post", 'description' => "Yay!!"]);
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $meta1 = Meta::create([
            'meta_title' => 'Question',
            'meta_description' => 'Industry',
            'meta_keywords' => 'major',
            'canonical_url' => 'http://google.com/search/jobs',
            'redirect_url' => 'http://google.com',
            'robot_index' => 'index',
            'robot_follow' => 'follow',
        ]);
        $meta2 = Meta::create([
            'meta_title' => 'Comment',
            'meta_description' => 'Social',
            'meta_keywords' => 'startup',
            'canonical_url' => 'http://facebook.com/search/users',
            'redirect_url' => 'http://facebook.com',
            'robot_index' => 'index',
            'robot_follow' => 'follow',
        ]);
        $meta3 = Meta::make([
            'meta_title' => 'Answer',
            'meta_description' => 'Employment',
            'meta_keywords' => 'minor',
            'canonical_url' => 'http://yahoo.com/search/stats',
            'redirect_url' => 'http://yahoo.com',
            'robot_index' => 'index',
            'robot_follow' => 'follow',
        ]);
        Model::reguard();

        // Set by Model object
        $post->info = $meta1;
        $post->save();
        $this->assertEquals($post->id, $meta1->taggable_id);
        $this->assertEquals(get_class($post), $meta1->taggable_type);
        $this->assertEquals('Question', $post->info->meta_title);

        // Double check
        $meta1 = Meta::find($meta1->id);
        $this->assertEquals($post->id, $meta1->taggable_id);
        $this->assertEquals(get_class($post), $meta1->taggable_type);

        // Set by primary key
        $metaId = $meta2->id;
        $author->info = $metaId;
        $author->save();
        $meta2 = Meta::find($metaId);
        $this->assertEquals($author->id, $meta2->taggable_id);
        $this->assertEquals(get_class($author), $meta2->taggable_type);
        $this->assertEquals('Comment', $author->info->meta_title);

        // Nullify
        $author->info = null;
        $author->save();
        $meta = Meta::find($metaId);
        $this->assertNull($meta->taggable_type);
        $this->assertNull($meta->taggable_id);
        $this->assertNull($meta->taggable);

        // Deferred in memory
        $author->info = $meta3;
        $this->assertEquals('Answer', $author->info->meta_title);
        $this->assertEquals($author->id, $meta3->taggable_id);
    }

    public function testSetRelationValueTwice()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $meta = Meta::create([
            'meta_title' => 'Question',
            'meta_description' => 'Industry',
            'meta_keywords' => 'major',
            'canonical_url' => 'http://google.com/search/jobs',
            'redirect_url' => 'http://google.com',
            'robot_index' => 'index',
            'robot_follow' => 'follow',
        ]);
        Model::reguard();

        $metaId = $meta->id;
        $author->meta = $metaId;
        $author->save();

        $author->meta = $metaId;
        $author->save();

        $meta = Meta::find($metaId);
        $this->assertEquals($author->id, $meta->taggable_id);
        $this->assertEquals(get_class($author), $meta->taggable_type);
    }

    public function testSetRelationValueTwiceLaravelRelation()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $meta = Meta::create([
            'meta_title' => 'Question',
            'meta_description' => 'Industry',
            'meta_keywords' => 'major',
            'canonical_url' => 'http://google.com/search/jobs',
            'redirect_url' => 'http://google.com',
            'robot_index' => 'index',
            'robot_follow' => 'follow',
        ]);
        Model::reguard();

        $metaId = $meta->id;
        $author->info = $metaId;
        $author->save();

        $author->info = $metaId;
        $author->save();

        $meta = Meta::find($metaId);
        $this->assertEquals($author->id, $meta->taggable_id);
        $this->assertEquals(get_class($author), $meta->taggable_type);
    }

    public function testGetRelationValue()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie']);
        $meta = Meta::create([
            'taggable_id' => $author->id,
            'taggable_type' => get_class($author),
            'meta_title' => 'Question',
            'meta_description' => 'Industry',
            'meta_keywords' => 'major',
            'canonical_url' => 'http://google.com/search/jobs',
            'redirect_url' => 'http://google.com',
            'robot_index' => 'index',
            'robot_follow' => 'follow',
        ]);
        Model::reguard();

        $this->assertEquals($meta->id, $author->getRelationValue('meta'));
    }

    public function testGetRelationValueLaravelRelation()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie']);
        $meta = Meta::create([
            'taggable_id' => $author->id,
            'taggable_type' => get_class($author),
            'meta_title' => 'Question',
            'meta_description' => 'Industry',
            'meta_keywords' => 'major',
            'canonical_url' => 'http://google.com/search/jobs',
            'redirect_url' => 'http://google.com',
            'robot_index' => 'index',
            'robot_follow' => 'follow',
        ]);
        Model::reguard();

        $this->assertEquals($meta->id, $author->getRelationValue('info'));
    }

    public function testDeferredBinding()
    {
        $sessionKey = uniqid('session_key', true);

        Model::unguard();
        $author = Author::create(['name' => 'Stevie']);
        $meta = Meta::create([
            'meta_title' => 'Comment',
            'meta_description' => 'Social',
            'meta_keywords' => 'startup',
            'canonical_url' => 'http://facebook.com/search/users',
            'redirect_url' => 'http://facebook.com',
            'robot_index' => 'index',
            'robot_follow' => 'follow',
        ]);
        Model::reguard();

        $metaId = $meta->id;

        // Deferred add
        $author->meta()->add($meta, $sessionKey);
        $this->assertNull($meta->taggable_id);
        $this->assertNull($author->meta);

        $this->assertEquals(0, $author->meta()->count());
        $this->assertEquals(1, $author->meta()->withDeferred($sessionKey)->count());

        // Commit deferred
        $author->save(null, $sessionKey);
        $meta = Meta::find($metaId);
        $this->assertEquals(1, $author->meta()->count());
        $this->assertEquals($author->id, $meta->taggable_id);
        $this->assertEquals('Comment', $author->meta->meta_title);

        // New session
        $sessionKey = uniqid('session_key', true);

        // Deferred remove
        $author->meta()->remove($meta, $sessionKey);
        $this->assertEquals(1, $author->meta()->count());
        $this->assertEquals(0, $author->meta()->withDeferred($sessionKey)->count());
        $this->assertEquals($author->id, $meta->taggable_id);
        $this->assertEquals('Comment', $author->meta->meta_title);

        // Commit deferred
        $author->save(null, $sessionKey);
        $meta = Meta::find($metaId);
        $this->assertEquals(0, $author->meta()->count());
        $this->assertNull($meta->taggable_id);
        $this->assertNull($author->meta);
    }

    public function testDeferredBindingLaravelRelation()
    {
        $sessionKey = uniqid('session_key', true);

        Model::unguard();
        $author = Author::create(['name' => 'Stevie']);
        $meta = Meta::create([
            'meta_title' => 'Comment',
            'meta_description' => 'Social',
            'meta_keywords' => 'startup',
            'canonical_url' => 'http://facebook.com/search/users',
            'redirect_url' => 'http://facebook.com',
            'robot_index' => 'index',
            'robot_follow' => 'follow',
        ]);
        Model::reguard();

        $metaId = $meta->id;

        // Deferred add
        $author->info()->add($meta, $sessionKey);
        $this->assertNull($meta->taggable_id);
        $this->assertNull($author->info);

        $this->assertEquals(0, $author->info()->count());
        $this->assertEquals(1, $author->info()->withDeferred($sessionKey)->count());

        // Commit deferred
        $author->save(null, $sessionKey);
        $meta = Meta::find($metaId);
        $this->assertEquals(1, $author->info()->count());
        $this->assertEquals($author->id, $meta->taggable_id);
        $this->assertEquals('Comment', $author->info->meta_title);

        // New session
        $sessionKey = uniqid('session_key', true);

        // Deferred remove
        $author->info()->remove($meta, $sessionKey);
        $this->assertEquals(1, $author->info()->count());
        $this->assertEquals(0, $author->info()->withDeferred($sessionKey)->count());
        $this->assertEquals($author->id, $meta->taggable_id);
        $this->assertEquals('Comment', $author->info->meta_title);

        // Commit deferred
        $author->save(null, $sessionKey);
        $meta = Meta::find($metaId);
        $this->assertEquals(0, $author->info()->count());
        $this->assertNull($meta->taggable_id);
        $this->assertNull($author->info);
    }
}
