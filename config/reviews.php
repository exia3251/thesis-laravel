<?php

return [

    /*
    |--------------------------------------------------------------------------
    | What a customer may attach to a review
    |--------------------------------------------------------------------------
    |
    | A photograph of the bottle that arrived, or a short clip of the engine
    | running, says more than a paragraph. It is also a file a stranger
    | uploads to this shop's server and that other customers then open, so
    | what is accepted is stated here rather than spread through the code.
    |
    | Two separate ceilings. A photograph has no business being eight
    | megabytes and a phone video cannot help being larger than a photograph,
    | so one number for both would have to be the video's -- and would then
    | let somebody upload a twenty-megabyte "photograph".
    |
    | The types are checked by what the file contains, not by what it is
    | called: Laravel's `mimes` rule reads the file's own type, so renaming
    | a script to .jpg does not get it past.
    |
    | PHP itself caps an upload before any of this is consulted. Keep
    | max_kilobytes under upload_max_filesize and post_max_size in php.ini,
    | or the file is refused by the server with an empty request and a
    | message nobody can act on.
    */

    'media' => [

        // How many files one review may carry, photographs and videos
        // together. Enough to show a problem from two angles; not a gallery.
        'max_files' => 3,

        'photo' => [
            'max_kilobytes' => 4096,                              // 4 MB
            'mimes'         => ['jpg', 'jpeg', 'png', 'webp'],
            // Big enough to see what is being complained about. A 40-pixel
            // thumbnail of a bottle proves nothing.
            'min_width'     => 200,
            'min_height'    => 200,
        ],

        'video' => [
            'max_kilobytes' => 20480,                             // 20 MB
            // mp4 and webm play in every browser this shop is used on.
            // mov is what an iPhone produces, so refusing it would refuse
            // the most common phone in the country.
            'mimes'         => ['mp4', 'webm', 'mov'],
            'max_seconds'   => 60,
        ],
    ],

];
