<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLessonLearningOutcomeTable extends Migration
{
    public function up()
    {
        Schema::create('lesson_learning_outcome', function (Blueprint $table) {
            $table->uuid('lesson_id');
            $table->uuid('learning_outcome_id');
            $table->timestamps();

            // Composite PK
            $table->primary(['lesson_id', 'learning_outcome_id']);

            // FK + cascade delete
            $table->foreign('lesson_id')
                ->references('id')->on('lessons')
                ->onDelete('cascade');

            $table->foreign('learning_outcome_id')
                ->references('id')->on('learning_outcomes')
                ->onDelete('cascade');

            // (اختياري) اندكس مساعد - مش ضروري لأن PK مركب
            // $table->index('lesson_id');
            // $table->index('learning_outcome_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lesson_learning_outcome');
    }
}
