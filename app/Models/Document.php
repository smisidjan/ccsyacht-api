<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Document model representing a DigitalDocument.
 *
 * @see https://schema.org/DigitalDocument
 */
class Document extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'document_type_id',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'uploaded_by',
        'uploaded_by_name',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * The document type this document belongs to.
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * The user who uploaded this document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Get human-readable file size.
     */
    public function getFileSizeForHumans(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    // =========================================================================
    // Schema.org Output
    // =========================================================================

    /**
     * Convert to Schema.org DigitalDocument JSON-LD format.
     *
     * @see https://schema.org/DigitalDocument
     */
    public function toSchemaOrg(): array
    {
        $data = [
            '@type' => 'DigitalDocument',
            'identifier' => $this->id,
            'name' => $this->title,
            'encodingFormat' => $this->mime_type,
            'contentSize' => $this->getFileSizeForHumans(),
            'dateCreated' => $this->created_at?->format('c'),
            'dateModified' => $this->updated_at?->format('c'),
        ];

        if ($this->description) {
            $data['description'] = $this->description;
        }

        if ($this->uploader) {
            $data['author'] = [
                '@type' => 'Person',
                'identifier' => $this->uploader->id,
                'name' => $this->uploader->name,
            ];
        } elseif ($this->uploaded_by_name) {
            $data['author'] = [
                '@type' => 'Organization',
                'name' => $this->uploaded_by_name,
            ];
        }

        return $data;
    }
}
