<?php

namespace App\Support;

use App\Models\ProductReview;
use App\Models\ProductReviewMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * What a customer may attach to a review, and what happens to it.
 *
 * Everything a stranger uploads passes through here: the rules that accept
 * it, the name it is stored under, and the record kept of it. One place,
 * because a second path that skipped one of those checks is exactly how a
 * file nobody vetted ends up being served to other customers.
 */
class ReviewMedia
{
    /**
     * Validation rules for the files on a review form.
     *
     * Photographs and videos arrive in one field and are told apart by what
     * they contain, so the rules have to accept either and then hold each to
     * its own ceiling. Laravel cannot express "4 MB if it is a photograph,
     * 20 MB if it is a clip" in one rule, so the shared rule admits both
     * types and the per-type size is checked in sizeProblem() below.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $media = config('reviews.media');

        $types = array_merge($media['photo']['mimes'], $media['video']['mimes']);

        return [
            'media'   => ['nullable', 'array', 'max:' . $media['max_files']],
            'media.*' => [
                'file',
                // Read from the file's own contents, not its name: a script
                // renamed to .jpg does not get past this.
                'mimes:' . implode(',', $types),
                // The outer ceiling. The per-type one is stricter and is
                // applied after, so a 20 MB "photograph" is still refused.
                'max:' . max($media['photo']['max_kilobytes'], $media['video']['max_kilobytes']),
            ],
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        $media = config('reviews.media');

        return [
            'media.max'     => 'You can attach up to ' . $media['max_files'] . ' files to a review.',
            'media.*.mimes' => 'Attach photographs (' . implode(', ', $media['photo']['mimes'])
                . ') or a short video (' . implode(', ', $media['video']['mimes']) . ').',
            'media.*.max'   => 'That file is too large.',
            'media.*.file'  => 'That did not arrive as a file.',
        ];
    }

    /** photo, video, or null when it is neither. */
    public static function kindOf(UploadedFile $file): ?string
    {
        $extension = strtolower((string) $file->guessExtension() ?: $file->getClientOriginalExtension());
        $media = config('reviews.media');

        if (in_array($extension, $media['photo']['mimes'], true)) {
            return ProductReviewMedia::KIND_PHOTO;
        }

        if (in_array($extension, $media['video']['mimes'], true)) {
            return ProductReviewMedia::KIND_VIDEO;
        }

        return null;
    }

    /**
     * The per-type ceiling, which the shared rule above cannot express.
     *
     * @return string|null the message to show, or null when the file is fine
     */
    public static function sizeProblem(UploadedFile $file): ?string
    {
        $kind = self::kindOf($file);

        if ($kind === null) {
            return 'That kind of file cannot be attached to a review.';
        }

        $limit = (int) config("reviews.media.{$kind}.max_kilobytes");

        if ($file->getSize() / 1024 > $limit) {
            return ucfirst($kind) . 's are limited to ' . self::readable($limit)
                . '. That one is ' . self::readable((int) round($file->getSize() / 1024)) . '.';
        }

        return null;
    }

    /**
     * Stores one file against a review.
     *
     * The name is generated, never taken from the upload: a name a stranger
     * chose is a name that can carry directory separators, a second
     * extension, or three hundred characters.
     */
    public static function attach(ProductReview $review, UploadedFile $file): ProductReviewMedia
    {
        $kind = self::kindOf($file);

        $path = $file->store('reviews', 'public');

        return ProductReviewMedia::create([
            'review_id'  => $review->review_id,
            'kind'       => $kind,
            'path'       => $path,
            // What the server made of the file, not what the browser said.
            'mime_type'  => $file->getClientMimeType(),
            'size_bytes' => (int) $file->getSize(),
        ]);
    }

    /** "4 MB", "512 KB" -- for a message somebody has to act on. */
    public static function readable(int $kilobytes): string
    {
        return $kilobytes >= 1024
            ? rtrim(rtrim(number_format($kilobytes / 1024, 1), '0'), '.') . ' MB'
            : $kilobytes . ' KB';
    }
}
