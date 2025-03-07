<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ChatMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'attachment_path' => $this->when($this->attachment_path, function () {
                return Storage::url($this->attachment_path);
            }),
            'attachment_type' => $this->attachment_type,
            'sender_type' => $this->sender_type,
            'sender' => $this->when($this->sender_type === 'App\Models\ChatAgent', function () {
                return [
                    'name' => $this->sender->user->name,
                ];
            }),
            'created_at' => $this->created_at->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
        ];
    }
}
