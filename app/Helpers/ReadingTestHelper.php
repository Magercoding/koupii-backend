<?php

namespace App\Helpers;

use App\Helpers\FileUploadHelper;

class ReadingTestHelper
{
    public static function syncImages($question, $request, $imageKey, $qData)
    {
        $currentPaths = $question->question_data['image_path'] ?? [];
        $newPaths = [];

        // uploaded new images
        if ($request->hasFile($imageKey)) {
            foreach ($currentPaths as $old) {
                FileUploadHelper::delete(str_replace('/storage/', '', $old));
            }
            $currentPaths = [];

            foreach ($request->file($imageKey) as $img) {
                $newPaths[] = FileUploadHelper::upload($img, 'question_images');
            }
        }

        // remove selected images
        if (!empty($qData['remove_images']) && !$newPaths) {
            foreach ($qData['remove_images'] as $remove) {
                FileUploadHelper::delete(str_replace('/storage/', '', $remove));
            }
            $currentPaths = array_values(array_diff($currentPaths, $qData['remove_images']));
        }

        return $newPaths ?: $currentPaths;
    }
}
