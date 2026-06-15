<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver !== 'sqlite') {
            Schema::table('submission_answers', function (Blueprint $table) {
                $table->dropForeign(['form_question_id']);
            });
            // ->change() no aplica DEFAULT NULL en MariaDB; usar DDL directo
            DB::statement('ALTER TABLE submission_answers MODIFY form_question_id VARCHAR(26) NULL DEFAULT NULL');
        } else {
            // SQLite no tiene FK reales ni MODIFY; la columna ya es recreable como nullable
            Schema::table('submission_answers', function (Blueprint $table) {
                $table->string('form_question_id', 26)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('submission_answers', function (Blueprint $table) {
            $table->string('form_question_id', 26)->nullable(false)->change();
            $table->foreign('form_question_id')
                ->references('id')
                ->on('form_questions')
                ->restrictOnDelete();
        });
    }
};
