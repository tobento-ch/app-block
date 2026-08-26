<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Block\Editable\Traits;

trait NormalizesFileSourceInput
{
    /**
     * Normalize a stored file entry into FileSource input format.
     *
     * Handles:
     * - translatable src (array of locales)
     * - non-translatable src (string)
     *
     * @param mixed $file
     * @param string $srcKey
     * @param string $storageKey
     * @return mixed
     */
    protected function normalizeFileSource(mixed $file, string $srcKey = 'src', string $storageKey = 'storage'): mixed
    {
        // If it's not an array, return it unchanged
        if (!is_array($file)) {
            return $file;
        }
        
        if (!isset($file[$srcKey], $file[$storageKey])) {
            return $file;
        }

        $src = $file[$srcKey];
        $storage = $file[$storageKey];

        // Case 1: translatable
        if (is_array($src)) {
            $newSrc = [];

            foreach ($src as $locale => $filename) {
                if (!is_string($filename) || $filename === '') {
                    continue;
                }

                $newSrc[$locale] = [
                    'storage' => $storage,
                    'path' => $filename,
                ];
            }

            $file[$srcKey] = $newSrc;
            return $file;
        }

        // Case 2: non-translatable
        if (is_string($src) && $src !== '') {
            $file[$srcKey] = [
                'storage' => $storage,
                'path' => $src,
            ];
        }

        return $file;
    }
}