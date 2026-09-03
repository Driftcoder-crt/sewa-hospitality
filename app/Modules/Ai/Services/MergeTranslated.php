<?php

namespace App\Modules\Ai\Services;

/**
 * Structural merge of model output onto the ORIGINAL payload (defense
 * in depth for the translate pipeline): only leaf string values that
 * came back non-empty replace their originals — keys, structure, URLs,
 * numbers and booleans always survive whatever the model returned.
 */
final class MergeTranslated
{
    public static function merge(mixed $original, mixed $translated): mixed
    {
        if (is_array($original) && is_array($translated)) {
            $out = [];

            foreach ($original as $key => $value) {
                $out[$key] = array_key_exists($key, $translated)
                    ? self::merge($value, $translated[$key])
                    : $value;
            }

            return $out;
        }

        if (is_string($original) && is_string($translated) && trim($translated) !== '') {
            return $translated;
        }

        return $original;
    }
}
