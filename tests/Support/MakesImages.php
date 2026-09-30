<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

/**
 * Real picture files for tests, built without the GD extension.
 *
 * Laravel's UploadedFile::fake()->image() calls imagecreatetruecolor, and GD
 * is not enabled on this installation -- which is the same reason the cropper
 * shrinks pictures in the browser rather than on the server. So the bytes are
 * written by hand: a PNG is a signature and three chunks, and the rules being
 * tested here read the header rather than decoding the picture.
 */
trait MakesImages
{
    protected function fakePng(string $name, int $width, int $height): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'png');

        file_put_contents($path, $this->pngBytes($width, $height));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    /** A solid grey PNG of the given size. */
    protected function pngBytes(int $width, int $height): string
    {
        $png = "\x89PNG\r\n\x1a\n";

        // Width, height, 8 bits per channel, colour type 2 (red, green, blue),
        // no compression, filter or interlace variations.
        $png .= $this->pngChunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0));

        // Each row carries a leading filter byte, then three bytes a pixel.
        $row = "\x00" . str_repeat("\x80\x80\x80", $width);
        $png .= $this->pngChunk('IDAT', gzcompress(str_repeat($row, $height), 9));

        return $png . $this->pngChunk('IEND', '');
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
    }
}
