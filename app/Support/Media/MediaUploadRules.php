<?php

namespace App\Support\Media;

use Illuminate\Validation\Rule;

/**
 * Server-side upload validation for the media pipeline
 * (03-technical-specs/09-media-pipeline.md §2 + §4). Consumed by the admin
 * uploader (M1) and any future form flow that attaches media; the arrays
 * are plain Laravel rule sets so they compose with Validator::make().
 *
 * Alt-text discipline (§4):
 * - `alt_text` is REQUIRED at upload — the rule is
 *   `required_unless:is_decorative,true`, i.e. the only sanctioned way to
 *   ship an empty alt is the explicit "decorative" checkbox (which then
 *   stores alt_text='' + is_decorative=true — intentional, never
 *   accidental).
 * - The admin UI must ALSO block publishing entities whose media lacks
 *   usable alt text (Media::hasUsableAltText()); these rules cover the
 *   upload gate, not the publish gate.
 *
 * SVG policy (§2): svg is accepted ONLY from the `brand` namespace
 * (icons/logos). Callers pass the target namespace so the mime whitelist
 * is tightened dynamically via array_filter — every other namespace gets
 * jpg/jpeg/png/webp only. NOTE: SVG is never converted and is served as
 * uploaded; server-side SVG sanitisation is an M1 requirement before the
 * brand uploader ships.
 *
 * Limits mirror config/sewa.php media.max_image_bytes (8 MB → 8192 KB)
 * and media.max_resume_bytes (5 MB → 5120 KB) — Laravel validates file
 * sizes in kilobytes.
 */
final class MediaUploadRules
{
    /**
     * Rules for an image upload. The optional $namespace is the target
     * media namespace (config('sewa.media.namespaces')); when it is
     * 'brand', svg joins the allowed mimes, otherwise it is filtered out.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function imageRules(?string $namespace = null): array
    {
        $allowedMimes = array_filter(
            ['jpg', 'jpeg', 'png', 'webp', 'svg'],
            static fn (string $mime): bool => $mime !== 'svg' || $namespace === 'brand',
        );

        return [
            'file' => [
                'required',
                'file',
                'image',
                'mimes:'.implode(',', $allowedMimes),
                'max:8192', // images ≤ 8 MB (config/sewa.php media.max_image_bytes)
            ],

            // Required at upload unless the explicit decorative checkbox is set (§4).
            'alt_text' => [
                'required_unless:is_decorative,true',
                'string',
                'max:1000',
            ],

            'is_decorative' => ['nullable', 'boolean'],

            // Photography attribution when needed (§4).
            'credit' => ['nullable', 'string', 'max:300'],

            'namespace' => [
                'required',
                'string',
                Rule::in(config('sewa.media.namespaces')),
            ],

            // Focal point as "x,y" percentages ("32.5,40.0" = 32.5% from
            // the left, 40.0% from the top) — HasSewaMedia maps it to the
            // nearest crop position.
            'focal_point' => [
                'nullable',
                'string',
                'regex:/^(\d{1,3}(\.\d+)?),(\d{1,3}(\.\d+)?)$/',
            ],
        ];
    }

    /**
     * Rules for a job-application resume upload — careers ONLY, PDF or
     * DOC/DOCX, ≤ 5 MB (config/sewa.php media.max_resume_bytes). Resumes
     * are PII: they are stored privately and never through the public
     * media subdomain (09-media-pipeline §2, 05-security-reliability).
     *
     * @return array<string, array<int, string>>
     */
    public static function resumeRules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx',
                'max:5120', // resumes ≤ 5 MB (careers only)
            ],
        ];
    }
}
