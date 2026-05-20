<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManuscriptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // For mock purposes, we assume $this->resource is an array
        $data = $this->resource;

        // Implement Hypermedia (HATEOAS)
        $links = [];

        // Admin link: Assign Reviewer (if unassigned)
        if (isset($data['status']) && $data['status'] === 'unassigned') {
            $links[] = [
                'rel' => 'assign_reviewer',
                'method' => 'POST',
                'href' => url("/api/admin/manuscripts/{$data['id']}/assign-reviewer")
            ];
        }

        // Reviewer links
        if (isset($data['status']) && $data['status'] === 'review_in_progress') {
            $links[] = [
                'rel' => 'get_details',
                'method' => 'GET',
                'href' => url("/api/reviewer/manuscripts/{$data['id']}")
            ];
            $links[] = [
                'rel' => 'get_rubric',
                'method' => 'GET',
                'href' => url("/api/reviewer/manuscripts/{$data['id']}/rubric")
            ];
            $links[] = [
                'rel' => 'submit_review',
                'method' => 'POST',
                'href' => url("/api/reviewer/manuscripts/{$data['id']}/review")
            ];
        }

        // General link to get compiled reviews
        if (isset($data['status']) && in_array($data['status'], ['review_completed', 'revised', 'approved'])) {
            $links[] = [
                'rel' => 'compiled_reviews',
                'method' => 'GET',
                'href' => url("/api/manuscripts/{$data['id']}/compiled-reviews")
            ];
        }

        if (!empty($links)) {
            $data['links'] = $links;
        }

        return $data;
    }
}
