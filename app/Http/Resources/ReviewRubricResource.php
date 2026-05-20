<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewRubricResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->resource;
        
        $data['links'] = [
            [
                'rel' => 'submit_review',
                'method' => 'POST',
                // Assuming rubric is queried per manuscript, we might need manuscript ID here,
                // but for simplicity in this mock, we just return the rubric criteria.
                'href' => url('/api/reviewer/manuscripts/'.request()->route('manuscriptId').'/review')
            ]
        ];

        return $data;
    }
}
