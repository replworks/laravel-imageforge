<?php

namespace Replworks\ImageForge\Concerns;

use InvalidArgumentException;
use Replworks\ImageForge\ImageForge;

trait HasImageForge
{
    public function __call($method, $parameters)
    {
        $suffix = 'ImageForge';

        if (! str_ends_with($method, $suffix)) {
            return parent::__call($method, $parameters);
        }

        $prefix = substr($method, 0, -strlen($suffix));

        if ($prefix === '') {
            return parent::__call($method, $parameters);
        }

        foreach ($this->imageForgeFields ?? [] as $field) {
            if ($prefix !== $this->camelCaseFieldName($field)) {
                continue;
            }

            $value = $this->getAttributes()[$field] ?? null;

            if ($value !== null && ! is_string($value)) {
                throw new InvalidArgumentException("The image field [{$field}] must contain a string or null.");
            }

            return ImageForge::make($value, ...$this->imageForgeOptions($parameters));
        }

        return parent::__call($method, $parameters);
    }

    private function camelCaseFieldName(string $field): string
    {
        $segments = explode('_', $field);
        $first = array_shift($segments);

        return $first.implode('', array_map(ucfirst(...), $segments));
    }

    private function imageForgeOptions(array $parameters): array
    {
        $options = [null, null, null, null, null];
        $names = ['w' => 0, 'h' => 1, 'fit' => 2, 'q' => 3, 'f' => 4];

        foreach ($parameters as $key => $value) {
            $index = is_int($key) ? $key : ($names[$key] ?? null);

            if ($index !== null) {
                $options[$index] = $value;
            }
        }

        return $options;
    }
}
