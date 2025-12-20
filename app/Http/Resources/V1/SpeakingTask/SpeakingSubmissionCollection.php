<?php

namespace App\Http\Resources\V1\SpeakingTask;

use App\Http\Resources\BasePaginatedCollection;

class SpeakingSubmissionCollection extends BasePaginatedCollection
{
    public $collects = SpeakingSubmissionResource::class;
}