<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * One photograph or clip belonging to a review.
 */
class ProductReviewMedia extends Model
{
    protected $table = 'product_review_media';

    protected $primaryKey = 'media_id';

    protected $fillable = ['review_id', 'kind', 'path', 'mime_type', 'size_bytes'];

    protected $casts = ['size_bytes' => 'integer'];

    public const KIND_PHOTO = 'photo';
    public const KIND_VIDEO = 'video';

    public function review()
    {
        return $this->belongsTo(ProductReview::class, 'review_id', 'review_id');
    }

    /**
     * Root-relative, never absolute.
     *
     * An absolute address composed here carries whichever host built it,
     * which is wrong for every other host: the shop is reached on localhost
     * while it is being worked on and through a tunnel while it is being
     * demonstrated, and a stored absolute address silently breaks one of
     * them. The same mistake once cost the GCash code on the receipt page.
     */
    public function url(): string
    {
        return '/storage/' . ltrim($this->path, '/');
    }

    public function isVideo(): bool
    {
        return $this->kind === self::KIND_VIDEO;
    }

    /** Removes the file as well as the row. */
    public function deleteWithFile(): void
    {
        Storage::disk('public')->delete($this->path);

        $this->delete();
    }
}
