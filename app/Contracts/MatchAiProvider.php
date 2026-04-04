<?php

namespace App\Contracts;

use App\Support\Matching\MatchAiInput;
use App\Support\Matching\MatchAiResult;

interface MatchAiProvider
{
    /**
     * Avalia a similaridade entre um pet perdido e um avistamento.
     */
    public function evaluate(MatchAiInput $input): MatchAiResult;
}
