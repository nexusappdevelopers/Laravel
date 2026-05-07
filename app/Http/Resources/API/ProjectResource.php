<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'status' => $this->status,
            'status_color' => $this->status_color,
            'priority' => $this->priority,
            'priority_color' => $this->priority_color,
            'budget' => $this->budget,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'duration' => $this->duration,
            'progress_percentage' => $this->progress_percentage,
            'repository_url' => $this->repository_url,
            'demo_url' => $this->demo_url,
            'production_url' => $this->production_url,
            'notes' => $this->notes,
            'is_overdue' => $this->isOverdue(),
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'slug' => $this->company->slug,
                    'logo_url' => $this->company->logo_url,
                ];
            }),
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'full_name' => $this->client->full_name,
                    'avatar_url' => $this->client->avatar_url,
                    'email' => $this->client->email,
                ];
            }),
            'project_manager' => $this->whenLoaded('projectManager', function () {
                return [
                    'id' => $this->projectManager->id,
                    'full_name' => $this->projectManager->full_name,
                    'avatar_url' => $this->projectManager->avatar_url,
                    'email' => $this->projectManager->email,
                ];
            }),
            'team_members' => $this->whenLoaded('teamMembers', function () {
                return $this->teamMembers->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'full_name' => $member->full_name,
                        'avatar_url' => $member->avatar_url,
                        'email' => $member->email,
                    ];
                });
            }),
            'tasks_count' => $this->when(isset($this->tasks_count), $this->tasks_count),
            'completed_tasks_count' => $this->when(isset($this->completed_tasks_count), $this->completed_tasks_count),
            'pending_tasks_count' => $this->when(isset($this->pending_tasks_count), $this->pending_tasks_count),
            'files_count' => $this->when(isset($this->files_count), $this->files_count),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'full_name' => $this->creator->full_name,
                    'avatar_url' => $this->creator->avatar_url,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
