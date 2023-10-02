<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait PackTrait {
    public function saveImage($image, $uuid) {
        $baseDir = 'public/packs/';
        // save the image in storage/packs/{uuid}.{extension}
        if (!$image->storeAs($baseDir, $uuid . '.jpg')) {
            return false;
        } else return '/storage/packs/' . $uuid . '.jpg';
    }
}
