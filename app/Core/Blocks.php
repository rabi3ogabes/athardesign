<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Page builder block definitions. The same schema drives the admin editor
 * (serialised to JSON) and the public renderer (Views/site/blocks/{type}.php).
 */
final class Blocks
{
    private static function f(string $name, string $type, string $ar, string $en, array $extra = []): array
    {
        return array_merge(['name' => $name, 'type' => $type, 'label' => ['ar' => $ar, 'en' => $en]], $extra);
    }

    private static function eyebrowHeadingIntro(): array
    {
        return [
            self::f('eyebrow', 'text', 'عنوان صغير', 'Eyebrow', ['i18n' => true]),
            self::f('heading', 'text', 'العنوان', 'Heading', ['i18n' => true]),
            self::f('intro', 'textarea', 'مقدمة', 'Intro', ['i18n' => true]),
        ];
    }

    /** @return array<string,array> */
    public static function schema(): array
    {
        static $schema = null;
        if ($schema !== null) return $schema;

        $link = fn(string $n, string $ar, string $en) => self::f($n, 'url', $ar, $en, ['placeholder' => '/contact']);
        $toggle = fn(string $n, string $ar, string $en, bool $d = true) => self::f($n, 'toggle', $ar, $en, ['default' => $d]);

        $schema = [
            'hero' => [
                'label' => ['ar' => 'الواجهة الرئيسية (Hero)', 'en' => 'Hero'],
                'icon' => 'sparkle',
                'fields' => [
                    self::f('eyebrow', 'text', 'عنوان صغير', 'Eyebrow', ['i18n' => true]),
                    self::f('heading', 'textarea', 'العنوان الرئيسي', 'Main heading', ['i18n' => true, 'rows' => 2]),
                    self::f('subheading', 'textarea', 'النص التعريفي', 'Subheading', ['i18n' => true]),
                    self::f('primary_text', 'text', 'نص الزر الأساسي', 'Primary button text', ['i18n' => true]),
                    $link('primary_link', 'رابط الزر الأساسي', 'Primary button link'),
                    self::f('secondary_text', 'text', 'نص الزر الثانوي', 'Secondary button text', ['i18n' => true]),
                    $link('secondary_link', 'رابط الزر الثانوي', 'Secondary button link'),
                    self::f('image', 'image', 'الصورة الرئيسية', 'Main image'),
                    self::f('image_2', 'image', 'صورة ثانية', 'Second image'),
                    self::f('image_3', 'image', 'صورة ثالثة', 'Third image'),
                    self::f('image_alt', 'text', 'وصف الصور (alt)', 'Image alt text', ['i18n' => true]),
                    self::f('stats', 'repeater', 'أرقام', 'Stats', ['fields' => [
                        self::f('value', 'text', 'القيمة', 'Value'),
                        self::f('label', 'text', 'الوصف', 'Label', ['i18n' => true]),
                    ]]),
                ],
            ],
            'marquee' => [
                'label' => ['ar' => 'شريط متحرك', 'en' => 'Marquee strip'],
                'icon' => 'rows',
                'fields' => [
                    self::f('items', 'repeater', 'الكلمات', 'Items', ['fields' => [
                        self::f('text', 'text', 'النص', 'Text', ['i18n' => true]),
                    ]]),
                ],
            ],
            'intro' => [
                'label' => ['ar' => 'بيان تعريفي', 'en' => 'Statement'],
                'icon' => 'article',
                'fields' => [
                    self::f('eyebrow', 'text', 'عنوان صغير', 'Eyebrow', ['i18n' => true]),
                    self::f('heading', 'textarea', 'العنوان', 'Heading', ['i18n' => true, 'rows' => 2]),
                    self::f('body', 'richtext', 'النص', 'Body', ['i18n' => true]),
                    self::f('image', 'image', 'صورة جانبية', 'Side image'),
                    self::f('image_alt', 'text', 'وصف الصورة', 'Image alt', ['i18n' => true]),
                    self::f('button_text', 'text', 'نص الزر', 'Button text', ['i18n' => true]),
                    $link('button_link', 'رابط الزر', 'Button link'),
                ],
            ],
            'services' => [
                'label' => ['ar' => 'الخدمات', 'en' => 'Services'],
                'icon' => 'briefcase',
                'fields' => array_merge(self::eyebrowHeadingIntro(), [
                    self::f('source', 'select', 'المصدر', 'Source', ['options' => ['featured' => ['ar' => 'المميزة فقط', 'en' => 'Featured only'], 'all' => ['ar' => 'كل الخدمات', 'en' => 'All services']], 'default' => 'all']),
                    self::f('limit', 'number', 'الحد الأقصى', 'Max items', ['default' => 6]),
                    $toggle('show_link', 'إظهار رابط "كل الخدمات"', 'Show "all services" link'),
                ]),
            ],
            'packages' => [
                'label' => ['ar' => 'الباقات والأسعار', 'en' => 'Packages & pricing'],
                'icon' => 'crown-simple',
                'fields' => array_merge(self::eyebrowHeadingIntro(), [
                    self::f('category', 'select', 'الخدمة', 'Service line', ['options' => ['all' => ['ar' => 'كل الخدمات', 'en' => 'All service lines'], 'bundle' => ['ar' => 'موقع + سوشيال ميديا (باقات مدمجة)', 'en' => 'Website + social media (combined)'], 'website' => ['ar' => 'تصميم المواقع', 'en' => 'Website design'], 'social' => ['ar' => 'السوشيال ميديا', 'en' => 'Social media'], 'seo' => ['ar' => 'تحسين محركات البحث', 'en' => 'SEO'], 'photography' => ['ar' => 'التصوير', 'en' => 'Photography']], 'default' => 'all']),
                    self::f('layout', 'select', 'عرض الخدمات المتعددة', 'Multiple lines layout', ['options' => ['tabs' => ['ar' => 'تبويبات', 'en' => 'Tabs'], 'stacked' => ['ar' => 'الكل تحت بعض', 'en' => 'Stacked, all visible']], 'default' => 'tabs']),
                    $toggle('show_custom', 'إظهار "تحتاج شيئًا مختلفًا؟"', 'Show "Need something custom?"'),
                ]),
            ],
            'portfolio' => [
                'label' => ['ar' => 'أعمالنا', 'en' => 'Portfolio'],
                'icon' => 'images',
                'fields' => array_merge(self::eyebrowHeadingIntro(), [
                    self::f('source', 'select', 'المصدر', 'Source', ['options' => ['featured' => ['ar' => 'المميزة فقط', 'en' => 'Featured only'], 'all' => ['ar' => 'كل الأعمال', 'en' => 'All projects']], 'default' => 'featured']),
                    self::f('category', 'text', 'تصفية حسب الفئة (slug)', 'Filter by category slug'),
                    self::f('limit', 'number', 'الحد الأقصى', 'Max items', ['default' => 6]),
                    self::f('layout', 'select', 'التخطيط', 'Layout', ['options' => ['bento' => ['ar' => 'بينتو', 'en' => 'Bento'], 'grid' => ['ar' => 'شبكة', 'en' => 'Grid']], 'default' => 'bento']),
                    $toggle('show_filters', 'إظهار فلاتر الفئات', 'Show category filters', false),
                    $toggle('show_link', 'إظهار رابط "كل الأعمال"', 'Show "all work" link'),
                ]),
            ],
            'features' => [
                'label' => ['ar' => 'مميزات / لماذا نحن', 'en' => 'Features / Why us'],
                'icon' => 'seal-check',
                'fields' => array_merge(self::eyebrowHeadingIntro(), [
                    self::f('items', 'repeater', 'العناصر', 'Items', ['fields' => [
                        self::f('icon', 'icon', 'الأيقونة', 'Icon'),
                        self::f('title', 'text', 'العنوان', 'Title', ['i18n' => true]),
                        self::f('text', 'textarea', 'النص', 'Text', ['i18n' => true]),
                    ]]),
                ]),
            ],
            'process' => [
                'label' => ['ar' => 'خطوات العمل', 'en' => 'Process steps'],
                'icon' => 'strategy',
                'fields' => array_merge(self::eyebrowHeadingIntro(), [
                    self::f('steps', 'repeater', 'الخطوات', 'Steps', ['fields' => [
                        self::f('title', 'text', 'العنوان', 'Title', ['i18n' => true]),
                        self::f('text', 'textarea', 'الوصف', 'Description', ['i18n' => true]),
                    ]]),
                ]),
            ],
            'stats' => [
                'label' => ['ar' => 'أرقام وإنجازات', 'en' => 'Stats'],
                'icon' => 'chart-bar',
                'fields' => [
                    self::f('heading', 'text', 'العنوان', 'Heading', ['i18n' => true]),
                    self::f('items', 'repeater', 'الأرقام', 'Numbers', ['fields' => [
                        self::f('value', 'text', 'القيمة (مثال 120+)', 'Value (e.g. 120+)'),
                        self::f('label', 'text', 'الوصف', 'Label', ['i18n' => true]),
                    ]]),
                ],
            ],
            'testimonials' => [
                'label' => ['ar' => 'آراء العملاء', 'en' => 'Testimonials'],
                'icon' => 'quotes',
                'fields' => array_merge(self::eyebrowHeadingIntro(), [
                    self::f('limit', 'number', 'الحد الأقصى', 'Max items', ['default' => 6]),
                ]),
            ],
            'gallery' => [
                'label' => ['ar' => 'معرض صور', 'en' => 'Gallery'],
                'icon' => 'image',
                'fields' => array_merge(self::eyebrowHeadingIntro(), [
                    self::f('images', 'repeater', 'الصور', 'Images', ['fields' => [
                        self::f('image', 'image', 'الصورة', 'Image'),
                        self::f('alt', 'text', 'وصف الصورة', 'Alt text', ['i18n' => true]),
                        self::f('caption', 'text', 'تعليق', 'Caption', ['i18n' => true]),
                    ]]),
                ]),
            ],
            'image_text' => [
                'label' => ['ar' => 'صورة ونص', 'en' => 'Image + text'],
                'icon' => 'layout',
                'fields' => [
                    self::f('eyebrow', 'text', 'عنوان صغير', 'Eyebrow', ['i18n' => true]),
                    self::f('heading', 'text', 'العنوان', 'Heading', ['i18n' => true]),
                    self::f('body', 'richtext', 'النص', 'Body', ['i18n' => true]),
                    self::f('image', 'image', 'الصورة', 'Image'),
                    self::f('image_alt', 'text', 'وصف الصورة', 'Image alt', ['i18n' => true]),
                    self::f('side', 'select', 'موضع الصورة', 'Image side', ['options' => ['start' => ['ar' => 'البداية', 'en' => 'Start'], 'end' => ['ar' => 'النهاية', 'en' => 'End']], 'default' => 'start']),
                    self::f('button_text', 'text', 'نص الزر', 'Button text', ['i18n' => true]),
                    $link('button_link', 'رابط الزر', 'Button link'),
                ],
            ],
            'faq' => [
                'label' => ['ar' => 'أسئلة شائعة', 'en' => 'FAQ'],
                'icon' => 'chat-circle-text',
                'fields' => array_merge(self::eyebrowHeadingIntro(), [
                    self::f('items', 'repeater', 'الأسئلة', 'Questions', ['fields' => [
                        self::f('question', 'text', 'السؤال', 'Question', ['i18n' => true]),
                        self::f('answer', 'textarea', 'الإجابة', 'Answer', ['i18n' => true]),
                    ]]),
                ]),
            ],
            'cta' => [
                'label' => ['ar' => 'دعوة لاتخاذ إجراء (CTA)', 'en' => 'Call to action'],
                'icon' => 'megaphone',
                'fields' => [
                    self::f('heading', 'textarea', 'العنوان', 'Heading', ['i18n' => true, 'rows' => 2]),
                    self::f('text', 'textarea', 'النص', 'Text', ['i18n' => true]),
                    self::f('button_text', 'text', 'نص الزر', 'Button text', ['i18n' => true]),
                    $link('button_link', 'رابط الزر', 'Button link'),
                    $toggle('show_whatsapp', 'إظهار زر واتساب', 'Show WhatsApp button'),
                ],
            ],
            'clients' => [
                'label' => ['ar' => 'شعارات العملاء', 'en' => 'Client logos'],
                'icon' => 'handshake',
                'fields' => [
                    self::f('heading', 'text', 'العنوان', 'Heading', ['i18n' => true]),
                    self::f('logos', 'repeater', 'الشعارات', 'Logos', ['fields' => [
                        self::f('image', 'image', 'الشعار', 'Logo'),
                        self::f('name', 'text', 'الاسم', 'Name'),
                        self::f('link', 'url', 'الرابط', 'Link'),
                    ]]),
                ],
            ],
            'contact' => [
                'label' => ['ar' => 'نموذج التواصل', 'en' => 'Contact form'],
                'icon' => 'envelope-simple',
                'fields' => array_merge(self::eyebrowHeadingIntro(), [
                    $toggle('show_form', 'إظهار النموذج', 'Show form'),
                    $toggle('show_info', 'إظهار بيانات التواصل', 'Show contact details'),
                    $toggle('show_map', 'إظهار رابط الخريطة', 'Show map link'),
                ]),
            ],
            'text' => [
                'label' => ['ar' => 'نص حر', 'en' => 'Rich text'],
                'icon' => 'text-aa',
                'fields' => [
                    self::f('heading', 'text', 'العنوان', 'Heading', ['i18n' => true]),
                    self::f('body', 'richtext', 'النص', 'Body', ['i18n' => true]),
                    self::f('width', 'select', 'العرض', 'Width', ['options' => ['narrow' => ['ar' => 'ضيق (للقراءة)', 'en' => 'Narrow (reading)'], 'wide' => ['ar' => 'عريض', 'en' => 'Wide']], 'default' => 'narrow']),
                ],
            ],
            'html' => [
                'label' => ['ar' => 'كود HTML مخصص', 'en' => 'Custom HTML'],
                'icon' => 'file-text',
                'fields' => [
                    self::f('code', 'code', 'الكود', 'Code', ['i18n' => true, 'rows' => 8]),
                ],
            ],
        ];
        return $schema;
    }

    /** Curated icon names available in the admin icon picker. */
    public static function icons(): array
    {
        return ['sparkle', 'briefcase', 'camera', 'megaphone', 'globe', 'star', 'chart-line-up', 'trend-up', 'target', 'lightning', 'palette', 'paint-brush', 'instagram-logo', 'pen-nib', 'video-camera', 'fork-knife', 'storefront', 'buildings', 'users', 'handshake', 'medal', 'rocket-launch', 'seal-check', 'shield-check', 'timer', 'gauge', 'compass', 'crown-simple', 'strategy', 'chart-bar', 'device-mobile', 'desktop', 'magnifying-glass', 'hash', 'share-network', 'chat-circle-text', 'map-pin', 'phone', 'envelope-simple', 'clock', 'check-circle', 'image', 'images', 'layout', 'cursor-click', 'monitor', 'key', 'bell', 'flag', 'tag'];
    }

    /** Render a list of blocks to HTML. */
    public static function renderAll(array $blocks, array $ctx = []): string
    {
        $html = '';
        foreach ($blocks as $i => $block) {
            if (!is_array($block) || empty($block['type'])) continue;
            if (!empty($block['hidden'])) continue;
            $html .= self::render($block, $i, $ctx);
        }
        return $html;
    }

    public static function render(array $block, int $index, array $ctx = []): string
    {
        $type = preg_replace('/[^a-z_]/', '', (string)$block['type']);
        $file = APP_PATH . '/Views/site/blocks/' . $type . '.php';
        if (!is_file($file)) return '';
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        $anchor = preg_replace('/[^a-zA-Z0-9\-_\x{0600}-\x{06FF}]/u', '', (string)($block['anchor'] ?? ''));
        try {
            return View::make('site/blocks/' . $type, [
                'b' => $data,
                'blk' => $block,
                'i' => $index,
                'anchor' => $anchor !== '' ? $anchor : $type . '-' . $index,
                'theme' => in_array($block['theme'] ?? '', ['ink', 'ivory'], true) ? $block['theme'] : 'ink',
                'ctx' => $ctx,
            ]);
        } catch (\Throwable $e) {
            Log::error('Block render error (' . $type . '): ' . $e->getMessage());
            return filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL) ? '<pre>' . e($e->getMessage()) . '</pre>' : '';
        }
    }

    /**
     * Validate + normalise blocks posted by the admin editor.
     * Unknown types and fields are dropped; rich text is sanitised.
     */
    public static function sanitize(mixed $blocks): array
    {
        if (is_string($blocks)) $blocks = json_decode($blocks, true);
        if (!is_array($blocks)) return [];
        $schema = self::schema();
        $out = [];
        foreach ($blocks as $block) {
            if (!is_array($block) || empty($block['type']) || !isset($schema[$block['type']])) continue;
            $def = $schema[$block['type']];
            $out[] = [
                'type'   => $block['type'],
                'anchor' => mb_substr(preg_replace('/[^a-zA-Z0-9\-_\x{0600}-\x{06FF}]/u', '', (string)($block['anchor'] ?? '')), 0, 60),
                'theme'  => in_array($block['theme'] ?? '', ['ink', 'ivory'], true) ? $block['theme'] : 'ink',
                'hidden' => !empty($block['hidden']),
                'data'   => self::sanitizeFields($def['fields'], is_array($block['data'] ?? null) ? $block['data'] : []),
            ];
        }
        return $out;
    }

    private static function sanitizeFields(array $fields, array $data): array
    {
        $clean = [];
        foreach ($fields as $f) {
            $name = $f['name'];
            $v = $data[$name] ?? null;
            switch ($f['type']) {
                case 'repeater':
                    $rows = [];
                    if (is_array($v)) {
                        foreach (array_slice($v, 0, 60) as $row) {
                            if (is_array($row)) $rows[] = self::sanitizeFields($f['fields'], $row);
                        }
                    }
                    $clean[$name] = $rows;
                    break;
                case 'toggle':
                    $clean[$name] = $v === null ? (bool)($f['default'] ?? false) : filter_var($v, FILTER_VALIDATE_BOOL);
                    break;
                case 'number':
                    $clean[$name] = is_numeric($v) ? (int)$v : (int)($f['default'] ?? 0);
                    break;
                case 'select':
                    $clean[$name] = (is_string($v) && isset($f['options'][$v])) ? $v : (string)($f['default'] ?? array_key_first($f['options']));
                    break;
                case 'icon':
                    $clean[$name] = (is_string($v) && in_array($v, self::icons(), true)) ? $v : '';
                    break;
                case 'image':
                case 'url':
                    $clean[$name] = is_string($v) ? mb_substr(trim($v), 0, 500) : '';
                    break;
                case 'richtext':
                    $clean[$name] = self::i18nValue($v, fn($s) => Html::clean($s), 20000);
                    break;
                case 'code':
                    $clean[$name] = self::i18nValue($v, fn($s) => $s, 50000);
                    break;
                default: // text, textarea
                    $clean[$name] = !empty($f['i18n'])
                        ? self::i18nValue($v, fn($s) => trim(strip_tags($s)), 2000)
                        : (is_string($v) ? mb_substr(trim(strip_tags($v)), 0, 500) : '');
            }
        }
        return $clean;
    }

    private static function i18nValue(mixed $v, callable $fn, int $max): array
    {
        $v = is_array($v) ? $v : ['ar' => (string)$v, 'en' => ''];
        return [
            'ar' => mb_substr($fn((string)($v['ar'] ?? '')), 0, $max),
            'en' => mb_substr($fn((string)($v['en'] ?? '')), 0, $max),
        ];
    }
}
