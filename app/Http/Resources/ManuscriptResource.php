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
        $isModel = is_object($this->resource);

        $id = $isModel ? $this->id : ($this->resource['id'] ?? null);
        $title = $isModel ? $this->title : ($this->resource['title'] ?? '');
        $book_type = $isModel ? $this->book_type : ($this->resource['book_type'] ?? '');
        $status = $isModel ? $this->status : ($this->resource['status'] ?? '');
        
        $author = '';
        if ($isModel) {
            $author = $this->author ? $this->author->name : '';
        } else {
            $author = $this->resource['author'] ?? '';
        }

        $submitted_at = null;
        if ($isModel) {
            $submitted_at = $this->created_at ? $this->created_at->toIso8601String() : null;
        } else {
            $submitted_at = $this->resource['submitted_at'] ?? null;
        }

        $abstract = $isModel ? $this->abstract : ($this->resource['abstract'] ?? null);
        
        $file_url = null;
        if ($isModel) {
            $initialFile = $this->files()->where('file_type', 'initial')->first();
            $file_url = $initialFile ? url($initialFile->file_path) : null;
        } else {
            $file_url = $this->resource['file_url'] ?? null;
        }

        $reviewers = [];
        $tenggat = '';
        if ($isModel) {
            $seen = [];
            foreach ($this->reviewSubmissions ?? [] as $sub) {
                if ($sub->reviewer && !in_array($sub->reviewer->id, $seen)) {
                    $seen[] = $sub->reviewer->id;
                    $reviewers[] = [
                        'id'   => $sub->reviewer->id,
                        'name' => $sub->reviewer->name,
                    ];
                }
            }
            $firstSub = $this->reviewSubmissions->first();
            if ($firstSub && $firstSub->deadline) {
                $tenggat = $firstSub->deadline->translatedFormat('j F Y');
            }
        } else {
            $reviewers = $this->resource['reviewers'] ?? [];
            $tenggat = $this->resource['tenggat'] ?? '';
        }

        $data = [
            'id' => $id,
            'title' => $title,
            'book_type' => $book_type,
            'status' => $status,
            'author' => $author,
            'submitted_at' => $submitted_at,
            'abstract' => $abstract,
            'file_url' => $file_url,
            'reviewers' => $reviewers,
            'tenggat' => $tenggat,
        ];

        // HATEOAS Links
        $links = [];

        // Admin link: Assign Reviewer (if manuscript is unassigned)
        if ($status === 'initial_draft_uploaded') {
            $links[] = [
                'rel' => 'assign_reviewer',
                'method' => 'POST',
                'href' => url("/api/admin/manuscripts/{$id}/assign-reviewer")
            ];
        }

        // Reviewer links
        if (in_array($status, ['reviewer_assigned', 'under_review', 'review_in_progress'])) {
            $links[] = [
                'rel' => 'get_details',
                'method' => 'GET',
                'href' => url("/api/reviewer/manuscripts/{$id}")
            ];
            $links[] = [
                'rel' => 'get_rubric',
                'method' => 'GET',
                'href' => url("/api/reviewer/manuscripts/{$id}/rubric")
            ];
            $links[] = [
                'rel' => 'submit_review',
                'method' => 'POST',
                'href' => url("/api/reviewer/manuscripts/{$id}/review")
            ];
        }

        // General link to get compiled reviews
        if (in_array($status, ['review_completed', 'revised', 'approved'])) {
            $links[] = [
                'rel' => 'compiled_reviews',
                'method' => 'GET',
                'href' => url("/api/manuscripts/{$id}/compiled-reviews")
            ];
            $links[] = [
                'rel' => 'download_pdf',
                'method' => 'GET',
                'href' => url("/api/manuscripts/{$id}/download-pdf")
            ];
        }

        if (!empty($links)) {
            $data['links'] = $links;
        }

        return $data;
    }
}
