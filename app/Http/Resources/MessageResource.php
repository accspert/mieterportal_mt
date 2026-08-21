<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
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
            'thread_id' => $this->thread_id,
            'from_user_id' => $this->from_user_id,
            'to_user_id' => $this->to_user_id,
            'subject' => $this->subject,
            'body' => $this->body,
            'read_at' => $this->read_at,
            'sender' => $this->whenLoaded('sender', fn () => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'email' => $this->sender->email,
            ]),
            'recipient' => $this->whenLoaded('recipient', fn () => [
                'id' => $this->recipient->id,
                'name' => $this->recipient->name,
                'email' => $this->recipient->email,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
