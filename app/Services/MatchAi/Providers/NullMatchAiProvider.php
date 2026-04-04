<?php

namespace App\Services\MatchAi\Providers;

use App\Contracts\MatchAiProvider;
use App\Support\Matching\MatchAiInput;
use App\Support\Matching\MatchAiResult;

class NullMatchAiProvider implements MatchAiProvider
{
    public function evaluate(MatchAiInput $input): MatchAiResult
    {
        return MatchAiResult::failed('null', 'null');
    }
}
