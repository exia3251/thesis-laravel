<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SiteSetting;
use App\Support\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Lets the business edit the parts of the storefront that are about itself.
 *
 * The telephone number, the address, the email, the opening hours and the
 * GCash QR code were fixed in configuration. Changing one meant editing a file
 * on the server, which is not something the people who run this shop should
 * have to ask for.
 *
 * Pictures arrive already cropped and shrunk by the browser, so what reaches
 * here is the finished article rather than a photograph from a telephone. They
 * are still checked: the browser is a convenience, never the guarantee.
 */
class SiteContentController extends Controller
{
    /** Where uploaded pictures live on the public disk. */
    private const FOLDER = 'site';

    public function index()
    {
        return view('admin.cms');
    }

    public function show()
    {
        $content = SiteContent::all();

        $fields = [];

        foreach (array_keys(SiteContent::TEXT_FIELDS) as $key) {
            $fields[$key] = [
                'value' => $content[$key],
                // What the storefront falls back to if this is left empty, so
                // the editor can say what will be shown rather than implying
                // the box is the only source.
                'fallback' => SiteContent::fallback($key),
                'customised' => SiteSetting::where('key', $key)->exists(),
            ];
        }

        $images = [];

        foreach (SiteContent::IMAGE_FIELDS as $key => $shape) {
            $images[$key] = $shape + [
                'url' => $content[$key . '_url'],
                // Whether the business chose this one. The GCash code falls
                // back to the file that ships with the system, which is shown
                // but is not theirs to remove.
                'uploaded' => $content[$key . '_uploaded'],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => ['fields' => $fields, 'images' => $images],
        ]);
    }

    public function update(Request $request)
    {
        $rules = [
            'name'     => ['nullable', 'string', 'max:120'],
            'tagline'  => ['nullable', 'string', 'max:300'],
            'address'  => ['nullable', 'string', 'max:255'],
            'phone'    => ['nullable', 'string', 'max:40'],
            'email'    => ['nullable', 'email', 'max:150'],
            'hours'    => ['nullable', 'string', 'max:120'],
            'facebook' => ['nullable', 'url', 'max:255'],
        ];

        $messages = [
            'email.email' => 'Enter a valid email address, or leave it empty.',
            'facebook.url' => 'Enter the full address of the page, starting with https://',
        ];

        $values = $request->validate($rules, $messages);

        foreach (array_keys(SiteContent::TEXT_FIELDS) as $key) {
            if ($request->has($key)) {
                // Stored even when empty. An empty box is a decision -- show
                // nothing here -- and is not the same as never having been
                // set, which falls back to the configured default.
                SiteContent::set($key, trim((string) ($values[$key] ?? '')));
            }
        }

        ActivityLog::logAction(
            auth()->id(),
            'site_content_updated',
            auth()->user()->full_name . ' updated the shop contact details'
        );

        return response()->json([
            'success' => true,
            'message' => 'Saved. The shop shows the new details straight away.',
            'data' => SiteContent::all(),
        ]);
    }

    public function uploadImage(Request $request, string $field)
    {
        $shape = SiteContent::IMAGE_FIELDS[$field] ?? null;

        if (! $shape) {
            return response()->json(['success' => false, 'message' => 'There is no such picture slot.'], 404);
        }

        /*
         * Checked here as well as in the browser. The cropper shrinks the
         * picture before it is sent, which is why the limit is small -- but a
         * request can be made without ever opening the page, so the rules hold
         * either way. Raster formats only: an SVG is a document, and one
         * served from our own address would run whatever script it carried.
         */
        $request->validate([
            'image' => [
                'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072',
                'dimensions:min_width=' . (int) ($shape['width'] / 4) . ',min_height=' . (int) ($shape['height'] / 4),
            ],
        ], [
            'image.required' => 'Choose a picture first.',
            'image.image' => 'That file is not a picture.',
            'image.mimes' => 'Use a JPG, PNG or WEBP.',
            'image.max' => 'That picture is larger than 3 MB.',
            'image.dimensions' => "That picture is too small for the {$shape['label']} slot.",
        ]);

        $previous = SiteContent::get($field . '_path');

        $path = $request->file('image')->store(self::FOLDER, 'public');

        SiteContent::set(SiteContent::imageKey($field), $path);

        // The old file is of no further use, and leaving it fills the disk one
        // upload at a time.
        if ($previous && $previous !== $path) {
            Storage::disk('public')->delete($previous);
        }

        ActivityLog::logAction(
            auth()->id(),
            'site_image_updated',
            auth()->user()->full_name . " updated the shop {$shape['label']}"
        );

        return response()->json([
            'success' => true,
            'message' => "{$shape['label']} updated.",
            'data' => ['url' => SiteContent::get($field . '_url')],
        ]);
    }

    public function removeImage(string $field)
    {
        $shape = SiteContent::IMAGE_FIELDS[$field] ?? null;

        if (! $shape) {
            return response()->json(['success' => false, 'message' => 'There is no such picture slot.'], 404);
        }

        $path = SiteContent::get($field . '_path');

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        SiteSetting::where('key', SiteContent::imageKey($field))->delete();
        SiteContent::forget();

        ActivityLog::logAction(
            auth()->id(),
            'site_image_removed',
            auth()->user()->full_name . " removed the shop {$shape['label']}"
        );

        return response()->json([
            'success' => true,
            'message' => "{$shape['label']} removed. The shop falls back to its built-in design.",
            'data' => ['url' => SiteContent::get($field . '_url')],
        ]);
    }
}
