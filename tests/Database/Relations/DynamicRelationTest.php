<?php

namespace Winter\Storm\Tests\Database\Relations;

use Winter\Storm\Database\Attributes\Relation;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Relations\BelongsTo;
use Winter\Storm\Tests\Database\Fixtures\Author;
use Winter\Storm\Tests\Database\Fixtures\Post;
use Winter\Storm\Tests\DbTestCase;

class DynamicRelationTest extends DbTestCase
{
    public function testCreateDynamicRelation()
    {
        Model::unguard();
        $post = Post::create(['title' => 'First post', 'description' => 'Yay!!']);
        $author1 = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $author2 = Author::create(['name' => 'Louie', 'email' => 'louie@example.com']);
        Model::reguard();

        Post::extend(function (Post $model) {
            $model->addDynamicMethod('creator', function () use ($model): BelongsTo {
                return $model->belongsTo(Author::class, 'author_id');
            });
        });

        $post = new Post;

        $this->assertTrue($post->hasRelation('creator'));
        $this->assertEquals('belongsTo', $post->getRelationType('creator'));

        // Set by Model object
        $post->creator = $author1;
        $this->assertEquals($author1->id, $post->author_id);
        $this->assertEquals('Stevie', $post->creator->name);

        // Set by primary key
        $post->creator = $author2->id;
        $this->assertEquals($author2->id, $post->author_id);
        $this->assertEquals('Louie', $post->creator->name);

        // Nullify
        $post->creator = null;
        $this->assertNull($post->author_id);
        $this->assertNull($post->creator);
    }
}
