<?php

namespace KennedyOsaze\LaravelApiResponse\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait Translatable
{
    /**
     * Transform a string to parameters used for translation.
     *
     * The string is of the format 'name:key1=value1|key2=value2'
     *
     * Such that it can be used as: __('name', ['key1' => value1, key2 => value2])
     * to retrieve corresponding translation field
     *
     * @param string $string
     *
     * @return array
     */
    public function parseStringToTranslationParameters(string $string): array
    {
        $stringParts = explode(':', Str::after($string, '::'));

        $prefix = Str::contains($string, '::') ? Str::before($string, '::').'::' : '';
        $name = $prefix.array_shift($stringParts);

        $attributes = [];

        if (! empty($stringParts)) {
            foreach (explode('|', $stringParts[0]) as $keyValue) {
                if ($keyValue === '') {
                    continue;
                }

                $parts = explode('=', $keyValue);
                $attributes[$parts[0]] = $parts[1] ?? '';
            }
        }

        return compact('name', 'attributes');
    }

    /**
     * Transforms the parameters into a string parsable for translation.
     *
     * Used like this: $this->transformToTranslatableString('name', ['key1' => 'value1', 'key2' => 'value2'])
     *
     * This outputs as string of the form 'name:key1=value1|key2=value2'
     *
     * @param string $name
     * @param array<string, mixed> $attributes
     * @param string $string
     *
     * @return string
     */
    public function transformToTranslatableString(string $name, array $attributes = []): string
    {
        if (empty($attributes) || ! Arr::isAssoc($attributes)) {
            return $name;
        }

        return rtrim($name.':'.http_build_query(Arr::dot($attributes), '', '|'), '=:|');
    }

    /**
     * Attempts to translates a message string returning the corresponding key and translated string.
     *
     * If the message cannot be translated, the returning key is null and message remains the same.
     *
     * @param string $message
     * @param array<string, mixed> $attributes
     * @param array
     *
     * @return array
     */
    public function getTranslatedStringArray(string $message, array $attributes = [], ?string $prefix = null): array
    {
        $path = ! empty($prefix) ? "{$prefix}.{$message}" : $message;

        $key = null;
        $translatedMessage = __($path, $attributes);

        if (! Str::startsWith($translatedMessage, $path)) {
            $message = $translatedMessage;
            $key = Str::slug(last(explode('.', $path)), '_');
        }

        return ['key' => $key, 'message' => $message];
    }
}
