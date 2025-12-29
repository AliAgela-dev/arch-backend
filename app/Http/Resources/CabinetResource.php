<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CabinetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'Room'    => $this->room->name,
            'name'       => $this->name,
            'position_x' => $this->position_x,
            'position_y' => $this->position_y,
            'status'     => $this->status,

            'drawers'    => DrawerResource::collection(
                                $this->whenLoaded('drawers')
                            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
