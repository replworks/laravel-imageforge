<?php

namespace Replworks\ImageForge;

use Illuminate\Support\Facades\Storage;

class ImageForge
{
    public function __construct(
        private readonly ?string $path,
        private readonly ?int $w = null,
        private readonly ?int $h = null,
        private readonly ?string $fit = null,
        private readonly ?int $q = null,
        private readonly ?string $f = null,
    ) {}

    public static function make(
        ?string $path,
        ?int $w = null,
        ?int $h = null,
        ?string $fit = null,
        ?int $q = null,
        ?string $f = null,
    ): self {
        return new self($path, $w, $h, $fit, $q, $f);
    }

    public function storage(): ?string
    {
        if ($this->path === null || $this->path === '') {
            return null;
        }

        $disk = config('imageforge.disk');
        $sourceUrl = $disk === null
            ? Storage::disk()->url($this->path)
            : Storage::disk($disk)->url($this->path);

        return $sourceUrl;
    }

    public function asset(): ?string
    {
        if ($this->path === null || $this->path === '') {
            return null;
        }

        return asset($this->path);
    }
}
