<?php

namespace App\Helpers;

use App\Models\TestQuestion;

class ReadingCleanupHelper
{
    /**
     * Delete all images associated with the given question.
     */
    public static function deleteQuestionImages(TestQuestion $question): void
    {
        $data = $question->question_data ?? [];

        $paths = $data['image_path'] ?? $data['images'] ?? [];

        if (!is_array($paths)) {
            $paths = [$paths];
        }

        foreach ($paths as $path) {
            $deletePath = str_replace('/storage/', '', $path);
            FileUploadHelper::delete($deletePath);
        }
    }
}
