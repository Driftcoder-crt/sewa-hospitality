<?php

namespace App\Modules\Ai\Services;

/**
 * Prompt templates, versioned in code (08-ai-system/01 §3: "prompts
 * reviewed like code — never inline strings scattered in business
 * logic"). Each feature gets its system contract + a user-message
 * builder; terminology glossary per 06-content-seo/04 doc §3.
 */
final class PromptLibrary
{
    /** Terms translated ONCE, then standardized everywhere (launch set). */
    private const GLOSSARY = [
        'en' => ['FRRO' => 'FRRO', 'PAN' => 'PAN', 'OCI' => 'OCI', 'Sewa Verified' => 'Sewa Verified'],
        'hi' => ['FRRO' => 'FRRO', 'PAN' => 'PAN', 'OCI' => 'OCI', 'Sewa Verified' => 'Sewa Verified'],
        'ja' => ['FRRO' => 'FRRO（外国人登録事務所）', 'PAN' => 'PAN（納税者番号）', 'OCI' => 'OCI（海外市民権）', 'Sewa Verified' => 'Sewa Verified'],
        'ko' => ['FRRO' => 'FRRO(외국인등록사무소)', 'PAN' => 'PAN(납세자번호)', 'OCI' => 'OCI(해외시민권)', 'Sewa Verified' => 'Sewa Verified'],
        'tr' => ['FRRO' => 'FRRO', 'PAN' => 'PAN', 'OCI' => 'OCI', 'Sewa Verified' => 'Sewa Verified'],
        'ar' => ['FRRO' => 'FRRO', 'PAN' => 'PAN', 'OCI' => 'OCI', 'Sewa Verified' => 'Sewa Verified'],
    ];

    public static function system(string $feature, string $locale = 'en'): string
    {
        // The politeness register is a translation-desk rule — injecting
        // it into enrich/score/draft prompts would just add noise.
        $register = $feature === 'translate' ? match ($locale) {
            'ja' => 'Use formal polite Japanese (です/ます register).',
            'ko' => 'Use formal polite Korean (합쇼체 register).',
            'ar' => 'Use formal Modern Standard Arabic.',
            'tr' => 'Use natural, professional Turkish.',
            default => 'Use clear professional English.',
        } : '';

        return match ($feature) {
            'translate' => 'You are the translation desk of Sewa Hospitality, a corporate relocation company in India. '
                .'Translate the JSON content faithfully into the target locale. Preserve the JSON structure exactly — '
                .'translate string VALUES only, never keys, never URLs, never {{placeholders}}. '.$register.' '
                .'Standardize terminology with this glossary: '.json_encode(self::glossary($locale), JSON_UNESCAPED_UNICODE).'. '
                .'Return ONLY the translated JSON — no commentary.',
            'enrich' => 'You are the CRM pre-read assistant of Sewa Hospitality. From ANONYMOUS lead metadata only, '
                .'suggest: segment (corporate|diplomat|family|expat-individual|student), message language (ISO 639-1), '
                .'a one-line summary, and a priority hint (low|medium|high). Never invent PII. Reply ONLY with JSON '
               .'{"segment":"...","language":"...","summary":"...","priority_hint":"..."}.',
            'summarize' => 'You summarize internal operations threads for Sewa Hospitality managers. Names are already '
                .'masked — keep them masked. Produce a 3-bullet TL;DR. Return plain text only.',
            'draft' => 'You assist Sewa Hospitality authors with briefs and outline suggestions. Output is a suggestion '
                .'for a named human author — never a finished publishable piece. Return plain text.',
            'score' => 'You are an advisory lead-quality assistant for Sewa Hospitality consultants. From anonymous '
                .'metadata, suggest one priority hint (low|medium|high) with a single short reason. Suggestions are '
                .'advisory only — humans decide. Reply ONLY with JSON {"priority_hint":"...","reason":"..."}.',
            default => 'You are a helpful assistant for Sewa Hospitality.',
        };
    }

    /** Full message array for translate: the system contract + the JSON payload. */
    public static function translateMessages(string $content, string $locale): array
    {
        return [
            ['role' => 'system', 'content' => self::system('translate', $locale)],
            ...self::translateUser($content, $locale),
        ];
    }

    /** Full message array for enrichment: the system contract + anonymous metadata. */
    public static function enrichMessages(array $anonymous): array
    {
        return [
            ['role' => 'system', 'content' => self::system('enrich')],
            ...self::enrichUser($anonymous),
        ];
    }

    /** Admin test-bench messages: the feature's own system contract + a raw prompt. */
    public static function testMessages(string $feature, string $prompt): array
    {
        return [
            ['role' => 'system', 'content' => self::system($feature)],
            ['role' => 'user', 'content' => $prompt],
        ];
    }

    /** User message for the translate feature: $content is the JSON payload. */
    public static function translateUser(string $content, string $locale): array
    {
        return [
            ['role' => 'user', 'content' => 'Target locale: '.$locale."\n\nContent JSON:\n".$content],
        ];
    }

    /** User message for enrichment: allowlisted anonymous fields only. */
    public static function enrichUser(array $anonymous): array
    {
        return [
            ['role' => 'user', 'content' => json_encode($anonymous, JSON_UNESCAPED_UNICODE)],
        ];
    }

    /** @return array<string, string> */
    public static function glossary(string $locale): array
    {
        return self::GLOSSARY[$locale] ?? self::GLOSSARY['en'];
    }
}
