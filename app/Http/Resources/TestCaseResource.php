<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'input' => $this->input,
            'expected_output' => $this->formatExpectedOutput($this->expected_output),
            'is_sample' => $this->is_sample,
        ];
    }

    private function formatExpectedOutput($value)
    {
        if ($value === null) {
            return null;
        }

        $decodedValue = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $value = $decodedValue;
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        return $value;
    }
}