<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Cache;
use App\Core\Db;

final class Settings
{
    private static ?array $all = null;

    /** Default values + groups. Also drives the settings forms. */
    public static function defaults(): array
    {
        return [
            // general
            'site_name'        => ['ar' => 'أثر لتصميم الويب', 'en' => 'Athar Web Design'],
            'tagline'          => ['ar' => 'نصنع حضورًا رقميًا يترك أثرًا', 'en' => 'Digital presence that leaves a mark'],
            'logo'             => '',
            'favicon'          => '',
            'default_lang'     => 'ar',
            'enable_english'   => true,
            'header_cta_text'  => ['ar' => 'ابدأ مشروعك', 'en' => 'Start a project'],
            'header_cta_link'  => '/contact',
            'footer_text'      => ['ar' => 'وكالة قطرية متخصصة في تصميم المواقع، تحسين محركات البحث، إدارة وتصميم محتوى السوشيال ميديا، والتصوير الاحترافي للمطاعم والشركات.', 'en' => 'A Qatar-based agency for web design, SEO, social media management and content design, and professional photography for restaurants and companies.'],
            'show_whatsapp_button' => true,
            'maintenance_mode' => false,
            // appearance
            'theme'        => 'ink',
            'accent_color' => '',
            'heading_font' => 'lusail',
            'body_font'    => 'lusail',
            'radius'       => 'soft',
            'custom_font_name'    => 'Custom',
            'custom_font_light'   => '',
            'custom_font_regular' => '',
            'custom_font_medium'  => '',
            'custom_font_bold'    => '',
            // contact
            'whatsapp_number'  => '974',
            'whatsapp_message' => ['ar' => 'مرحبًا أثر، أود الاستفسار عن خدماتكم.', 'en' => 'Hello Athar, I would like to ask about your services.'],
            'phone'            => '',
            'email'            => '',
            'address'          => ['ar' => 'الدوحة، قطر', 'en' => 'Doha, Qatar'],
            'map_link'         => '',
            'working_hours'    => ['ar' => 'الأحد – الخميس، 9 صباحًا – 6 مساءً', 'en' => 'Sunday – Thursday, 9am – 6pm'],
            'notify_email'     => '',
            // social
            'social_instagram' => '',
            'social_facebook'  => '',
            'social_tiktok'    => '',
            'social_snapchat'  => '',
            'social_x'         => '',
            'social_linkedin'  => '',
            'social_youtube'   => '',
            'social_threads'   => '',
            'social_pinterest' => '',
            'social_behance'   => '',
            'social_telegram'  => '',
            // seo
            'meta_title_suffix'        => ['ar' => '| أثر لتصميم الويب', 'en' => '| Athar Web Design Qatar'],
            'default_meta_description' => ['ar' => 'أثر لتصميم الويب في قطر: تصميم مواقع فاخرة، تحسين محركات البحث، إدارة وتصميم محتوى السوشيال ميديا، وتصوير احترافي للمطاعم والشركات في الدوحة.', 'en' => 'Athar Web Design, Qatar: luxury website design, SEO, social media management and content design, and professional photography for restaurants and companies in Doha.'],
            'default_meta_keywords'    => ['ar' => 'تصميم مواقع قطر, شركة تصميم مواقع الدوحة, تحسين محركات البحث قطر, إدارة سوشيال ميديا قطر, تصوير مطاعم الدوحة', 'en' => 'web design qatar, website design doha, seo qatar, social media management qatar, restaurant photography doha'],
            'og_image'            => '',
            'schema_type'         => 'ProfessionalService',
            'google_verification' => '',
            'bing_verification'   => '',
            'ga4_id'              => '',
            'gtm_id'              => '',
            'geo_lat'             => '',
            'geo_lng'             => '',
            'robots_txt'          => "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /track\n",
            'head_scripts'        => '',
            // analytics
            'analytics_enabled'        => true,
            'analytics_track_bots'     => false,
            'analytics_track_admins'   => false,
            'analytics_geo'            => true,
            'analytics_retention_days' => 365,
            // mail
            'mail_from'   => '',
            'smtp_host'   => '',
            'smtp_port'   => 587,
            'smtp_user'   => '',
            'smtp_pass'   => '',
            'smtp_secure' => 'tls',
            'mail_smtp_probe' => true,
        ];
    }

    public static function groups(): array
    {
        return [
            'general'   => ['site_name', 'tagline', 'logo', 'favicon', 'default_lang', 'enable_english', 'header_cta_text', 'header_cta_link', 'footer_text', 'show_whatsapp_button', 'maintenance_mode'],
            'appearance' => ['theme', 'accent_color', 'heading_font', 'body_font', 'radius', 'custom_font_name', 'custom_font_light', 'custom_font_regular', 'custom_font_medium', 'custom_font_bold'],
            'contact'   => ['whatsapp_number', 'whatsapp_message', 'phone', 'email', 'address', 'map_link', 'working_hours', 'notify_email'],
            'social'    => ['social_instagram', 'social_facebook', 'social_tiktok', 'social_snapchat', 'social_x', 'social_linkedin', 'social_youtube', 'social_threads', 'social_pinterest', 'social_behance', 'social_telegram'],
            'seo'       => ['meta_title_suffix', 'default_meta_description', 'default_meta_keywords', 'og_image', 'schema_type', 'google_verification', 'bing_verification', 'ga4_id', 'gtm_id', 'geo_lat', 'geo_lng', 'robots_txt', 'head_scripts'],
            'analytics' => ['analytics_enabled', 'analytics_track_bots', 'analytics_track_admins', 'analytics_geo', 'analytics_retention_days'],
            'mail'      => ['mail_from', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_secure', 'mail_smtp_probe'],
        ];
    }

    public static function all(): array
    {
        if (self::$all !== null) return self::$all;
        $stored = Cache::remember('settings.all', function () {
            $out = [];
            foreach (Db::from('settings')->select('key,value')->get() as $row) {
                $out[$row['key']] = $row['value'];
            }
            return $out;
        });
        return self::$all = array_merge(self::defaults(), is_array($stored) ? $stored : []);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /** @param array<string,mixed> $values */
    public static function save(array $values, string $group = 'general'): void
    {
        $rows = [];
        foreach ($values as $k => $v) {
            $rows[] = ['key' => $k, 'value' => $v, 'group' => $group, 'updated_at' => gmdate('c')];
        }
        if ($rows) {
            Db::from('settings')->upsert($rows, 'key');
        }
        self::$all = null;
        Cache::flush();
    }

    /** Typefaces the site can use for headlines and body text (key => labels + CSS stack for previews) */
    public static function fonts(): array
    {
        return [
            'lusail' => ['ar' => 'لوسيل (الخط القطري، عربي ولاتيني)', 'en' => 'Lusail (Qatar typeface, Arabic & Latin)', 'stack' => "'Lusail', sans-serif"],
            'serif'  => ['ar' => 'سيريف تحريري (Amiri / Cormorant)', 'en' => 'Editorial serif (Amiri / Cormorant)', 'stack' => "'Amiri', 'Cormorant Garamond', serif"],
            'sans'   => ['ar' => 'عصري بدون زوائد (IBM Plex Arabic / Plus Jakarta)', 'en' => 'Modern sans (IBM Plex Arabic / Plus Jakarta)', 'stack' => "'IBM Plex Sans Arabic', 'Plus Jakarta Sans', sans-serif"],
            'custom' => ['ar' => 'خط مرفوع (' . self::get('custom_font_name', 'Custom') . ')', 'en' => 'Uploaded font (' . self::get('custom_font_name', 'Custom') . ')', 'stack' => "'AtharCustomPreview', sans-serif"],
        ];
    }

    /** The four upload slots of the custom typeface: slot => [setting key, CSS weight] */
    public static function fontSlots(): array
    {
        return ['light' => ['custom_font_light', '300'], 'regular' => ['custom_font_regular', '400'], 'medium' => ['custom_font_medium', '500'], 'bold' => ['custom_font_bold', '600 700']];
    }

    /** Theme presets shown in the admin (key => labels + swatch colours) */
    public static function themes(): array
    {
        return [
            'ink'      => ['ar' => 'حبر وذهب (الافتراضي)', 'en' => 'Ink & brass (default)', 'bg' => '#0E0D0B', 'fg' => '#F1EADC', 'accent' => '#C9A868'],
            'ivory'    => ['ar' => 'عاجي فاتح', 'en' => 'Ivory light', 'bg' => '#F3EEE3', 'fg' => '#15130F', 'accent' => '#A8853F'],
            'sand'     => ['ar' => 'رمال الدوحة', 'en' => 'Doha sand', 'bg' => '#EFE6D6', 'fg' => '#1B1711', 'accent' => '#7A5C25'],
            'oud'      => ['ar' => 'عود', 'en' => 'Oud maroon', 'bg' => '#1A0B10', 'fg' => '#F4E9E3', 'accent' => '#D4AF6A'],
            'emerald'  => ['ar' => 'زمرد', 'en' => 'Emerald', 'bg' => '#0A1612', 'fg' => '#EEF2EA', 'accent' => '#C8B27A'],
            'midnight' => ['ar' => 'منتصف الليل', 'en' => 'Midnight silver', 'bg' => '#0B0F17', 'fg' => '#EEF1F6', 'accent' => '#BFC8D6'],
        ];
    }

    /** Social links that are filled in: [key => url] */
    public static function socials(): array
    {
        $out = [];
        foreach (['instagram', 'tiktok', 'snapchat', 'x', 'facebook', 'linkedin', 'youtube', 'threads', 'pinterest', 'behance', 'telegram'] as $k) {
            $v = trim((string)self::get('social_' . $k, ''));
            if ($v !== '') $out[$k] = $v;
        }
        return $out;
    }
}
