<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_color' => $this->status_color,
            'priority' => $this->priority,
            'priority_color' => $this->priority_color,
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'time_difference' => $this->time_difference,
            'tags' => $this->tags,
            'notes' => $this->notes,
            'is_overdue' => $this->isOverdue(),
            'is_completed' => $this->isCompleted(),
            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'name' => $this->project->name,
                    'slug' => $this->project->slug,
                ];
            }),
            'assignee' => $this->whenLoaded('assignee', function () {
                return [
                    'id' => $this->assignee->id,
                    'full_name' => $this->assignee->full_name,
                    'avatar_url' => $this->assignee->avatar_url,
                    'email' => $this->assignee->email,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'full_name' => $this->creator->full_name,
                    'avatar_url' => $this->creator->avatar_url,
                    'email' => $this->creator->email,
                ];
            }),
            'files_count' => $this->when(isset($this->files_count), $this->files_count),
            'files' => $this->whenLoaded('files', function () {
                return $this->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'filename' => $file->filename,
                        'original_filename' => $file->original_filename,
                        'mime_type' => $file->mime_type,
                        'size' => $file->size,
                        'human_size' => $file->human_size,
                        'url' => $file->url,
                        'download_url' => $file->download_url,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
