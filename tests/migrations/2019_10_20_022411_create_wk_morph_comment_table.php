<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateWkMorphCommentTable extends Migration
{
    public function up()
    {
        Schema::create(config('wk-core.table.morph-comment.comments'), function (Blueprint $table) {
            $table->uuid('id');
            $table->nullableUuidMorphs('morph');
            $table->uuid('user_id')->nullable();
            $table->float('score')->nullable();
            $table->json('options')->nullable();
            $table->json('addresses')->nullable();
            $table->boolean('is_private')->default(0);
            $table->boolean('is_highlighted')->default(0);
            $table->boolean('is_enabled')->default(0);
            $table->timestamp('edit_at')->nullable();

            $table->timestampsTz();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')
                  ->on(config('wk-core.table.user'))
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->primary('id');
            $table->index('score');
            $table->index('is_private');
            $table->index('is_highlighted');
            $table->index('is_enabled');
        });
        if (!config('wk-morph-comment.onoff.core-lang_core')) {
            Schema::create(config('wk-core.table.morph-comment.comments_lang'), function (Blueprint $table) {
                $table->uuid('id');
                $table->uuidMorphs('morph');
                $table->uuid('user_id')->nullable();
                $table->string('code');
                $table->string('key');
                $table->longText('value')->nullable();
                $table->boolean('is_current')->default(1);

                $table->timestampsTz();
                $table->softDeletes();

                $table->foreign('user_id')->references('id')
                    ->on(config('wk-core.table.user'))
                    ->onDelete('set null')
                    ->onUpdate('cascade');

                $table->primary('id');
            });
        }
    }

    public function down() {
        Schema::dropIfExists(config('wk-core.table.morph-comment.comments_lang'));
        Schema::dropIfExists(config('wk-core.table.morph-comment.comments'));
    }
}
