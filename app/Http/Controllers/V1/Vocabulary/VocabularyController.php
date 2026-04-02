<?php

namespace App\Http\Controllers\V1\Vocabulary;

use App\Helpers\FileUploadHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vocabulary\StoreVocabularyRequest;
use App\Http\Requests\V1\Vocabulary\UpdateVocabularyRequest;
use App\Http\Resources\V1\Vocabulary\VocabularyResource;
use App\Models\Vocabulary;
use App\Models\VocabularyBookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VocabularyController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $classId = $request->query('class_id');

        $query = match ($user->role) {
            'admin' => Vocabulary::forAdmin(),
            'teacher' => Vocabulary::forTeacher($user->id),
            'student' => Vocabulary::forStudent($user->id),
            default => null,
        };

        if (!$query) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($classId) {
            $query->whereHas('classes', function ($q) use ($classId) {
                $q->where('classes.id', $classId);
            });
        }

        $vocabularies = $query->get();

        if ($user->role === 'student') {
            $vocabularies->each(fn($v) => $v->is_bookmarked = $v->bookmarks->first()->is_bookmarked ?? false);
        }

        if (!$vocabularies) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return VocabularyResource::collection($vocabularies);
    }

    public function store(StoreVocabularyRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            if ($request->hasFile('audio_file_path')) {
                $data['audio_file_path'] = FileUploadHelper::upload(
                    $request->file('audio_file_path'),
                    'audio'
                );
            }

            $data['teacher_id'] = auth()->user()->id;

            $vocabulary = Vocabulary::create($data);
            
            if ($request->has('class_ids')) {
                $vocabulary->classes()->sync($request->class_ids);
            }

            DB::commit();

            return response()->json([
                'message' => 'Vocabulary created successfully',
                'data' => new VocabularyResource($vocabulary)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $vocabulary = Vocabulary::with([
            'teacher:id,name',
            'category:id,name,color_code',
        ])->findOrFail($id);

        if (!$vocabulary) {
            return response()->json(['error' => 'Vocabulary not found'], 404);
        }

        return new VocabularyResource($vocabulary);
    }
    public function update(UpdateVocabularyRequest $request, $id)
    {
        $vocabulary = Vocabulary::find($id);

        if (!$vocabulary) {
            return response()->json(['error' => 'Vocabulary not found'], 404);
        }
      
        Gate::authorize('update', $vocabulary);

        $data = $request->validated();

        if (isset($data['word'])) {
            $exists = Vocabulary::where('word', $data['word'])
                ->where('id', '!=', $vocabulary->id)
                ->where('teacher_id', auth()->id())
                ->exists();

            if ($exists) {
                return response()->json(['error' => 'Vocabulary word already exists'], 422);
            }
        }

        DB::beginTransaction();
        try {
            if ($request->hasFile('audio_file_path')) {
                if ($vocabulary->audio_file_path) {
                    FileUploadHelper::delete($vocabulary->audio_file_path);
                }

                $data['audio_file_path'] = FileUploadHelper::upload(
                    $request->file('audio_file_path'),
                    'audio'
                );
            }

            $vocabulary->update($data);
            
            if ($request->has('class_ids')) {
                $vocabulary->classes()->sync($request->class_ids);
            }

            DB::commit();

            return response()->json([
                'message' => 'Vocabulary updated successfully',
                'data' => $vocabulary,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $vocabulary = Vocabulary::findOrFail($id);

        if (!$vocabulary) {
            return response()->json(['error' => 'Vocabulary not found'], 404);
        }

    
        Gate::authorize('delete', $vocabulary);

        if ($vocabulary->audio_file_path) {
            FileUploadHelper::delete($vocabulary->audio_file_path);
        }

        $vocabulary->delete();

        return response()->json(['message' => 'Vocabulary deleted successfully'], 200);
    }




    public function toggleBookmark($id)
    {
        $user = Auth::user();

        $vocabulary = Vocabulary::find($id);
        if (!$vocabulary) {
            return response()->json(['error' => 'Vocabulary not found'], 404);
        }

        $bookmark = VocabularyBookmark::firstOrCreate(
            [
                'user_id' => $user->id,
                'vocabulary_id' => $vocabulary->id,
            ],
            [
                'is_bookmarked' => false,
            ]
        );

    
        $bookmark->is_bookmarked = !$bookmark->is_bookmarked;
        $bookmark->save();

        return response()->json([
            'message' => $bookmark->is_bookmarked
                ? 'Vocabulary bookmarked.'
                : 'Bookmark removed.',
        ]);
    }


    public function bulkAssign(Request $request)
    {
        $request->validate([
            'vocabulary_ids' => 'required|array',
            'vocabulary_ids.*' => 'exists:vocabularies,id',
            'class_ids' => 'required|array',
            'class_ids.*' => 'exists:classes,id',
        ]);

        foreach ($request->vocabulary_ids as $vocabId) {
            $vocabulary = Vocabulary::find($vocabId);
            if ($vocabulary) {
                $vocabulary->classes()->syncWithoutDetaching($request->class_ids);
            }
        }

        return response()->json([
            'message' => 'Vocabularies assigned successfully'
        ]);
    }
}
