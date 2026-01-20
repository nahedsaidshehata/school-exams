<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionLearningOutcomeTable extends Migration
{
    public function up()
    {
        Schema::create('question_learning_outcome', function (Blueprint $table) {
            $table->uuid('question_id');
            $table->uuid('learning_outcome_id');

            // Optional: primary vs secondary coverage
            $table->string('coverage_level')->default('PRIMARY'); // PRIMARY | SECONDARY

            $table->timestamps();

            $table->primary(['question_id', 'learning_outcome_id']);

            $table->index('question_id');
            $table->index('learning_outcome_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('question_learning_outcome');
    }
}
