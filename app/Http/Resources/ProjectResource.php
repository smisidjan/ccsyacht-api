<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProjectResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $data = [
            '@context' => $this->schemaContext(),
            '@type' => 'CreativeWork',
            'identifier' => $this->id,
            'name' => $this->name,
            'additionalType' => $this->project_type,
            'status' => $this->status,
            'dateCreated' => $this->formatDate($this->created_at),
            'dateModified' => $this->formatDate($this->updated_at),
        ];

        if ($this->description) {
            $data['description'] = $this->description;
        }

        if ($this->start_date) {
            $data['startDate'] = $this->start_date->format('Y-m-d');
        }

        if ($this->end_date) {
            $data['endDate'] = $this->end_date->format('Y-m-d');
        }

        if ($this->general_arrangement_path) {
            $data['generalArrangement'] = $this->general_arrangement_path;
        }

        if ($this->relationLoaded('shipyard') && $this->shipyard) {
            $data['producer'] = [
                '@type' => 'Organization',
                'identifier' => $this->shipyard->id,
                'name' => $this->shipyard->name,
            ];

            if ($this->shipyard->contact_name || $this->shipyard->contact_email || $this->shipyard->contact_phone) {
                $contactPoint = ['@type' => 'ContactPoint'];

                if ($this->shipyard->contact_name) {
                    $contactPoint['name'] = $this->shipyard->contact_name;
                }
                if ($this->shipyard->contact_email) {
                    $contactPoint['email'] = $this->shipyard->contact_email;
                }
                if ($this->shipyard->contact_phone) {
                    $contactPoint['telephone'] = $this->shipyard->contact_phone;
                }

                $data['producer']['contactPoint'] = $contactPoint;
            }
        }

        if ($this->relationLoaded('creator') && $this->creator) {
            $data['author'] = $this->formatPerson($this->creator);
        }

        return $data;
    }
}
