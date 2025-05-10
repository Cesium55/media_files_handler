<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('clips', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('video_id')->constrained('videos')->onDelete('cascade');
            $table->string('video_path')->nullable();
            $table->string('thumb_path')->nullable();
            $table->json('subs')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clips');
    }
};
