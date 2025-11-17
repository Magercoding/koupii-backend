<?php

namespace App\Http\Controllers\V1\ReadingTest;

use App\Helpers\ReadingCleanupHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ReadingTest\StoreReadingTestRequest;
use App\Http\Requests\V1\ReadingTest\UpdateReadingTestRequest;
use App\Http\Resources\V1\ReadingTest\ReadingTestResource;
use App\Models\Test;

use App\Models\TestQuestion;
use App\Services\Reading\ReadingTestDeleteService;
use App\Services\Reading\ReadingTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReadingTestQuestionController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = Test::reading()->with([
            'passages.questionGroups.questions.options',
            'passages.questionGroups.questions.breakdowns.highlightSegments',
            'creator',
        ]);

        $query->when($user->role === 'student', fn($q) => $q->where('is_published', true));
        $query->when(!in_array($user->role, ['admin', 'student']), fn($q) => $q->where('creator_id', $user->id));

        $tests = $query->get();

        return response()->json([
            'message' => 'Reading tests retrieved successfully',
            'data' => ReadingTestResource::collection($tests),
        ], 200);
    }
    public function store(StoreReadingTestRequest $request, ReadingTestService $service)
    {
        $test = $service->create($request->validated(), $request);

        return response()->json([
            'message' => 'Reading test created successfully',
            'test_id' => $test->id,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = auth()->user();

        $query = Test::with([
            'creator',
            'passages.questionGroups.questions.options',
            'passages.questionGroups.questions.breakdowns.highlightSegments'
        ])
            ->where('type', 'reading')
            ->where('id', $id);

        if ($user->role === 'student') {
            $query->where('is_published', true);
        } elseif ($user->role !== 'admin') {
            $query->where('creator_id', $user->id);
        }

        $test = $query->first();

        if (!$test) {
            return response()->json([
                'message' => 'Test not found or unauthorized access',
            ], 404);
        }

        return new ReadingTestResource($test);
    }

    public function update(UpdateReadingTestRequest $request, $id)
    {
        $test = Test::find($id);

        if (!$test) {
            return response()->json(['message' => 'Test not found'], 404);
        }

        if ($test->creator_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            DB::beginTransaction();

            (new ReadingTestService())->updateTest($test, $request->validated(), $request);

            DB::commit();
            return response()->json(['message' => 'Test updated', 'test_id' => $test->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Update failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function deletePassage($id)
    {
        $service = new ReadingTestDeleteService();
        $response = $service->deletePassage($id);

        return response()->json(
            ['message' => $response['message'] ?? $response['error']],
            $response['status']
        );
    }

    public function deleteQuestion($id)
    {
        $service = new ReadingTestDeleteService();
        $response = $service->deleteQuestion($id);

        return response()->json(
            [
                'message' => $response['message'] ?? $response['error'],
                'group_deleted' => $response['group_deleted'] ?? null
            ],
            $response['status']
        );
    }



    public function deleteTest(string $id): array
    {
        $test = Test::find($id);
        if (!$test) {
            return ['error' => 'Test not found', 'status' => 404];
        }

        if (!auth()->user()->isAdmin() && $test->creator_id !== auth()->id()) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        DB::transaction(function () use ($id, $test) {

     
            $questions = TestQuestion::whereHas('questionGroup.passage', function ($query) use ($id) {
                $query->where('test_id', $id);
            })->get();

          
            foreach ($questions as $question) {
                ReadingCleanupHelper::deleteQuestionImages($question);
            }

        
            $test->delete();
        });

        return ['message' => 'Test deleted successfully', 'status' => 200];
    }





}
