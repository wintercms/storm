<?php

namespace Winter\Storm\Tests\Database\Relations;

use Winter\Storm\Database\Model;
use Winter\Storm\Support\Facades\DB;
use Winter\Storm\Tests\Database\Fixtures\Category;
use Winter\Storm\Tests\Database\Fixtures\Post;
use Winter\Storm\Tests\Database\Fixtures\Role;
use Winter\Storm\Tests\Database\Fixtures\Author;
use Winter\Storm\Tests\DbTestCase;

class BelongsToManyTest extends DbTestCase
{
    public function testSetRelationValue()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);
        $role3 = Role::create(['name' => "Manager", 'description' => "Budget"]);
        Model::reguard();

        // Add/remove to collection
        $this->assertFalse($author->roles->contains($role1->id));
        $author->roles()->add($role1);
        $author->roles()->add($role2);
        $this->assertTrue($author->roles->contains($role1->id));
        $this->assertTrue($author->roles->contains($role2->id));

        // Set by Model object
        $author->roles = $role1;
        $this->assertEquals(1, $author->roles->count());
        $this->assertEquals('Designer', $author->roles->first()->name);

        $author->roles = [$role1, $role2, $role3];
        $this->assertEquals(3, $author->roles->count());

        // Set by primary key
        $author->roles = $role2->id;
        $this->assertEquals(1, $author->roles->count());
        $this->assertEquals('Programmer', $author->roles->first()->name);

        $author->roles = [$role2->id, $role3->id];
        $this->assertEquals(2, $author->roles->count());

        // Nullify
        $author->roles = null;
        $this->assertEquals(0, $author->roles->count());

        // Extra nullify checks (still exists in DB until saved)
        $author->reloadRelations('roles');
        $this->assertEquals(2, $author->roles->count());
        $author->save();
        $author->reloadRelations('roles');
        $this->assertEquals(0, $author->roles->count());

        // Deferred in memory
        $author->roles = [$role2->id, $role3->id];
        $this->assertEquals(2, $author->roles->count());
        $this->assertEquals('Programmer', $author->roles->first()->name);
    }

    public function testSetRelationValueLaravelRelation()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);
        $role3 = Role::create(['name' => "Manager", 'description' => "Budget"]);
        Model::reguard();

        // Add/remove to collection
        $this->assertFalse($author->scopes->contains($role1->id));
        $author->scopes()->add($role1);
        $author->scopes()->add($role2);
        $this->assertTrue($author->scopes->contains($role1->id));
        $this->assertTrue($author->scopes->contains($role2->id));

        // Set by Model object
        $author->scopes = $role1;
        $this->assertEquals(1, $author->scopes->count());
        $this->assertEquals('Designer', $author->scopes->first()->name);

        $author->scopes = [$role1, $role2, $role3];
        $this->assertEquals(3, $author->scopes->count());

        // Set by primary key
        $author->scopes = $role2->id;
        $this->assertEquals(1, $author->scopes->count());
        $this->assertEquals('Programmer', $author->scopes->first()->name);

        $author->scopes = [$role2->id, $role3->id];
        $this->assertEquals(2, $author->scopes->count());

        // Nullify
        $author->scopes = null;
        $this->assertEquals(0, $author->scopes->count());

        // Extra nullify checks (still exists in DB until saved)
        $author->reloadRelations('scopes');
        $this->assertEquals(2, $author->scopes->count());
        $author->save();
        $author->reloadRelations('scopes');
        $this->assertEquals(0, $author->scopes->count());

        // Deferred in memory
        $author->scopes = [$role2->id, $role3->id];
        $this->assertEquals(2, $author->scopes->count());
        $this->assertEquals('Programmer', $author->scopes->first()->name);
    }

    public function testGetRelationValue()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);
        Model::reguard();

        $author->roles()->add($role1);
        $author->roles()->add($role2);

        $this->assertEquals([$role1->id, $role2->id], $author->getRelationValue('roles'));
    }

    public function testGetRelationValueLaravelRelation()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);
        Model::reguard();

        $author->scopes()->add($role1);
        $author->scopes()->add($role2);

        $this->assertEquals([$role1->id, $role2->id], $author->getRelationValue('scopes'));
    }

    public function testDeferredBinding()
    {
        $sessionKey = uniqid('session_key', true);

        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);

        $category = Category::create(['name' => 'News']);
        $post1 = Post::create(['title' => 'First post']);
        $post2 = Post::create(['title' => 'Second post']);
        Model::reguard();

        // Deferred add
        $author->roles()->add($role1, $sessionKey);
        $author->roles()->add($role2, $sessionKey);
        $category->posts()->add($post1, $sessionKey, [
            'category_name' => $category->name . ' in pivot',
            'post_name' => $post1->title . ' in pivot',
        ]);
        $category->posts()->add($post2, $sessionKey, [
            'category_name' => $category->name . ' in pivot',
            'post_name' => $post2->title . ' in pivot',
        ]);
        $this->assertEmpty($author->roles);
        $this->assertEmpty($category->posts);

        $this->assertEquals(0, $author->roles()->count());
        $this->assertEquals(2, $author->roles()->withDeferred($sessionKey)->count());
        $this->assertEquals(0, $category->posts()->count());
        $this->assertEquals(2, $category->posts()->withDeferred($sessionKey)->count());

        // Get simple value (implicit)
        $author->reloadRelations();
        $author->sessionKey = $sessionKey;
        $this->assertEquals([$role1->id, $role2->id], $author->getRelationValue('roles'));
        $category->reloadRelations();
        $category->sessionKey = $sessionKey;
        $this->assertEquals([$post1->id, $post2->id], $category->getRelationValue('posts'));

        // Get simple value (explicit)
        $relatedIds = $author->roles()->allRelatedIds($sessionKey)->all();
        $this->assertEquals([$role1->id, $role2->id], $relatedIds);
        $relatedIds = $category->posts()->allRelatedIds($sessionKey)->all();
        $this->assertEquals([$post1->id, $post2->id], $relatedIds);

        // Commit deferred
        $author->save(null, $sessionKey);
        $category->save(null, $sessionKey);
        $this->assertEquals(2, $author->roles()->count());
        $this->assertEquals('Designer', $author->roles->first()->name);
        $this->assertEquals(2, $category->posts()->count());
        $this->assertEquals('First post', $category->posts->first()->title);
        $this->assertEquals('Second post', $category->posts->last()->title);
        $this->assertEquals('First post in pivot', $category->posts->first()->pivot->post_name);
        $this->assertEquals('Second post in pivot', $category->posts->last()->pivot->post_name);
        $this->assertEquals('News in pivot', $category->posts->first()->pivot->category_name);
        $this->assertEquals('News in pivot', $category->posts->last()->pivot->category_name);

        // New session
        $sessionKey = uniqid('session_key', true);

        // Deferred remove
        $author->roles()->remove($role1, $sessionKey);
        $author->roles()->remove($role2, $sessionKey);
        $category->posts()->remove($post1, $sessionKey);
        $category->posts()->remove($post2, $sessionKey);
        $this->assertEquals(2, $author->roles()->count());
        $this->assertEquals(0, $author->roles()->withDeferred($sessionKey)->count());
        $this->assertEquals(2, $category->posts()->count());
        $this->assertEquals(0, $category->posts()->withDeferred($sessionKey)->count());
        $this->assertEquals('Designer', $author->roles->first()->name);
        $this->assertEquals('First post', $category->posts->first()->title);
        $this->assertEquals('Second post', $category->posts->last()->title);
        $this->assertEquals('First post in pivot', $category->posts->first()->pivot->post_name);
        $this->assertEquals('Second post in pivot', $category->posts->last()->pivot->post_name);
        $this->assertEquals('News in pivot', $category->posts->first()->pivot->category_name);
        $this->assertEquals('News in pivot', $category->posts->last()->pivot->category_name);

        // Commit deferred
        $author->save(null, $sessionKey);
        $category->save(null, $sessionKey);
        $this->assertEquals(0, $author->roles()->count());
        $this->assertEquals(0, $author->roles->count());
        $this->assertEquals(0, $category->posts()->count());
        $this->assertEquals(0, $category->posts->count());
    }

    public function testDeferredBindingLaravelRelation()
    {
        $sessionKey = uniqid('session_key', true);

        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);

        $category = Category::create(['name' => 'News']);
        $post1 = Post::create(['title' => 'First post']);
        $post2 = Post::create(['title' => 'Second post']);
        Model::reguard();

        // Deferred add
        $author->scopes()->add($role1, $sessionKey);
        $author->scopes()->add($role2, $sessionKey);
        $category->posts()->add($post1, $sessionKey, [
            'category_name' => $category->name . ' in pivot',
            'post_name' => $post1->title . ' in pivot',
        ]);
        $category->posts()->add($post2, $sessionKey, [
            'category_name' => $category->name . ' in pivot',
            'post_name' => $post2->title . ' in pivot',
        ]);
        $this->assertEmpty($author->scopes);
        $this->assertEmpty($category->posts);

        $this->assertEquals(0, $author->scopes()->count());
        $this->assertEquals(2, $author->scopes()->withDeferred($sessionKey)->count());
        $this->assertEquals(0, $category->posts()->count());
        $this->assertEquals(2, $category->posts()->withDeferred($sessionKey)->count());

        // Get simple value (implicit)
        $author->reloadRelations();
        $author->sessionKey = $sessionKey;
        $this->assertEquals([$role1->id, $role2->id], $author->getRelationValue('scopes'));
        $category->reloadRelations();
        $category->sessionKey = $sessionKey;
        $this->assertEquals([$post1->id, $post2->id], $category->getRelationValue('posts'));

        // Get simple value (explicit)
        $relatedIds = $author->scopes()->allRelatedIds($sessionKey)->all();
        $this->assertEquals([$role1->id, $role2->id], $relatedIds);
        $relatedIds = $category->posts()->allRelatedIds($sessionKey)->all();
        $this->assertEquals([$post1->id, $post2->id], $relatedIds);

        // Commit deferred
        $author->save(null, $sessionKey);
        $category->save(null, $sessionKey);
        $this->assertEquals(2, $author->scopes()->count());
        $this->assertEquals('Designer', $author->scopes->first()->name);
        $this->assertEquals(2, $category->posts()->count());
        $this->assertEquals('First post', $category->posts->first()->title);
        $this->assertEquals('Second post', $category->posts->last()->title);
        $this->assertEquals('First post in pivot', $category->posts->first()->pivot->post_name);
        $this->assertEquals('Second post in pivot', $category->posts->last()->pivot->post_name);
        $this->assertEquals('News in pivot', $category->posts->first()->pivot->category_name);
        $this->assertEquals('News in pivot', $category->posts->last()->pivot->category_name);

        // New session
        $sessionKey = uniqid('session_key', true);

        // Deferred remove
        $author->scopes()->remove($role1, $sessionKey);
        $author->scopes()->remove($role2, $sessionKey);
        $category->posts()->remove($post1, $sessionKey);
        $category->posts()->remove($post2, $sessionKey);
        $this->assertEquals(2, $author->scopes()->count());
        $this->assertEquals(0, $author->scopes()->withDeferred($sessionKey)->count());
        $this->assertEquals(2, $category->posts()->count());
        $this->assertEquals(0, $category->posts()->withDeferred($sessionKey)->count());
        $this->assertEquals('Designer', $author->scopes->first()->name);
        $this->assertEquals('First post', $category->posts->first()->title);
        $this->assertEquals('Second post', $category->posts->last()->title);
        $this->assertEquals('First post in pivot', $category->posts->first()->pivot->post_name);
        $this->assertEquals('Second post in pivot', $category->posts->last()->pivot->post_name);
        $this->assertEquals('News in pivot', $category->posts->first()->pivot->category_name);
        $this->assertEquals('News in pivot', $category->posts->last()->pivot->category_name);

        // Commit deferred
        $author->save(null, $sessionKey);
        $category->save(null, $sessionKey);
        $this->assertEquals(0, $author->scopes()->count());
        $this->assertEquals(0, $author->scopes->count());
        $this->assertEquals(0, $category->posts()->count());
        $this->assertEquals(0, $category->posts->count());
    }

    public function testDetachAfterDelete()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);
        $role3 = Role::create(['name' => "Manager", 'description' => "Budget"]);
        Model::reguard();

        $author->roles()->add($role1);
        $author->roles()->add($role2);
        $author->roles()->add($role3);
        $this->assertEquals(3, DB::table('database_tester_authors_roles')->where('author_id', $author->id)->count());

        $author->delete();
        $this->assertEquals(0, DB::table('database_tester_authors_roles')->where('author_id', $author->id)->count());
    }

    public function testDetachAfterDeleteLaravelRelation()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);
        $role3 = Role::create(['name' => "Manager", 'description' => "Budget"]);
        Model::reguard();

        $author->scopes()->add($role1);
        $author->scopes()->add($role2);
        $author->scopes()->add($role3);
        $this->assertEquals(3, DB::table('database_tester_authors_roles')->where('author_id', $author->id)->count());

        $author->delete();
        $this->assertEquals(0, DB::table('database_tester_authors_roles')->where('author_id', $author->id)->count());
    }

    public function testConditionsWithPivotAttributes()
    {
        Model::unguard();
        $author = Author::create(['name' => 'Stevie', 'email' => 'stevie@example.com']);
        $role1 = Role::create(['name' => "Designer", 'description' => "Quality"]);
        $role2 = Role::create(['name' => "Programmer", 'description' => "Speed"]);
        $role3 = Role::create(['name' => "Manager", 'description' => "Budget"]);
        Model::reguard();

        $author->roles()->add($role1, null, ['is_executive' => 1]);
        $author->roles()->add($role2, null, ['is_executive' => 1]);
        $author->roles()->add($role3, null, ['is_executive' => 0]);

        $this->assertEquals([1, 2], $author->executiveAuthors->lists('id'));
        $this->assertEquals([1, 2], $author->executiveAuthors()->lists('id'));
        $this->assertEquals([1, 2], $author->executiveAuthors()->get()->lists('id'));
    }
}
