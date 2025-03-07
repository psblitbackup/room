<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return [
        //     'id' => $this->id,
        //     'visitor_name' => $this->visitor_name,
        //     'visitor_phone' => $this->visitor_phone,
        //     'visitor_email' => $this->visitor_email,
        //     'status' => $this->status,
        //     'agent' => $this->when($this->agent, function () {
        //         return [
        //             'id' => $this->agent->id,
        //             'name' => $this->agent->user->name,
        //             'status' => $this->agent->status,
        //         ];
        //     }),
        //     'notes' => $this->when($request->user()?->hasRole('admin'), $this->notes),
        //     'created_at' => $this->created_at->toISOString(),
        //     'ended_at' => $this->ended_at?->toISOString(),
        //     'messages' => ChatMessageResource::collection($this->whenLoaded('messages')),
        // ];

        return [
            'id' => $this->id,
            'visitor_name' => $this->visitor_name,
            'visitor_phone' => $this->visitor_phone,
            'visitor_email' => $this->visitor_email,
            'status' => $this->status,
            'agent' => $this->when($this->agent, [
                'id' => $this->agent->id,
                'name' => $this->agent->user->name,
                'status' => $this->agent->status,
            ]),
            'created_at' => $this->created_at,
            'ended_at' => $this->ended_at,
        ];
    }
}
