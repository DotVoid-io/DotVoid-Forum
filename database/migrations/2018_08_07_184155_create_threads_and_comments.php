<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateThreadsAndComments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Info common to basic threads and blog articles
        Schema::create('threads', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
            $table->increments('id');
            $table->string('title');
            $table->text('content');
            $table->boolean('is_draft');
            $table->boolean('is_article');
            // -- Lock --
            $table->timestamp('locked_at')->nullable();
            $table->unsignedInteger('locker_id')->nullable();
            $table->foreign('locker_id')->references('id')->on('users');
            $table->string('lock_message')->nullable();
            // ----------
            // -- Pin --
            $table->timestamp('pinned_at')->nullable();
            $table->unsignedInteger('pinner_id')->nullable();
            $table->foreign('pinner_id')->references('id')->on('users');
            // ---------
            // TODO not in this migration: polls
        });

        // Comments under threads, ie under basic threads and articles
        Schema::create('comments', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
            $table->increments('id');
            $table->text('content');
            $table->unsignedInteger('thread_id');
            $table->foreign('thread_id')->references('id')->on('threads');
            $table->unsignedInteger('author_id');
            $table->foreign('author_id')->references('id')->on('users');
            $table->boolean('is_pinned');
        });

        // Categories for basic threads
        Schema::create('categories', function (Blueprint $table) {
            $table->softDeletes();
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
            // self-reference cannot be created here because the table
            // doesn't exist yet, it will be created in the next block
            $table->string('name');
            $table->string('description');
            $table->string('fa_icon');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('categories');
        });

        // Basic threads that belong to a category and have one author
        Schema::create('basic_threads', function (Blueprint $table) {
            $table->increments('id');
            $table->foreign('id')->references('id')->on('threads');
            $table->boolean('is_question');
            $table->unsignedInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->unsignedInteger('author_id');
            $table->foreign('author_id')->references('id')->on('users');
        });

        // Blogs for articles
        Schema::create('blogs', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('description');
            $table->string('logo_url')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED']);
            // -- Review --
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedInteger('reviewer_id')->nullable();
            $table->string('review_message')->nullable();
        });

        // Articles that belong to a blog and may have more than one author
        Schema::create('articles', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED']);
            // -- Review --
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedInteger('reviewer_id')->nullable();
            $table->string('review_message')->nullable();
            // ------------
            $table->unsignedInteger('blog_id');
            $table->foreign('blog_id')->references('id')->on('blogs');
        });

        // Pivot table users[0,*] <-> blogs[1,*]
        Schema::create('blogs_contributors', function (Blueprint $table) {
            $table->unsignedInteger('blog_id');
            $table->foreign('blog_id')->references('id')->on('blogs');
            $table->unsignedInteger('contributor_id');
            $table->foreign('contributor_id')->references('id')->on('users');
            $table->boolean('is_owner');
            $table->primary(['blog_id', 'contributor_id']);
        });

        // Pivot table users[0,*] <-> articles[1,*]
        Schema::create('articles_authors', function (Blueprint $table) {
            $table->unsignedInteger('article_id');
            $table->foreign('article_id')->references('id')->on('articles');
            $table->unsignedInteger('author_id');
            $table->foreign('author_id')->references('id')->on('users');
            $table->primary(['article_id', 'author_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('threads');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('basic_threads');
        Schema::dropIfExists('blogs');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('blogs_contributors');
        Schema::dropIfExists('articles_authors');
    }
}
