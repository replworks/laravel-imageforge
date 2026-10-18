<?php

namespace Replworks\ImageForge\Blade;

use Replworks\ImageForge\Dsl;

class UtilityClassPrecompiler
{
    public static function compile(string $template): string
    {
        $output = '';
        $offset = 0;
        $length = strlen($template);

        while ($offset < $length) {
            $protectedEnd = self::protectedEnd($template, $offset);
            if ($protectedEnd !== null) {
                $output .= substr($template, $offset, $protectedEnd - $offset);
                $offset = $protectedEnd;

                continue;
            }

            if (self::startsImgTag($template, $offset)) {
                $end = self::tagEnd($template, $offset);
                if ($end !== null) {
                    $tag = substr($template, $offset, $end - $offset);
                    $output .= self::compileImg($tag);
                    $offset = $end;

                    continue;
                }
            }

            $output .= $template[$offset];
            $offset++;
        }

        return $output;
    }

    private static function compileImg(string $tag): string
    {
        $attributes = self::attributes($tag);
        $class = self::attribute($attributes, 'class');
        $src = self::attribute($attributes, 'src');

        if ($class === null || $src === null || ! self::isStaticAttribute($class)) {
            return $tag;
        }

        $classes = preg_split('/\s+/', trim($class['value']), -1, PREG_SPLIT_NO_EMPTY);
        $parsed = Dsl::parse($classes ?: [], (string) config('imageforge.dsl_prefix', 'if-'));

        if ($parsed['consumed'] === [] || ($srcExpression = self::srcExpression($src['value'])) === null) {
            return $tag;
        }

        $remaining = array_values(array_diff($classes, $parsed['consumed']));
        $replacements = [];

        if ($remaining === []) {
            $classStart = $class['start'];
            if ($classStart > 4 && ctype_space($tag[$classStart - 1])) {
                $classStart--;
            }
            $replacements[] = [$classStart, $class['end'], ''];
        } else {
            $replacement = 'class='.$class['quote'].implode(' ', $remaining).$class['quote'];
            $replacements[] = [$class['start'], $class['end'], $replacement];
        }

        $options = var_export($parsed['options'], true);
        $srcReplacement = 'src="{{ \\Replworks\\ImageForge\\Dsl::url('
            .$srcExpression.', '.$options.') }}"';
        $replacements[] = [$src['start'], $src['end'], $srcReplacement];

        usort($replacements, static fn (array $left, array $right): int => $right[0] <=> $left[0]);
        foreach ($replacements as [$start, $end, $replacement]) {
            $tag = substr_replace($tag, $replacement, $start, $end - $start);
        }

        return $tag;
    }

    private static function srcExpression(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return 'null';
        }

        if (preg_match('/^\{\{\s*(.*?)\s*\}\}$/s', $value, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/^\{!!\s*(.*?)\s*!!\}$/s', $value, $matches)) {
            return trim($matches[1]);
        }

        $parts = [];
        $cursor = 0;
        while (true) {
            $double = strpos($value, '{{', $cursor);
            $raw = strpos($value, '{!!', $cursor);
            if ($double === false && $raw === false) {
                break;
            }

            if ($double === false || ($raw !== false && $raw < $double)) {
                $openPosition = $raw;
                $open = '{!!';
                $close = '!!}';
            } else {
                $openPosition = $double;
                $open = '{{';
                $close = '}}';
            }

            $text = substr($value, $cursor, $openPosition - $cursor);
            if ($text !== '') {
                $parts[] = var_export($text, true);
            }

            $expressionStart = $openPosition + strlen($open);
            $expressionEnd = strpos($value, $close, $expressionStart);
            if ($expressionEnd === false) {
                return null;
            }

            $parts[] = '('.trim(substr($value, $expressionStart, $expressionEnd - $expressionStart)).')';
            $cursor = $expressionEnd + strlen($close);
        }

        if ($cursor < strlen($value)) {
            $parts[] = var_export(substr($value, $cursor), true);
        }

        return $parts === [] ? null : implode(' . ', $parts);
    }

    private static function attributes(string $tag): array
    {
        $attributes = [];
        $length = strlen($tag);
        $index = 4;

        while ($index < $length - 1) {
            while ($index < $length && (ctype_space($tag[$index]) || $tag[$index] === '/')) {
                $index++;
            }
            if ($index >= $length - 1 || $tag[$index] === '>') {
                break;
            }

            $start = $index;
            while ($index < $length && preg_match('/[A-Za-z0-9_:-]/', $tag[$index])) {
                $index++;
            }
            if ($start === $index) {
                $index++;

                continue;
            }

            $name = strtolower(substr($tag, $start, $index - $start));
            while ($index < $length && ctype_space($tag[$index])) {
                $index++;
            }

            $value = null;
            $quote = '"';
            if ($index < $length && $tag[$index] === '=') {
                $index++;
                while ($index < $length && ctype_space($tag[$index])) {
                    $index++;
                }
                if ($index < $length && in_array($tag[$index], ['"', "'"], true)) {
                    $quote = $tag[$index++];
                    $valueStart = $index;
                    while ($index < $length && $tag[$index] !== $quote) {
                        $index++;
                    }
                    $value = substr($tag, $valueStart, $index - $valueStart);
                    $index = min($length, $index + 1);
                } else {
                    $valueStart = $index;
                    if (substr($tag, $index, 2) === '{{') {
                        $valueEnd = strpos($tag, '}}', $index + 2);
                        $index = $valueEnd === false ? $length : $valueEnd + 2;
                    } elseif (substr($tag, $index, 3) === '{!!') {
                        $valueEnd = strpos($tag, '!!}', $index + 3);
                        $index = $valueEnd === false ? $length : $valueEnd + 3;
                    } else {
                        while ($index < $length && ! ctype_space($tag[$index]) && $tag[$index] !== '>') {
                            $index++;
                        }
                    }
                    $value = substr($tag, $valueStart, $index - $valueStart);
                    $quote = '';
                }
            }

            $attributes[] = compact('name', 'value', 'quote', 'start') + ['end' => $index];
        }

        return $attributes;
    }

    private static function attribute(array $attributes, string $name): ?array
    {
        foreach ($attributes as $attribute) {
            if ($attribute['name'] === $name) {
                return $attribute;
            }
        }

        return null;
    }

    private static function isStaticAttribute(array $attribute): bool
    {
        return $attribute['value'] !== null && $attribute['quote'] !== '';
    }

    private static function startsImgTag(string $template, int $offset): bool
    {
        if (strtolower(substr($template, $offset, 4)) !== '<img') {
            return false;
        }

        $next = $template[$offset + 4] ?? '';

        return $next === '' || ctype_space($next) || $next === '/' || $next === '>';
    }

    private static function tagEnd(string $template, int $offset): ?int
    {
        $length = strlen($template);
        $quote = null;
        $parentheses = 0;
        $bladeEcho = 0;

        for ($index = $offset + 4; $index < $length; $index++) {
            $two = substr($template, $index, 2);
            if ($quote !== null) {
                if ($template[$index] === $quote) {
                    $quote = null;
                }

                continue;
            }
            if ($two === '{{' || $two === '{!') {
                $bladeEcho++;

                continue;
            }
            if ($bladeEcho > 0 && ($two === '}}' || substr($template, $index, 3) === '!!}')) {
                $bladeEcho--;
                $index += $two === '}}' ? 1 : 2;

                continue;
            }
            if ($template[$index] === '"' || $template[$index] === "'") {
                $quote = $template[$index];
            } elseif ($template[$index] === '(') {
                $parentheses++;
            } elseif ($template[$index] === ')' && $parentheses > 0) {
                $parentheses--;
            } elseif ($template[$index] === '>' && $parentheses === 0 && $bladeEcho === 0) {
                return $index + 1;
            }
        }

        return null;
    }

    private static function protectedEnd(string $template, int $offset): ?int
    {
        if (substr($template, $offset, 5) === '@php(') {
            $quote = null;
            $parentheses = 1;
            $length = strlen($template);

            for ($index = $offset + 5; $index < $length; $index++) {
                if ($quote !== null) {
                    if ($template[$index] === $quote) {
                        $quote = null;
                    }

                    continue;
                }
                if ($template[$index] === '"' || $template[$index] === "'") {
                    $quote = $template[$index];
                } elseif ($template[$index] === '(') {
                    $parentheses++;
                } elseif ($template[$index] === ')' && --$parentheses === 0) {
                    return $index + 1;
                }
            }

            return $length;
        }

        foreach (['{{--' => '--}}', '@verbatim' => '@endverbatim', '@php' => '@endphp'] as $open => $close) {
            if (substr($template, $offset, strlen($open)) === $open) {
                $end = stripos($template, $close, $offset + strlen($open));

                return $end === false ? strlen($template) : $end + strlen($close);
            }
        }

        foreach (['<script' => '</script>', '<style' => '</style>'] as $open => $close) {
            if (strtolower(substr($template, $offset, strlen($open))) === $open) {
                $end = stripos($template, $close, $offset + strlen($open));

                return $end === false ? strlen($template) : $end + strlen($close);
            }
        }

        return null;
    }
}
