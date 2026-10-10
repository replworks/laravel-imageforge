<?php

namespace Replworks\ImageForge;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

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
        if ($this->isEmptyPath()) {
            return null;
        }

        $sourceUrl = $this->storageSourceUrl();

        return $this->w === null ? $sourceUrl : $this->forgeUrl($sourceUrl);
    }

    public function asset(): ?string
    {
        if ($this->isEmptyPath()) {
            return null;
        }

        return asset($this->path);
    }

    private function isEmptyPath(): bool
    {
        return $this->path === null || $this->path === '';
    }

    private function storageSourceUrl(): string
    {
        $disk = config('imageforge.disk');

        return $disk === null
            ? Storage::disk()->url($this->path)
            : Storage::disk($disk)->url($this->path);
    }

    private function forgeUrl(string $sourceUrl): string
    {
        $service = config('imageforge.service');

        if ($service === null || $service === '') {
            throw new InvalidArgumentException('The imageforge.service setting must be configured.');
        }

        $baseUrl = config('imageforge.base_url');
        $originPath = parse_url($sourceUrl, PHP_URL_PATH);
        $options = [
            'w' => $this->w,
            'h' => $this->h,
            'fit' => $this->fit,
            'q' => $this->q,
            'f' => $this->f,
        ];
        $query = [];

        foreach ($options as $name => $value) {
            if ($value !== null) {
                $query[] = $name.'='.$value;
            }
        }

        return $baseUrl.'/'.$service.$originPath.'?'.implode('&', $query);
    }
}
