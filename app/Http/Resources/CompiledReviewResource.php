<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompiledReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->resource;
        
        // HATEOAS: What can be done after viewing compiled reviews?
        // Usually, an author might submit a revision.
        $data['links'] = [
            [
                'rel' => 'upload_revision',
                'method' => 'POST',
                'href' => url('/api/author/manuscripts/'.request()->route('manuscriptId').'/revisions')
            ]
        ];

        return $data;
    }
}
