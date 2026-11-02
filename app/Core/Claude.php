<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Settings;

/**
 * Claude (Anthropic Messages API) client. Plain cURL on purpose: the site ships without Composer
 * so it can be uploaded to any PHP host. The key and model live in Settings → Claude AI.
 */
final class Claude
{
    public const ENDPOINT = 'https://api.anthropic.com/v1/messages';
    public const VERSION = '2026-10-21';

    /** Models offered in the admin: id => label (first one is the default) */
    public const MODELS = [
        'claude-opus-5-5'   => 'Claude Opus 5.5 — best quality (default)',
        'claude-sonnet-5-5' => 'Claude Sonnet 5.5 — fast and capable',
        'claude-haiku-5-5'  => 'Claude Haiku 5.5 — cheapest',
    ];

    public static function apiKey(): string
    {
        return trim((string)Settings::get('anthropic_api_key', ''));
    }

    public static function configured(): bool
    {
        return self::apiKey() !== '' && filter_var(Settings::get('ai_enabled', true), FILTER_VALIDATE_BOOL);
    }

    public static function model(): string
    {
        $m = (string)Settings::get('ai_model', 'claude-opus-5-5');
        return isset(self::MODELS[$m]) ? $m : 'claude-opus-5-5';
    }

    /**
     * One request to the Messages API.
     * @param string $system system prompt
     * @param string $user user turn
     * @param array{max_tokens?:int,effort?:string,schema?:array,model?:string,timeout?:int} $o
     * @return array{text:string,json:mixed,model:string,usage:array,stop:string}
     * @throws \RuntimeException with a message safe to show in the admin
     */
    public static function message(string $system, string $user, array $o = []): array
    {
        $key = self::apiKey();
        if ($key === '') throw new \RuntimeException(t('ai.not_configured'));
        $model = isset(self::MODELS[$o['model'] ?? '']) ? $o['model'] : self::model();
        $effort = in_array($o['effort'] ?? '', ['low', 'medium', 'high', 'xhigh', 'max'], true) ? $o['effort'] : 'medium';
        $body = [
            'model' => $model,
            'max_tokens' => max(256, min(32000, (int)($o['max_tokens'] ?? 8000))),
            'system' => [['type' => 'text', 'text' => $system, 'cache_control' => ['type' => 'ephemeral']]],
            'messages' => [['role' => 'user', 'content' => $user]],
            'output_config' => ['effort' => $effort],
        ];
        if (!empty($o['schema'])) {
            $body['output_config']['format'] = ['type' => 'json_schema', 'schema' => $o['schema']];
        }
        $headers = ['Content-Type: application/json', 'x-api-key: ' . $key, 'anthropic-version: ' . self::VERSION];
        if (!str_starts_with($model, 'claude-haiku')) {
            // a safety-classifier decline is re-run on a fallback model inside the same call
            $body['fallbacks'] = 'default';
            $headers[] = 'anthropic-beta: server-side-fallback-2026-07-01';
        }

        $base = rtrim((string)env('ANTHROPIC_BASE_URL', ''), '/'); // optional: proxy or test double
        $ch = curl_init($base !== '' ? $base . '/v1/messages' : self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => max(30, min(600, (int)($o['timeout'] ?? 180))),
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $raw === '') {
            Log::error('Claude: connection failed: ' . $err);
            throw new \RuntimeException(t('ai.connection_failed') . ($err !== '' ? ' (' . $err . ')' : ''));
        }
        $data = json_decode((string)$raw, true);
        if (!is_array($data)) throw new \RuntimeException(t('ai.bad_response'));
        if ($status >= 400 || ($data['type'] ?? '') === 'error') {
            $type = (string)($data['error']['type'] ?? 'error');
            $msg = (string)($data['error']['message'] ?? 'HTTP ' . $status);
            Log::error('Claude: ' . $type . ': ' . $msg);
            throw new \RuntimeException(match ($type) {
                'authentication_error' => t('ai.bad_key'),
                'rate_limit_error' => t('ai.rate_limited'),
                'overloaded_error' => t('ai.overloaded'),
                default => $msg,
            });
        }
        $stop = (string)($data['stop_reason'] ?? '');
        if ($stop === 'refusal') throw new \RuntimeException(t('ai.refused'));
        $text = '';
        foreach ((array)($data['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'text') $text .= (string)$block['text'];
        }
        if ($stop === 'max_tokens') Log::error('Claude: output truncated at max_tokens');
        $json = null;
        if (!empty($o['schema'])) {
            $json = json_decode($text, true);
            if (!is_array($json)) throw new \RuntimeException(t('ai.bad_response'));
        }
        return ['text' => trim($text), 'json' => $json, 'model' => (string)($data['model'] ?? $model), 'usage' => (array)($data['usage'] ?? []), 'stop' => $stop];
    }

    /** Short round-trip used by the "Test connection" button: returns the model name that answered. */
    public static function ping(): string
    {
        $r = self::message('Reply with the single word OK.', 'Ping', ['max_tokens' => 256, 'effort' => 'low', 'timeout' => 60]);
        return $r['model'];
    }

    /** Standing instructions shared by every task: who the site is, its tone, bilingual rules. */
    public static function systemPrompt(): string
    {
        $site = setting_tr('site_name');
        $desc = setting_tr('default_meta_description');
        $voice = trim((string)Settings::get('ai_brand_voice', ''));
        $lines = [
            'You are the in-house content and SEO editor of "' . $site . '", a web design and digital marketing agency in Doha, Qatar.',
            'About the agency: ' . $desc,
            'The website is bilingual: Arabic (default, right-to-left, Modern Standard Arabic with a warm Gulf tone) and English. Keep each language natural, not a literal translation.',
            'House style: premium, confident, concrete, no hype, no clichés, short sentences, benefits before features. Mention Qatar, Doha or restaurants/companies only where it reads naturally.',
            'Never invent facts, prices, client names, numbers or awards that are not in the input. Keep every placeholder such as {{first_name}} or {{site_url}} exactly as written.',
            'Return only the requested output, with no preamble, no explanation and no markdown fences.',
        ];
        if ($voice !== '') $lines[] = 'Brand voice notes from the owner: ' . $voice;
        return implode("\n", $lines);
    }
}
