<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Claude;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Models\Pages;
use App\Models\SeoTasks;
use App\Models\Services;
use App\Models\Settings;

/** Claude assistant: rewrite / write / translate content, generate meta tags, plan and apply SEO fixes. */
final class AiController extends AdminController
{
    private const TASKS = ['rewrite', 'improve_seo', 'shorten', 'expand', 'translate', 'write', 'fix_grammar', 'custom'];

    /** Settings → Claude AI → "Test connection" */
    public function test(Request $r): void
    {
        try {
            $model = Claude::ping();
            $this->log('ai_test', 'settings', 'ai', ['model' => $model]);
            $this->ok(t('ai.test_ok', ['model' => $model]), admin_url('settings/ai'));
        } catch (\Throwable $e) {
            $this->fail(t('ai.test_failed') . ' ' . $e->getMessage(), admin_url('settings/ai'));
        }
    }

    /**
     * POST /admin/ai/generate  (JSON)
     * { task, kind: text|richtext|html, lang: ar|en|both, text, other, brief, context: {title, type} }
     * → { ok, output: string | {ar, en} }
     */
    public function generate(Request $r): void
    {
        if (!Claude::configured()) Response::json(['ok' => false, 'error' => t('ai.not_configured')], 422);
        $task = in_array($r->str('task'), self::TASKS, true) ? $r->str('task') : 'rewrite';
        $kind = in_array($r->str('kind'), ['text', 'richtext', 'html'], true) ? $r->str('kind') : 'text';
        $lang = in_array($r->str('lang'), ['ar', 'en', 'both'], true) ? $r->str('lang') : 'ar';
        $text = mb_substr((string)$r->input('text', ''), 0, 60000);
        $other = mb_substr((string)$r->input('other', ''), 0, 60000);
        $brief = mb_substr(trim((string)$r->input('brief', '')), 0, 4000);
        $ctx = is_array($r->input('context')) ? $r->input('context') : [];
        $title = mb_substr(strip_tags((string)($ctx['title'] ?? '')), 0, 200);
        $type = mb_substr(preg_replace('/[^a-z_ ]/i', '', (string)($ctx['type'] ?? '')), 0, 40);
        if ($task !== 'write' && trim($text) === '' && trim($other) === '') Response::json(['ok' => false, 'error' => t('ai.nothing_to_work_on')], 422);
        if (($task === 'write' || $task === 'custom') && $brief === '') Response::json(['ok' => false, 'error' => t('ai.brief_required')], 422);

        $format = match ($kind) {
            'richtext' => 'Output format: clean HTML fragment using only <p>, <h2>, <h3>, <ul>, <ol>, <li>, <strong>, <em>, <a>, <blockquote>. No <html>, <body>, <style> or inline styles.',
            'html' => 'Output format: a complete, email-safe HTML document (tables, inline CSS, no external CSS or scripts). Keep the existing structure, images, links, colours and merge tags; change only the words unless asked otherwise.',
            default => 'Output format: plain text, no HTML, no markdown. Keep it a single field value (one line, or short paragraphs only if the input had them).',
        };
        $langName = fn(string $l) => $l === 'ar' ? 'Arabic' : 'English';
        $instruction = match ($task) {
            'rewrite' => 'Rewrite the text so it reads better: clearer, more persuasive, premium tone, same meaning, same length (±20%).',
            'improve_seo' => 'Rewrite for SEO without keyword stuffing: a clear topic in the first sentence, natural keywords a Qatar customer would search for, scannable structure, specific benefits. Keep facts unchanged.',
            'shorten' => 'Make it about half as long while keeping every important fact and the call to action.',
            'expand' => 'Expand it to roughly double the length with concrete, useful detail (what, for whom, how it works, what the customer gets). Do not invent facts, prices or numbers.',
            'fix_grammar' => 'Fix spelling, grammar, punctuation and typography only. Keep wording and meaning as they are.',
            'translate' => 'Translate into the other language as a native copywriter would write it for the same audience: adapt idioms, keep names, numbers, links and placeholders.',
            'write' => 'Write new content from the brief below. Match the purpose of the field (' . ($type ?: 'website text') . ').',
            default => 'Apply the instruction below to the text.',
        };
        $contextLine = $title !== '' ? "Context: this belongs to \"{$title}\" (" . ($type ?: 'website content') . ").\n" : '';
        $system = Claude::systemPrompt() . "\n\n" . $format;

        if ($lang === 'both') {
            $schema = ['type' => 'object', 'properties' => ['ar' => ['type' => 'string'], 'en' => ['type' => 'string']], 'required' => ['ar', 'en'], 'additionalProperties' => false];
            $user = $contextLine . $instruction . "\nProduce BOTH languages as JSON {\"ar\": ..., \"en\": ...}: Arabic first, then English written for English-speaking customers (not a literal translation)."
                . ($brief !== '' ? "\nInstruction / brief: " . $brief : '')
                . ($text !== '' ? "\n\nArabic input:\n" . $text : '')
                . ($other !== '' ? "\n\nEnglish input:\n" . $other : '');
            try { $res = Claude::message($system, $user, ['schema' => $schema, 'max_tokens' => 16000]); }
            catch (\Throwable $e) { Response::json(['ok' => false, 'error' => $e->getMessage()], 422); }
            $out = ['ar' => (string)($res['json']['ar'] ?? ''), 'en' => (string)($res['json']['en'] ?? '')];
        } else {
            $target = $task === 'translate' ? ($lang === 'ar' ? 'en' : 'ar') : $lang;
            $user = $contextLine . $instruction . "\nWrite in " . $langName($target) . '.'
                . ($brief !== '' ? "\nInstruction / brief: " . $brief : '')
                . ($text !== '' ? "\n\nInput (" . $langName($lang) . "):\n" . $text : '')
                . ($other !== '' && $text === '' ? "\n\nReference in the other language:\n" . $other : '');
            try { $res = Claude::message($system, $user, ['max_tokens' => 16000]); }
            catch (\Throwable $e) { Response::json(['ok' => false, 'error' => $e->getMessage()], 422); }
            $out = $res['text'];
        }
        $this->log('ai_generate', $type ?: 'content', $title, ['task' => $task, 'lang' => $lang, 'tokens' => (int)($res['usage']['output_tokens'] ?? 0)]);
        Response::json(['ok' => true, 'output' => $out, 'model' => $res['model']]);
    }

    /**
     * POST /admin/ai/meta (JSON): { title, body, kind, url } → { ok, meta: {meta_title:{ar,en}, meta_description:{ar,en}, meta_keywords:{ar,en}} }
     */
    public function meta(Request $r): void
    {
        if (!Claude::configured()) Response::json(['ok' => false, 'error' => t('ai.not_configured')], 422);
        $title = is_array($r->input('title')) ? $r->input('title') : ['ar' => (string)$r->input('title', ''), 'en' => ''];
        $body = is_array($r->input('body')) ? $r->input('body') : ['ar' => (string)$r->input('body', ''), 'en' => ''];
        $kind = mb_substr(preg_replace('/[^a-z_ ]/i', '', $r->str('kind')), 0, 40) ?: 'page';
        try {
            $meta = self::generateMeta($kind, $title, $body, $r->str('url'));
        } catch (\Throwable $e) {
            Response::json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
        $this->log('ai_meta', $kind, tr($title), []);
        Response::json(['ok' => true, 'meta' => $meta]);
    }

    /** POST /admin/ai/seo-plan: a prioritised fix plan built from the live health checks */
    public function seoPlan(Request $r): void
    {
        if (!Claude::configured()) Response::json(['ok' => false, 'error' => t('ai.not_configured')], 422);
        $health = SeoTasks::health();
        $facts = [];
        foreach (['missing_title', 'missing_description', 'missing_keywords', 'noindex', 'services_missing_description', 'projects_missing_cover'] as $k) {
            $items = (array)($health[$k] ?? []);
            if ($items) $facts[] = str_replace('_', ' ', $k) . ': ' . implode(', ', array_map('strval', array_slice($items, 0, 30)));
        }
        $facts[] = 'media files without alt text: ' . (int)($health['media_missing_alt'] ?? 0);
        $facts[] = 'open SEO tasks: ' . (int)($health['tasks_open'] ?? 0);
        $facts[] = 'published projects (portfolio): ' . count(\App\Models\Projects::published());
        $settingsFacts = [
            'Google Search Console verification: ' . (trim((string)setting('google_verification', '')) !== '' ? 'set' : 'missing'),
            'GA4 measurement id: ' . (trim((string)setting('ga4_id', '')) !== '' ? 'set' : 'missing'),
            'Default OG image: ' . (trim((string)setting('og_image', '')) !== '' ? 'set' : 'missing (fallback image used)'),
            'WhatsApp number: ' . (strlen(preg_replace('/\D/', '', (string)setting('whatsapp_number', ''))) >= 8 ? 'set' : 'placeholder'),
            'Instagram link: ' . (trim((string)setting('social_instagram', '')) !== '' ? 'set' : 'missing'),
            'Phone: ' . (trim((string)setting('phone', '')) !== '' ? 'set' : 'missing'),
            'Address: ' . (setting_tr('address') ?: 'missing'),
        ];
        $pages = array_map(fn($p) => '/' . $p['slug'] . ' (' . tr($p['title'], 'en') . ')', Pages::published());
        $services = array_map(fn($s) => '/services/' . $s['slug'] . ' (' . tr($s['title'], 'en') . ')', Services::published());
        $tasks = array_map(fn($t) => $t['title'] . ' [' . $t['status'] . ']', SeoTasks::all());
        $lang = lang();
        $system = Claude::systemPrompt() . "\n\nYou are reviewing the SEO of this site for its owner, who manages it from an admin panel (Pages, Services, Packages, Portfolio, Media library, Settings → SEO / Contact / Social, SEO tasks). "
            . 'Output format: an HTML fragment using <h3>, <p>, <ol>, <ul>, <li>, <strong> only. Write in ' . ($lang === 'ar' ? 'Arabic' : 'English') . '.';
        $user = "Site: " . base_url() . "\nPublished pages: " . implode(', ', $pages) . "\nServices: " . implode(', ', $services)
            . "\n\nHealth check findings:\n- " . implode("\n- ", $facts)
            . "\n\nSettings:\n- " . implode("\n- ", $settingsFacts)
            . "\n\nSEO task board:\n- " . (implode("\n- ", $tasks) ?: 'empty')
            . "\n\nWrite a prioritised action plan (max 8 items). For each item: what is wrong, why it matters for a Qatar web-design agency, and the exact steps in this admin panel (or outside it, e.g. Google Search Console, Google Business Profile) to fix it. Mark which items the \"Fill missing meta with Claude\" button can do automatically. Be specific to the findings; skip generic advice that does not apply.";
        try {
            $res = Claude::message($system, $user, ['effort' => 'high', 'max_tokens' => 12000]);
        } catch (\Throwable $e) {
            Response::json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $res['text']);
        $html = preg_replace('/<(?!\/?(h3|p|ol|ul|li|strong|em|br)\b)[^>]*>/i', '', (string)$html);
        $this->log('ai_seo_plan', 'seo', '', ['model' => $res['model']]);
        Response::json(['ok' => true, 'html' => $html, 'model' => $res['model']]);
    }

    /** POST /admin/ai/seo-fill: generate and save meta title / description / keywords where they are missing (pages + services) */
    public function seoFill(Request $r): void
    {
        if (!Claude::configured()) Response::json(['ok' => false, 'error' => t('ai.not_configured')], 422);
        set_time_limit(600);
        $done = []; $errors = [];
        $empty = fn($v) => trim(tr($v, 'ar')) === '' || trim(tr($v, 'en')) === '';
        foreach (Db::from('pages')->get() as $p) { // full rows: the model's list query omits blocks and meta
            if ($p['slug'] === '404' || !empty($p['noindex']) || ($p['status'] ?? 'published') !== 'published') continue;
            if (!$empty($p['meta_title']) && !$empty($p['meta_description']) && !$empty($p['meta_keywords'])) continue;
            try {
                $meta = self::generateMeta('page', (array)$p['title'], self::blocksText((array)$p['blocks']), '/' . $p['slug']);
                Pages::update($p['id'], self::mergeMeta($p, $meta));
                $done[] = '/' . $p['slug'];
            } catch (\Throwable $e) { $errors[] = '/' . $p['slug'] . ': ' . $e->getMessage(); }
            if (count($done) + count($errors) >= 25) break;
        }
        foreach (Db::from('services')->get() as $s) {
            if (($s['status'] ?? 'published') !== 'published') continue;
            if (!$empty($s['meta_title']) && !$empty($s['meta_description']) && !$empty($s['meta_keywords'])) continue;
            try {
                $meta = self::generateMeta('service', (array)$s['title'], ['ar' => strip_tags(tr($s['body'], 'ar')), 'en' => strip_tags(tr($s['body'], 'en'))], '/services/' . $s['slug']);
                Services::update($s['id'], self::mergeMeta($s, $meta));
                $done[] = '/services/' . $s['slug'];
            } catch (\Throwable $e) { $errors[] = '/services/' . $s['slug'] . ': ' . $e->getMessage(); }
            if (count($done) + count($errors) >= 40) break;
        }
        $this->log('ai_seo_fill', 'seo', '', ['done' => $done, 'errors' => $errors]);
        Response::json(['ok' => true, 'done' => $done, 'errors' => $errors]);
    }

    // ------------------------------------------------------------------ helpers

    /** @return array{meta_title:array,meta_description:array,meta_keywords:array} */
    private static function generateMeta(string $kind, array $title, array $body, string $url): array
    {
        $str = ['type' => 'string'];
        $pair = ['type' => 'object', 'properties' => ['ar' => $str, 'en' => $str], 'required' => ['ar', 'en'], 'additionalProperties' => false];
        $schema = ['type' => 'object', 'properties' => ['meta_title' => $pair, 'meta_description' => $pair, 'meta_keywords' => $pair], 'required' => ['meta_title', 'meta_description', 'meta_keywords'], 'additionalProperties' => false];
        $suffixAr = tr(Settings::get('meta_title_suffix'), 'ar'); $suffixEn = tr(Settings::get('meta_title_suffix'), 'en');
        $system = Claude::systemPrompt() . "\n\nYou write search-engine meta tags. Rules: meta_title 45-60 characters, the main keyword first, end with \" | أثر\" (Arabic) or \" | Athar\" (English) — do not repeat the site name otherwise; meta_description 130-155 characters, one concrete benefit and a soft call to action, no quotes; meta_keywords 4-7 comma-separated phrases people in Qatar actually search (mix Doha/Qatar with the service). Arabic and English are written independently for their own audiences.";
        $user = "Content type: {$kind}\nURL: {$url}\nTitle (ar): " . trim((string)($title['ar'] ?? '')) . "\nTitle (en): " . trim((string)($title['en'] ?? ''))
            . "\n\nPage text (ar):\n" . mb_substr(trim((string)($body['ar'] ?? '')), 0, 6000)
            . "\n\nPage text (en):\n" . mb_substr(trim((string)($body['en'] ?? '')), 0, 6000)
            . "\n\nSite title suffixes currently in use: ar \"{$suffixAr}\", en \"{$suffixEn}\".";
        $res = Claude::message($system, $user, ['schema' => $schema, 'max_tokens' => 2000, 'effort' => 'medium']);
        $j = $res['json'];
        $clean = fn($v, int $max) => mb_substr(trim(str_replace(["\r", "\n"], ' ', (string)$v)), 0, $max);
        return [
            'meta_title' => ['ar' => $clean($j['meta_title']['ar'] ?? '', 120), 'en' => $clean($j['meta_title']['en'] ?? '', 120)],
            'meta_description' => ['ar' => $clean($j['meta_description']['ar'] ?? '', 320), 'en' => $clean($j['meta_description']['en'] ?? '', 320)],
            'meta_keywords' => ['ar' => $clean($j['meta_keywords']['ar'] ?? '', 300), 'en' => $clean($j['meta_keywords']['en'] ?? '', 300)],
        ];
    }

    /** Only the empty language slots are filled; what the admin already wrote is kept. */
    private static function mergeMeta(array $row, array $meta): array
    {
        $out = [];
        foreach (['meta_title', 'meta_description', 'meta_keywords'] as $f) {
            $cur = is_array($row[$f] ?? null) ? $row[$f] : ['ar' => (string)($row[$f] ?? ''), 'en' => ''];
            $out[$f] = ['ar' => trim((string)($cur['ar'] ?? '')) !== '' ? $cur['ar'] : $meta[$f]['ar'], 'en' => trim((string)($cur['en'] ?? '')) !== '' ? $cur['en'] : $meta[$f]['en']];
        }
        return $out;
    }

    /** Readable text of a block page, per language */
    private static function blocksText(array $blocks): array
    {
        $out = ['ar' => '', 'en' => ''];
        $walk = function ($v) use (&$walk, &$out) {
            if (is_array($v)) {
                if (isset($v['ar']) || isset($v['en'])) {
                    foreach (['ar', 'en'] as $l) { $s = trim(strip_tags((string)($v[$l] ?? ''))); if ($s !== '') $out[$l] .= $s . "\n"; }
                    return;
                }
                foreach ($v as $k => $x) { if ($k !== 'items' || is_array($x)) $walk($x); }
            }
        };
        foreach ($blocks as $b) { if (empty($b['hidden'])) $walk($b['data'] ?? []); }
        return $out;
    }
}
