<?php

namespace App\Http\Resources\V1\SpeakingTask;

use App\Http\Resources\BasePaginatedCollection;

class SpeakingTaskCollection extends BasePaginatedCollection
{
    public $collects = SpeakingTaskResource::class;
}