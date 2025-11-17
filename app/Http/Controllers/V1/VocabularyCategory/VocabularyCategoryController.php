<?php

namespace App\Http\Controllers\V1\VocabularyCategory;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\VocabularyCategory\UpdateVocabularyCategoryRequest;
use App\Http\Resources\V1\VocabularyCategory\VocabularyCategoryResource;
use App\Models\VocabularyCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VocabularyCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = VocabularyCategory::search($request->search)
            ->perPage($request->per_page);

        return VocabularyCategoryResource::collection($categories);
    }
    public function show($id)
    {
        $category = VocabularyCategory::findOrFail($id);

        return new VocabularyCategoryResource($category);
    }
    public function update(UpdateVocabularyCategoryRequest $request, $id)
    {
        $category = VocabularyCategory::find($id);

        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        DB::beginTransaction();
        try {
            $category->update($request->validated());

            DB::commit();

            return response()->json([
                'message' => 'Category updated successfully',
                'data' => new VocabularyCategoryResource($category)
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy($id)
    {
        $category = VocabularyCategory::findOrFail($id);


        $category->delete();
        
        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
}
