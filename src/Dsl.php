<?php

namespace Replworks\ImageForge;

class Dsl
{
    public static function parse(array $classes, string $prefix): array
    {
        $options = [
            'w' => null,
            'h' => null,
            'fit' => null,
            'q' => null,
            'f' => null,
        ];
        $consumed = [];

        foreach ($classes as $class) {
            $suffix = str_starts_with($class, $prefix)
                ? substr($class, strlen($prefix))
                : null;

            if ($suffix === null) {
                continue;
            }

            if (preg_match('/^w-(\d+)$/', $suffix, $matches)) {
                $options['w'] = min(2000, (int) $matches[1]);
                $consumed[] = $class;
            } elseif (preg_match('/^h-(\d+)$/', $suffix, $matches)) {
                $options['h'] = min(2000, (int) $matches[1]);
                $consumed[] = $class;
            } elseif (preg_match('/^q-(\d+)$/', $suffix, $matches)) {
                $options['q'] = max(1, min(100, (int) $matches[1]));
                $consumed[] = $class;
            } elseif ($suffix === 'fit') {
                $options['fit'] = 'fit';
                $consumed[] = $class;
            } elseif ($suffix === 'fill' || $suffix === 'crop') {
                $options['fit'] = 'fill';
                $consumed[] = $class;
            } elseif ($suffix === 'fit-auto') {
                $options['fit'] = 'auto';
                $consumed[] = $class;
            } elseif (in_array($suffix, ['avif', 'webp', 'jpeg', 'png'], true)) {
                $options['f'] = $suffix;
                $consumed[] = $class;
            } elseif ($suffix === 'auto') {
                $options['f'] = null;
                $consumed[] = $class;
            }
        }

        return compact('options', 'consumed');
    }

    public static function url(?string $src, array $options): ?string
    {
        if ($src === null || $src === '') {
            return null;
        }

        $builder = ImageForge::make(
            $src,
            w: $options['w'] ?? 0,
            h: $options['h'] ?? null,
            fit: $options['fit'] ?? null,
            q: $options['q'] ?? null,
            f: $options['f'] ?? null,
        );

        return $builder->asset();
    }
}
