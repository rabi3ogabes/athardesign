<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Cache;
use App\Core\Db;

/**
 * Service packages: a tier (silver / elite / premium) inside a service line (website / social / ...).
 */
final class Packages
{
    public const TIERS = ['silver', 'elite', 'premium'];
    public const CATEGORIES = ['bundle', 'website', 'social', 'seo', 'photography'];

    /** @return array<string,array{ar:string,en:string}> */
    public static function categoryLabels(): array
    {
        return [
            'bundle'      => ['ar' => 'تصميم المواقع + السوشيال ميديا', 'en' => 'Website + social media'],
            'website'     => ['ar' => 'تصميم المواقع', 'en' => 'Website design'],
            'social'      => ['ar' => 'السوشيال ميديا', 'en' => 'Social media'],
            'seo'         => ['ar' => 'تحسين محركات البحث', 'en' => 'SEO'],
            'photography' => ['ar' => 'التصوير', 'en' => 'Photography'],
        ];
    }

    /** @return array<string,array{ar:string,en:string}> */
    public static function tierLabels(): array
    {
        return [
            'silver'  => ['ar' => 'سيلفر', 'en' => 'Silver'],
            'elite'   => ['ar' => 'إيليت', 'en' => 'Elite'],
            'premium' => ['ar' => 'بريميوم', 'en' => 'Premium'],
        ];
    }

    public static function published(): array
    {
        return Cache::remember('packages.published', fn() => self::sort(Db::from('packages')->eq('status', 'published')->get()));
    }

    /** Published packages grouped by category, each group ordered silver → elite → premium */
    public static function grouped(?string $category = null): array
    {
        $out = [];
        foreach (self::published() as $p) {
            if ($category && $p['category'] !== $category) continue;
            $out[$p['category']][] = $p;
        }
        return $out;
    }

    public static function bySlug(string $slug): ?array
    {
        foreach (self::published() as $p) {
            if ($p['slug'] === $slug) return $p;
        }
        return null;
    }

    public static function all(): array
    {
        return self::sort(Db::from('packages')->get());
    }

    public static function find(string $id): ?array
    {
        if (!preg_match('/^[0-9a-f\-]{36}$/', $id)) return null;
        return Db::from('packages')->eq('id', $id)->first();
    }

    public static function create(array $data): array
    {
        $row = Db::from('packages')->insert($data)[0] ?? [];
        Cache::flush();
        return $row;
    }

    public static function update(string $id, array $data): void
    {
        Db::from('packages')->eq('id', $id)->update($data);
        Cache::flush();
    }

    public static function delete(string $id): void
    {
        Db::from('packages')->eq('id', $id)->delete();
        Cache::flush();
    }

    public static function slugExists(string $slug, ?string $exceptId = null): bool
    {
        $q = Db::from('packages')->select('id')->eq('slug', $slug);
        if ($exceptId) $q->neq('id', $exceptId);
        return $q->first() !== null;
    }

    public static function reorder(array $ids): void
    {
        foreach (array_values($ids) as $i => $id) {
            if (preg_match('/^[0-9a-f\-]{36}$/', (string)$id)) {
                Db::from('packages')->eq('id', $id)->update(['sort_order' => ($i + 1) * 10]);
            }
        }
        Cache::flush();
    }

    /** Formatted price, e.g. "2,500 QAR" (empty when the package has no fixed price) */
    public static function price(array $p, ?string $lang = null): string
    {
        if ($p['price'] === null || $p['price'] === '') return '';
        $n = (float)$p['price'];
        $num = number_format($n, fmod($n, 1.0) === 0.0 ? 0 : 2, '.', ',');
        $cur = strtoupper((string)($p['currency'] ?: 'QAR'));
        $lang ??= lang();
        return $lang === 'ar' && $cur === 'QAR' ? $num . ' ر.ق' : $num . ' ' . $cur;
    }

    /** Ready-to-send WhatsApp text for a package (uses the package template or the site default) */
    public static function whatsappMessage(array $p, ?string $lang = null): string
    {
        $lang ??= lang();
        $tpl = tr($p['whatsapp_message'] ?? '', $lang);
        if ($tpl === '') {
            $tpl = $lang === 'ar'
                ? "مرحبًا {site}، أرغب بطلب باقة {package} ({tier}) من {category}{price_line}.\nالرجاء التواصل معي لبدء التنفيذ."
                : "Hello {site}, I would like to order the {package} package ({tier}) for {category}{price_line}.\nPlease contact me to get started.";
        }
        $price = self::price($p, $lang);
        $priceLine = $price === '' ? '' : ($lang === 'ar' ? ' بسعر ' . $price : ' at ' . $price);
        $map = [
            '{site}'       => setting_tr('site_name'),
            '{package}'    => tr($p['name'] ?? '', $lang),
            '{tier}'       => self::tierLabels()[$p['tier']][$lang] ?? $p['tier'],
            '{category}'   => self::categoryLabels()[$p['category']][$lang] ?? $p['category'],
            '{price}'      => $price,
            '{price_line}' => $priceLine,
            '{url}'        => url('/packages/' . $p['slug'], $lang),
        ];
        return trim(strtr($tpl, $map));
    }

    public static function whatsappLink(array $p, ?string $lang = null): string
    {
        return whatsapp_link(null, self::whatsappMessage($p, $lang));
    }

    private static function sort(array $rows): array
    {
        $tierRank = array_flip(self::TIERS);
        $catRank = array_flip(self::CATEGORIES);
        usort($rows, fn($a, $b) => [$catRank[$a['category']] ?? 9, $a['sort_order'], $tierRank[$a['tier']] ?? 9, $a['created_at']] <=> [$catRank[$b['category']] ?? 9, $b['sort_order'], $tierRank[$b['tier']] ?? 9, $b['created_at']]);
        return $rows;
    }
}
