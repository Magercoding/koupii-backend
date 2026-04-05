<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ── 1. reading_task_assignments ──────────────────────────────────
        if (Schema::hasTable('reading_task_assignments')) {
            foreach (DB::table('reading_task_assignments')->get() as $row) {
                if (DB::table('assignments')->where('id', $row->id)->exists()) {
                    continue;
                }

                $taskId = $row->reading_task_id ?? null;
                $title  = $taskId ? DB::table('reading_tasks')->where('id', $taskId)->value('title') : null;

                DB::table('assignments')->insert([
                    'id'          => $row->id,
                    'class_id'    => $row->class_id ?? $row->classroom_id,
                    'task_id'     => $taskId,
                    'task_type'   => 'reading_task',
                    'assigned_by' => $row->assigned_by ?? null,
                    'title'       => $title ?? 'Reading Assignment',
                    'due_date'    => $row->due_date ?? null,
                    'is_published'=> true,
                    'max_attempts'=> $row->max_attempts ?? 3,
                    'instructions'=> $row->instructions ?? null,
                    'status'      => $row->status ?? 'active',
                    'source_type' => 'manual',
                    'type'        => 'reading',
                    'created_at'  => $row->created_at ?? $now,
                    'updated_at'  => $row->updated_at ?? $now,
                ]);
            }

            Schema::dropIfExists('reading_task_assignments');
        }

        // ── 2. listening_task_assignments ────────────────────────────────
        if (Schema::hasTable('listening_task_assignments')) {
            foreach (DB::table('listening_task_assignments')->get() as $row) {
                if (DB::table('assignments')->where('id', $row->id)->exists()) {
                    continue;
                }

                $taskId = $row->listening_task_id ?? null;
                $title  = $taskId ? DB::table('listening_tasks')->where('id', $taskId)->value('title') : null;

                DB::table('assignments')->insert([
                    'id'          => $row->id,
                    'class_id'    => $row->class_id ?? null,
                    'task_id'     => $taskId,
                    'task_type'   => 'listening_task',
                    'assigned_by' => $row->assigned_by ?? null,
                    'title'       => $title ?? 'Listening Assignment',
                    'due_date'    => $row->due_date ?? null,
                    'is_published'=> true,
                    'max_attempts'=> $row->max_attempts ?? 3,
                    'instructions'=> $row->instructions ?? null,
                    'status'      => $row->status ?? 'active',
                    'source_type' => 'manual',
                    'type'        => 'listening',
                    'created_at'  => $row->created_at ?? $now,
                    'updated_at'  => $row->updated_at ?? $now,
                ]);
            }

            Schema::dropIfExists('listening_task_assignments');
        }

        // ── 3. writing_task_assignments ──────────────────────────────────
        if (Schema::hasTable('writing_task_assignments')) {
            foreach (DB::table('writing_task_assignments')->get() as $row) {
                if (DB::table('assignments')->where('id', $row->id)->exists()) {
                    continue;
                }

                $taskId = $row->writing_task_id ?? null;
                $title  = $taskId ? DB::table('writing_tasks')->where('id', $taskId)->value('title') : null;

                DB::table('assignments')->insert([
                    'id'          => $row->id,
                    'class_id'    => $row->class_id ?? $row->classroom_id,
                    'task_id'     => $taskId,
                    'task_type'   => 'writing_task',
                    'assigned_by' => $row->assigned_by ?? null,
                    'title'       => $title ?? 'Writing Assignment',
                    'due_date'    => $row->due_date ?? null,
                    'is_published'=> true,
                    'max_attempts'=> $row->max_attempts ?? 3,
                    'instructions'=> $row->instructions ?? null,
                    'status'      => $row->status ?? 'active',
                    'source_type' => 'manual',
                    'type'        => 'writing',
                    'created_at'  => $row->created_at ?? $now,
                    'updated_at'  => $row->updated_at ?? $now,
                ]);
            }

            Schema::dropIfExists('writing_task_assignments');
        }

        // ── 4. speaking_task_assignments ─────────────────────────────────
        if (Schema::hasTable('speaking_task_assignments')) {
            foreach (DB::table('speaking_task_assignments')->get() as $row) {
                if (DB::table('assignments')->where('id', $row->id)->exists()) {
                    continue;
                }

                $taskId = $row->speaking_task_id ?? null;
                $title  = $taskId ? DB::table('speaking_tasks')->where('id', $taskId)->value('title') : null;

                DB::table('assignments')->insert([
                    'id'          => $row->id,
                    'class_id'    => $row->class_id,
                    'task_id'     => $taskId,
                    'task_type'   => 'speaking_task',
                    'assigned_by' => $row->assigned_by ?? null,
                    'title'       => $title ?? 'Speaking Assignment',
                    'due_date'    => $row->due_date ?? null,
                    'is_published'=> true,
                    'max_attempts'=> $row->max_attempts ?? 3,
                    'status'      => 'active',
                    'source_type' => 'manual',
                    'type'        => 'speaking',
                    'created_at'  => $row->created_at ?? $now,
                    'updated_at'  => $row->updated_at ?? $now,
                ]);
            }

            Schema::dropIfExists('speaking_task_assignments');
        }

        // ── 5. Normalise type column: strip _task suffix on any remaining rows ─
        DB::table('assignments')
            ->whereIn('type', ['reading_task', 'listening_task', 'speaking_task', 'writing_task'])
            ->get()
            ->each(function ($row) {
                DB::table('assignments')
                    ->where('id', $row->id)
                    ->update(['type' => str_replace('_task', '', $row->type)]);
            });
    }

    public function down(): void
    {
        throw new \RuntimeException('This migration cannot be reversed safely.');
    }
};
