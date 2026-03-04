<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ShipyardResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            '@context' => $this->schemaContext(),
            '@type' => 'Organization',
            'identifier' => $this->id,
            'name' => $this->name,
            'dateCreated' => $this->formatDate($this->created_at),
            'dateModified' => $this->formatDate($this->updated_at),
        ];

        if ($this->address) {
            $data['address'] = $this->address;
        }

        if ($this->contact_name || $this->contact_email || $this->contact_phone) {
            $contactPoint = ['@type' => 'ContactPoint'];

            if ($this->contact_name) {
                $contactPoint['name'] = $this->contact_name;
            }
            if ($this->contact_email) {
                $contactPoint['email'] = $this->contact_email;
            }
            if ($this->contact_phone) {
                $contactPoint['telephone'] = $this->contact_phone;
            }

            $data['contactPoint'] = $contactPoint;
        }

        return $data;
    }
}
