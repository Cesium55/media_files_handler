<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('language')->default("en");
            $table->string('video_path')->nullable();
            $table->string('thumb_path')->nullable();
            $table->boolean('subs_cutted')->default(false);
            $table->boolean('video_processed')->default(false);
            $table->json('subs')->nullable();
            $table->json('clip_intervals')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('videos');
    }
};
