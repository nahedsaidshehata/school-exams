<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningOutcomesTable extends Migration
{
    public function up()
    {
        Schema::create('learning_outcomes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Optional scoping (helps browsing/filtering)
            $table->uuid('material_id')->nullable()->index();
            $table->uuid('section_id')->nullable()->index();

            $table->string('code')->nullable()->index(); // e.g. SS-G6-LO-03
            $table->string('title_ar');
            $table->string('title_en')->nullable();

            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();

            $table->string('grade_level')->nullable()->index(); // e.g. "6" or "G6"

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('learning_outcomes');
    }
}
