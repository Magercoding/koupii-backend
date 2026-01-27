<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Classes;
use App\Models\ClassEnrollment;
use App\Models\Assignment;
use App\Models\StudentAssignment;
use App\Events\StudentEnrolledInClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class StudentEnrollmentEventDispatchTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Classes $class;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create(['role' => 'student']);
        $this->class = Classes::factory()->create([
            'teacher_id' => $this->teacher->id,
            'class_code' => 'TEST123'
        ]);
    }

    public function test_student_enrolled_in_class_event_dispatched_on_direct_enrollment()
    {
        Event::fake();

        // Create some assignments for the class
        $assignment = Assignment::factory()->create([
            'class_id' => $this->class->id,
            'is_published' => true
        ]);

        // Act as teacher and create enrollment
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/enrollments/create', [
                'class_id' => $this->class->id,
                'student_id' => $this->student->id,
                'status' => 'active',
                'enrolled_at' => now()
            ]);

        $response->assertStatus(200);

        // Verify event was dispatched
        Event::assertDispatched(StudentEnrolledInClass::class, function ($event) {
            return $event->student->id === $this->student->id &&
                   $event->class->id === $this->class->id;
        });
    }

    public function test_student_enrolled_in_class_event_dispatched_on_join_by_code()
    {
        Event::fake();

        // Create some assignments for the class
        $assignment = Assignment::factory()->create([
            'class_id' => $this->class->id,
            'is_published' => true
        ]);

        // Act as student and join class by code
        $response = $this->actingAs($this->student)
            ->postJson('/api/v1/classes/join', [
                'class_code' => $this->class->class_code
            ]);

        $response->assertStatus(200);

        // Verify event was dispatched
        Event::assertDispatched(StudentEnrolledInClass::class, function ($event) {
            return $event->student->id === $this->student->id &&
                   $event->class->id === $this->class->id;
        });
    }

    public function test_student_enrolled_in_class_event_dispatched_on_enrollment_reactivation()
    {
        Event::fake();

        // Create inactive enrollment
        $enrollment = ClassEnrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'inactive',
            'enrolled_at' => now()->subDays(7)
        ]);

        // Create some assignments for the class
        $assignment = Assignment::factory()->create([
            'class_id' => $this->class->id,
            'is_published' => true
        ]);

        // Act as student and join class by code (should reactivate)
        $response = $this->actingAs($this->student)
            ->postJson('/api/v1/classes/join', [
                'class_code' => $this->class->class_code
            ]);

        $response->assertStatus(200);

        // Verify event was dispatched for reactivation
        Event::assertDispatched(StudentEnrolledInClass::class, function ($event) {
            return $event->student->id === $this->student->id &&
                   $event->class->id === $this->class->id;
        });
    }

    public function test_student_enrolled_in_class_event_dispatched_on_enrollment_update_to_active()
    {
        Event::fake();

        // Create inactive enrollment
        $enrollment = ClassEnrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'inactive',
            'enrolled_at' => now()->subDays(7)
        ]);

        // Create some assignments for the class
        $assignment = Assignment::factory()->create([
            'class_id' => $this->class->id,
            'is_published' => true
        ]);

        // Act as teacher and update enrollment to active
        $response = $this->actingAs($this->teacher)
            ->patchJson("/api/v1/classes/enrollments/update/{$enrollment->id}", [
                'status' => 'active'
            ]);

        $response->assertStatus(200);

        // Verify event was dispatched
        Event::assertDispatched(StudentEnrolledInClass::class, function ($event) {
            return $event->student->id === $this->student->id &&
                   $event->class->id === $this->class->id;
        });
    }

    public function test_student_enrolled_in_class_event_not_dispatched_for_inactive_enrollment()
    {
        Event::fake();

        // Act as teacher and create inactive enrollment
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/enrollments/create', [
                'class_id' => $this->class->id,
                'student_id' => $this->student->id,
                'status' => 'inactive',
                'enrolled_at' => now()
            ]);

        $response->assertStatus(200);

        // Verify event was NOT dispatched for inactive enrollment
        Event::assertNotDispatched(StudentEnrolledInClass::class);
    }

    public function test_event_listener_creates_assignments_for_newly_enrolled_student()
    {
        // Don't fake events - let them run naturally
        
        // Create some assignments for the class
        $assignment1 = Assignment::factory()->create([
            'class_id' => $this->class->id,
            'is_published' => true
        ]);
        
        $assignment2 = Assignment::factory()->create([
            'class_id' => $this->class->id,
            'is_published' => true
        ]);

        // Create unpublished assignment (should not create student assignment)
        $unpublishedAssignment = Assignment::factory()->create([
            'class_id' => $this->class->id,
            'is_published' => false
        ]);

        // Act as student and join class by code
        $response = $this->actingAs($this->student)
            ->postJson('/api/v1/classes/join', [
                'class_code' => $this->class->class_code
            ]);

        $response->assertStatus(200);

        // Verify student assignments were created for published assignments
        $this->assertDatabaseHas('student_assignments', [
            'assignment_id' => $assignment1->id,
            'student_id' => $this->student->id,
            'status' => 'not_started'
        ]);

        $this->assertDatabaseHas('student_assignments', [
            'assignment_id' => $assignment2->id,
            'student_id' => $this->student->id,
            'status' => 'not_started'
        ]);

        // Verify no student assignment for unpublished assignment
        $this->assertDatabaseMissing('student_assignments', [
            'assignment_id' => $unpublishedAssignment->id,
            'student_id' => $this->student->id
        ]);
    }
}