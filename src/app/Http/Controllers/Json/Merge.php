<?php

namespace LaravelEnso\Localisation\app\Http\Controllers\Json;

use LaravelEnso\Localisation\app\Services\Json\Merger;

class Merge
{
    public function __invoke($locale = null)
    {
        (new Merger())
            ->run($locale);

        return [
            'message' => __('The language files were successfully merged'),
        ];
    }
}
