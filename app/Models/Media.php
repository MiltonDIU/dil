<?php

namespace App\Models;

use App\Models\Concerns\HasUploadUrls;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasUploadUrls;

    protected $table = 'media';

    protected $guarded = [];

    protected function url(): Attribute
    {
        return Attribute::get(fn () => $this->uploadUrl($this->path));
    }

    protected function humanSize(): Attribute
    {
        return Attribute::get(function () {
            $bytes = (int) $this->size;
            $units = ['B', 'KB', 'MB', 'GB'];
            $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
            $i = min($i, count($units) - 1);

            return round($bytes / (1024 ** $i), $i === 0 ? 0 : 1).' '.$units[$i];
        });
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    public function exists(): bool
    {
        return ! $this->uploadMissing($this->path);
    }
}
