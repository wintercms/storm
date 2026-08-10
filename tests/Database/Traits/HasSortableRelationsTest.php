<?php

namespace Winter\Storm\Tests\Database\Traits;

use Winter\Storm\Database\Model;
use Winter\Storm\Database\Models\DeferredBinding;
use Winter\Storm\Database\Traits\HasSortableRelations;
use Winter\Storm\Tests\Database\Fixtures\Author;
use Winter\Storm\Tests\Database\Fixtures\SortableArticle;
use Winter\Storm\Tests\Database\Fixtures\Tag;
use Winter\Storm\Tests\DbTestCase;

class HasSortableRelationsTest extends DbTestCase
{
    protected function makeArticleWithAuthors(): array
    {
        Model::unguard();
        $article = SortableArticle::create(['title' => 'Article']);
        $a = Author::create(['name' => 'A']);
        $b = Author::create(['name' => 'B']);
        $c = Author::create(['name' => 'C']);
        Model::reguard();

        return [$article, $a, $b, $c];
    }

    public function testGetSortableRelationsFromProperty()
    {
        $article = new SortableArticle();
        $this->assertSame(['authors' => 'sort_order'], $article->getSortableRelations());
    }

    public function testGetSortableRelationsIsPublic()
    {
        // Behaviors call this externally; it must be public.
        $method = new \ReflectionMethod(SortableArticle::class, 'getSortableRelations');
        $this->assertTrue($method->isPublic());
    }

    public function testIsSortableRelation()
    {
        $article = new SortableArticle();
        $this->assertTrue($article->isSortableRelation('authors'));
        $this->assertFalse($article->isSortableRelation('nonexistent'));
    }

    public function testGetRelationSortOrderColumn()
    {
        $article = new SortableArticle();
        $this->assertSame('sort_order', $article->getRelationSortOrderColumn('authors'));
        // Falls back to the default when the relation is not configured.
        $this->assertSame('sort_order', $article->getRelationSortOrderColumn('whatever'));
    }

    public function testInitAddsPivotColumnAndQualifiedOrderClause()
    {
        $article = new SortableArticle();
        $definition = $article->belongsToMany['authors'];

        $this->assertContains('sort_order', $definition['pivot']);
        // Order clause must be qualified with the pivot table name to avoid
        // colliding with a same-named column on the related model.
        $this->assertSame('database_tester_sortable_article_author.sort_order asc', $definition['order']);
    }

    public function testInitInjectsForMorphToMany()
    {
        $model = new class extends Model {
            use HasSortableRelations;
            public $morphToMany = [
                'tags' => [Tag::class, 'name' => 'taggable', 'table' => 'database_tester_taggables'],
            ];
            public $sortableRelations = ['tags' => 'sort_order'];
        };

        $this->assertContains('sort_order', $model->morphToMany['tags']['pivot']);
        $this->assertSame('database_tester_taggables.sort_order asc', $model->morphToMany['tags']['order']);
    }

    public function testInitInjectsForMorphedByMany()
    {
        $model = new class extends Model {
            use HasSortableRelations;
            public $morphedByMany = [
                'posts' => [Tag::class, 'name' => 'taggable', 'table' => 'database_tester_taggables'],
            ];
            public $sortableRelations = ['posts' => 'sort_order'];
        };

        $this->assertContains('sort_order', $model->morphedByMany['posts']['pivot']);
        $this->assertSame('database_tester_taggables.sort_order asc', $model->morphedByMany['posts']['order']);
    }

    public function testAutoAttachAssignsSequentialSortOrder()
    {
        [$article, $a, $b, $c] = $this->makeArticleWithAuthors();

        $article->authors()->attach($a->id);
        $article->authors()->attach($b->id);
        $article->authors()->attach($c->id);

        $fresh = SortableArticle::find($article->id);
        $orders = $fresh->authors->pluck('pivot.sort_order', 'name')->all();
        $this->assertSame(['A' => 1, 'B' => 2, 'C' => 3], $orders);
    }

    public function testSetRelationOrderReassignsAndReturnsInOrder()
    {
        [$article, $a, $b, $c] = $this->makeArticleWithAuthors();
        $article->authors()->attach([$a->id, $b->id, $c->id]);

        // Drag C to the front: new id order [C, A, B], explicit orders [1, 2, 3]
        $article->setRelationOrder('authors', [$c->id, $a->id, $b->id], [1, 2, 3]);

        $fresh = SortableArticle::find($article->id);
        $this->assertSame(['C', 'A', 'B'], $fresh->authors->pluck('name')->all());
        $this->assertSame(
            [$c->id => 1, $a->id => 2, $b->id => 3],
            $fresh->authors->pluck('pivot.sort_order', 'pivot.author_id')->all()
        );
    }

    public function testSetRelationOrderEmptyOrdersUsesSequentialRangeNotIds()
    {
        [$article, $a, $b, $c] = $this->makeArticleWithAuthors();
        $article->authors()->attach([$a->id, $b->id, $c->id]);

        // No explicit orders -> 1..N in the given id order (NOT the ids themselves)
        $article->setRelationOrder('authors', [$c->id, $a->id, $b->id]);

        $fresh = SortableArticle::find($article->id);
        $this->assertSame(
            [$c->id => 1, $a->id => 2, $b->id => 3],
            $fresh->authors->pluck('pivot.sort_order', 'pivot.author_id')->all()
        );
    }

    public function testAttachWithExplicitSortOrderIsNotAutoAppended()
    {
        [$article, $a, $b, $c] = $this->makeArticleWithAuthors();

        // Attaching with an explicit pivot sort order (as happens when committing deferred
        // bindings that carry pivot_data) must be preserved, not overwritten by auto-append.
        $article->authors()->attach($a->id, ['sort_order' => 5]);
        $article->authors()->attach($b->id, ['sort_order' => 9]);
        // No explicit order -> auto-append to the end.
        $article->authors()->attach($c->id);

        $fresh = SortableArticle::find($article->id);
        $orders = $fresh->authors->pluck('pivot.sort_order', 'pivot.author_id')->all();

        $this->assertSame(5, (int) $orders[$a->id]);
        $this->assertSame(9, (int) $orders[$b->id]);
        $this->assertSame(10, (int) $orders[$c->id]); // max(9) + 1
    }

    public function testSetRelationOrderPreservesExplicitZeroOrder()
    {
        [$article, $a, $b, $c] = $this->makeArticleWithAuthors();
        $article->authors()->attach([$a->id, $b->id, $c->id]);

        // An explicit sort order of 0 must be written as 0, not treated as "auto-append".
        $article->setRelationOrder('authors', [$c->id, $a->id, $b->id], [0, 1, 2]);

        $fresh = SortableArticle::find($article->id);
        $this->assertSame(
            [$c->id => 0, $a->id => 1, $b->id => 2],
            $fresh->authors->pluck('pivot.sort_order', 'pivot.author_id')->all()
        );
        $this->assertSame(['C', 'A', 'B'], $fresh->authors->pluck('name')->all());
    }

    public function testSetRelationOrderDeferredWritesToPivotData()
    {
        DeferredBinding::truncate();

        Model::unguard();
        $a = Author::create(['name' => 'A']);
        $b = Author::create(['name' => 'B']);
        $c = Author::create(['name' => 'C']);
        Model::reguard();

        // Unsaved parent -> deferred binding
        $article = new SortableArticle();
        $article->title = 'Deferred';
        $sessionKey = uniqid('session_key', true);

        $article->authors()->add($a, $sessionKey);
        $article->authors()->add($b, $sessionKey);
        $article->authors()->add($c, $sessionKey);

        $article->setRelationOrder('authors', [$c->id, $a->id, $b->id], [1, 2, 3], $sessionKey);

        $bindings = DeferredBinding::where('session_key', $sessionKey)
            ->where('is_bind', 1)
            ->get()
            ->keyBy('slave_id');

        $this->assertSame(1, (int) $bindings[$c->id]->pivot_data['sort_order']);
        $this->assertSame(2, (int) $bindings[$a->id]->pivot_data['sort_order']);
        $this->assertSame(3, (int) $bindings[$b->id]->pivot_data['sort_order']);
    }

    public function testSetRelationOrderThrowsOnCountMismatch()
    {
        [$article] = $this->makeArticleWithAuthors();

        $this->expectException(\Exception::class);
        $article->setRelationOrder('authors', [1, 2, 3], [1, 2]);
    }
}
