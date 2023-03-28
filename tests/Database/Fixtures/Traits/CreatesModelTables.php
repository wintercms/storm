<?php

namespace Winter\Storm\Tests\Database\Fixtures\Traits;

trait CreatesModelTables
{
    protected function createModelTables()
    {
        $this->getBuilder()->create('database_tester_authors', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index()->nullable();
            $table->integer('country_id')->unsigned()->index()->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $this->getBuilder()->create('database_tester_categories', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('parent_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->index()->unique();
            $table->string('description')->nullable();
            $table->integer('company_id')->unsigned()->nullable();
            $table->string('language', 3)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->getBuilder()->create('database_tester_categories_nested', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('parent_id')->nullable();
            $table->integer('nest_left')->nullable();
            $table->integer('nest_right')->nullable();
            $table->integer('nest_depth')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->index()->unique();
            $table->string('description')->nullable();
            $table->integer('company_id')->unsigned()->nullable();
            $table->string('language', 3)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->getBuilder()->create('database_tester_countries', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $this->getBuilder()->create('database_tester_event_log', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('action', 30)->nullable();
            $table->string('related_id')->index()->nullable();
            $table->string('related_type')->index()->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $this->getBuilder()->create('database_tester_meta', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('taggable_id')->unsigned()->index()->nullable();
            $table->string('taggable_type')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('redirect_url')->nullable();
            $table->string('robot_index')->nullable();
            $table->string('robot_follow')->nullable();
        });

        $this->getBuilder()->create('database_tester_phones', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('number')->nullable();
            $table->integer('author_id')->unsigned()->index()->nullable();
            $table->timestamps();
        });

        $this->getBuilder()->create('database_tester_posts', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('slug')->nullable()->index();
            $table->text('long_slug')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->integer('author_id')->unsigned()->index()->nullable();
            $table->string('author_nickname')->default('Winter')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $this->getBuilder()->create('database_tester_categories_posts', function ($table) {
            $table->engine = 'InnoDB';
            $table->integer('category_id')->unsigned();
            $table->integer('post_id')->unsigned();
            $table->primary(['category_id', 'post_id']);
            $table->string('category_name')->nullable();
            $table->string('post_name')->nullable();
        });

        $this->getBuilder()->create('database_tester_roles', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $this->getBuilder()->create('database_tester_authors_roles', function ($table) {
            $table->engine = 'InnoDB';
            $table->integer('author_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->primary(['author_id', 'role_id']);
            $table->string('clearance_level')->nullable();
            $table->boolean('is_executive')->default(false);
        });

        $this->getBuilder()->create('database_tester_tags', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->getBuilder()->create('database_tester_taggables', function ($table) {
            $table->engine = 'InnoDB';
            $table->unsignedInteger('tag_id');
            $table->morphs('taggable', 'testings_taggable');
            $table->unsignedInteger('added_by')->nullable();
        });

        $this->getBuilder()->create('database_tester_users', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $this->getBuilder()->create('deferred_bindings', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('master_type')->index();
            $table->string('master_field')->index();
            $table->string('slave_type')->index();
            $table->string('slave_id')->index();
            $table->mediumText('pivot_data')->nullable();
            $table->string('session_key');
            $table->boolean('is_bind')->default(true);
            $table->timestamps();
        });

        $this->getBuilder()->create('files', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('disk_name');
            $table->string('file_name');
            $table->integer('file_size');
            $table->string('content_type');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('field')->nullable()->index();
            $table->string('attachment_id')->index()->nullable();
            $table->string('attachment_type')->index()->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });

        $this->getBuilder()->create('revisions', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->string('field')->nullable()->index();
            $table->string('cast')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('revisionable_type');
            $table->integer('revisionable_id');
            $table->timestamps();
            $table->index(['revisionable_id', 'revisionable_type']);
        });
    }
}
