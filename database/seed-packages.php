<?php
/**
 * Seeds the Silver / Elite / Premium packages for the website and social media
 * service lines, plus the public "Packages" page. Idempotent: runs only when
 * the packages table is empty / the page is missing.
 *
 *   php database/seed-packages.php
 */
declare(strict_types=1);
if (!defined('APP_PATH')) require __DIR__ . '/../app/bootstrap.php';

use App\Core\Db;

$ar = fn(string $a, string $e) => ['ar' => $a, 'en' => $e];
$feat = fn(array $rows) => array_map(fn($r) => ['ar' => $r[0], 'en' => $r[1], 'included' => $r[2] ?? true], $rows);
$out = fn(string $m) => print($m . PHP_EOL);

if (Db::from('packages')->count() === 0) {
    $rows = [
        // ------------------------------------------------ website
        [
            'slug' => 'website-silver', 'category' => 'website', 'tier' => 'silver', 'sort_order' => 10,
            'name' => $ar('موقع تعريفي أنيق', 'Elegant landing website'),
            'tagline' => $ar('مثالي للمطاعم والمقاهي والعيادات التي تحتاج حضورًا سريعًا وفاخرًا.', 'Perfect for restaurants, cafés and clinics that need a fast, luxury presence.'),
            'price' => 2500, 'currency' => 'QAR', 'price_prefix' => $ar('يبدأ من', 'From'), 'period' => $ar('دفعة واحدة', 'one-time'),
            'delivery' => $ar('10 أيام عمل', '10 working days'),
            'features' => $feat([
                ['صفحة رئيسية طويلة حتى 5 أقسام', 'One long home page, up to 5 sections'],
                ['تصميم حصري متوافق مع الجوال', 'Exclusive mobile-first design'],
                ['عربي / إنجليزي', 'Arabic / English'],
                ['زر واتساب ونموذج تواصل', 'WhatsApp button & contact form'],
                ['إعداد SEO أساسي وربط Google', 'Basic SEO setup & Google linking'],
                ['استضافة ونطاق للسنة الأولى', 'Hosting & domain for the first year'],
                ['لوحة تحكم لتعديل المحتوى', 'Content admin panel', false],
                ['صفحات داخلية للخدمات', 'Inner service pages', false],
            ]),
            'body' => $ar('<p>باقة الانطلاق لأي نشاط يريد موقعًا فاخرًا بسرعة. نصمم صفحة رئيسية طويلة تروي قصة علامتك: من أول انطباع إلى زر الطلب، مع أداء عالٍ على الجوال وربط كامل بواتساب وخرائط جوجل.</p><h3>لمن هذه الباقة؟</h3><ul><li>مطعم أو مقهى جديد يريد حضورًا احترافيًا قبل الافتتاح.</li><li>عيادة أو مكتب يحتاج صفحة تعريفية أنيقة مع وسائل تواصل واضحة.</li></ul>', '<p>The launch package for any business that wants a luxury website quickly. We design one long home page that tells your brand story, from the first impression to the order button, with high mobile performance and full WhatsApp and Google Maps integration.</p><h3>Who is it for?</h3><ul><li>A new restaurant or café that wants a professional presence before opening.</li><li>A clinic or office that needs an elegant profile page with clear contact options.</li></ul>'),
            'meta_title' => $ar('باقة سيلفر لتصميم المواقع في قطر | أثر', 'Silver website package in Qatar | Athar'),
            'meta_description' => $ar('موقع تعريفي فاخر ثنائي اللغة يبدأ من 2,500 ر.ق: تصميم حصري، واتساب، SEO أساسي واستضافة. اطلبه عبر واتساب.', 'Luxury bilingual landing website from QAR 2,500: exclusive design, WhatsApp, basic SEO and hosting. Order on WhatsApp.'),
            'meta_keywords' => $ar('باقة تصميم موقع قطر, موقع تعريفي الدوحة, سعر تصميم موقع', 'website package qatar, landing page doha, website price qatar'),
        ],
        [
            'slug' => 'website-elite', 'category' => 'website', 'tier' => 'elite', 'sort_order' => 20, 'is_highlighted' => true,
            'name' => $ar('موقع متكامل بلوحة تحكم', 'Complete website with admin panel'),
            'tagline' => $ar('الخيار الأكثر طلبًا: موقع متعدد الصفحات تديره بنفسك ويظهر في جوجل.', 'The most requested option: a multi-page website you manage yourself and that ranks on Google.'),
            'badge' => $ar('الأكثر طلبًا', 'Most popular'),
            'price' => 5500, 'currency' => 'QAR', 'price_prefix' => $ar('يبدأ من', 'From'), 'period' => $ar('دفعة واحدة', 'one-time'),
            'delivery' => $ar('3 أسابيع', '3 weeks'),
            'features' => $feat([
                ['حتى 8 صفحات (خدمات، أعمال، من نحن، تواصل)', 'Up to 8 pages (services, work, about, contact)'],
                ['لوحة تحكم لتعديل كل المحتوى', 'Admin panel to edit all content'],
                ['عربي / إنجليزي مع إدارة SEO لكل صفحة', 'Arabic / English with per-page SEO'],
                ['تتبّع الزوار ونقرات واتساب', 'Visitor & WhatsApp click tracking'],
                ['تصميم حصري + حركة (Motion)', 'Exclusive design + motion'],
                ['سرعة تحميل محسّنة وصور WebP', 'Optimised speed & WebP images'],
                ['استضافة ونطاق للسنة الأولى', 'Hosting & domain for the first year'],
                ['دعم فني 3 أشهر بعد الإطلاق', '3 months post-launch support'],
                ['جلسة تصوير احترافية', 'Professional photoshoot', false],
            ]),
            'body' => $ar('<p>الباقة التي يختارها معظم عملائنا في الدوحة. موقع متعدد الصفحات بتصميم حصري وحركة أنيقة، مع لوحة تحكم كاملة تتيح لك إضافة الخدمات والأعمال وتعديل النصوص والصور في أي وقت دون الرجوع إلينا.</p><h3>ماذا يميزها؟</h3><ul><li>صفحة لكل خدمة مع كلمات مفتاحية ووصف مخصص لجوجل.</li><li>لوحة تحكم تعرض لك من زار موقعك ومن ضغط على واتساب.</li><li>مهيأة للنمو: أضف صفحات جديدة في دقائق.</li></ul>', '<p>The package most of our Doha clients choose. A multi-page website with an exclusive design and elegant motion, plus a full admin panel that lets you add services and work, and edit texts and images at any time without coming back to us.</p><h3>What makes it special?</h3><ul><li>A page per service with its own keywords and Google description.</li><li>An admin panel that shows who visited and who tapped WhatsApp.</li><li>Built to grow: add new pages in minutes.</li></ul>'),
            'meta_title' => $ar('باقة إيليت لتصميم المواقع في قطر | أثر', 'Elite website package in Qatar | Athar'),
            'meta_description' => $ar('موقع متكامل بلوحة تحكم وSEO وتتبّع زوار يبدأ من 5,500 ر.ق. الباقة الأكثر طلبًا للمطاعم والشركات في الدوحة.', 'Complete website with admin panel, SEO and visitor tracking from QAR 5,500. The most popular package for restaurants and companies in Doha.'),
            'meta_keywords' => $ar('تصميم موقع بلوحة تحكم قطر, سعر موقع شركة الدوحة', 'website with admin panel qatar, company website price doha'),
        ],
        [
            'slug' => 'website-premium', 'category' => 'website', 'tier' => 'premium', 'sort_order' => 30,
            'name' => $ar('منصة فاخرة بهوية كاملة', 'Luxury platform with full identity'),
            'tagline' => $ar('للعلامات التي تريد التميز الكامل: هوية، تصوير، موقع، وSEO لمدة 3 أشهر.', 'For brands that want it all: identity, photoshoot, website and 3 months of SEO.'),
            'price' => 9500, 'currency' => 'QAR', 'price_prefix' => $ar('يبدأ من', 'From'), 'period' => $ar('دفعة واحدة', 'one-time'),
            'delivery' => $ar('5 – 6 أسابيع', '5 – 6 weeks'),
            'features' => $feat([
                ['كل ما في باقة إيليت', 'Everything in Elite'],
                ['صفحات غير محدودة + مدونة', 'Unlimited pages + blog'],
                ['هوية بصرية (شعار، ألوان، خطوط)', 'Brand identity (logo, colours, type)'],
                ['جلسة تصوير احترافية للمكان والمنتجات', 'Professional shoot of the venue & products'],
                ['قائمة طعام / كتالوج رقمي', 'Digital menu / catalogue'],
                ['حجز أو طلب عبر الموقع', 'Booking or ordering on the website'],
                ['SEO شهري لمدة 3 أشهر', 'Monthly SEO for 3 months'],
                ['دعم فني سنة كاملة', 'One full year of support'],
            ]),
            'body' => $ar('<p>تجربة متكاملة من الهوية إلى الإطلاق. نبدأ بورشة لفهم علامتك، نصمم الهوية، نصوّر المكان والمنتجات، ثم نبني منصة فاخرة تتضمن القائمة أو الكتالوج والحجز، ونتابع ظهورها في جوجل لثلاثة أشهر بعد الإطلاق.</p><h3>النتيجة</h3><p>علامة متسقة في كل نقطة تماس: الموقع، السوشيال ميديا، والمطبوعات.</p>', '<p>An end-to-end experience from identity to launch. We start with a workshop to understand your brand, design the identity, shoot the venue and products, then build a luxury platform with menu or catalogue and booking, and follow its Google visibility for three months after launch.</p><h3>The result</h3><p>A consistent brand at every touchpoint: website, social media and print.</p>'),
            'meta_title' => $ar('باقة بريميوم: هوية وموقع وتصوير في قطر | أثر', 'Premium package: identity, website & photoshoot in Qatar | Athar'),
            'meta_description' => $ar('الباقة الشاملة للعلامات الفاخرة في قطر: هوية بصرية، جلسة تصوير، منصة متكاملة وSEO لثلاثة أشهر. تبدأ من 9,500 ر.ق.', 'The complete package for luxury brands in Qatar: brand identity, photoshoot, full platform and 3 months of SEO. From QAR 9,500.'),
            'meta_keywords' => $ar('هوية بصرية وموقع قطر, باقة شاملة تصميم الدوحة', 'brand identity and website qatar, complete web package doha'),
        ],
        // ------------------------------------------------ social media
        [
            'slug' => 'social-silver', 'category' => 'social', 'tier' => 'silver', 'sort_order' => 10,
            'name' => $ar('حضور منتظم', 'Consistent presence'),
            'tagline' => $ar('ابدأ بمحتوى احترافي منتظم على إنستغرام وسناب شات دون عناء.', 'Start with professional, regular content on Instagram and Snapchat without the hassle.'),
            'price' => 1500, 'currency' => 'QAR', 'price_prefix' => $ar('', ''), 'period' => $ar('/ شهريًا', '/ month'),
            'delivery' => $ar('خطة محتوى خلال 5 أيام', 'Content plan within 5 days'),
            'features' => $feat([
                ['8 تصاميم منشورات شهريًا', '8 designed posts per month'],
                ['8 قصص (Stories)', '8 stories'],
                ['كتابة المحتوى عربي / إنجليزي', 'Copywriting in Arabic / English'],
                ['جدولة ونشر', 'Scheduling & publishing'],
                ['تقرير أداء شهري', 'Monthly performance report'],
                ['الرد على التعليقات والرسائل', 'Community management', false],
                ['فيديوهات ريلز', 'Reels', false],
            ]),
            'body' => $ar('<p>لأصحاب المطاعم والمتاجر الذين يريدون حسابًا نشطًا وأنيقًا دون تفرغ. نضع خطة شهرية، نصمم المنشورات والقصص بهوية موحدة، ونجدولها في أفضل أوقات التفاعل في قطر.</p>', '<p>For restaurant and shop owners who want an active, elegant account without the time commitment. We plan each month, design posts and stories with a unified identity, and schedule them at the best engagement times in Qatar.</p>'),
            'meta_title' => $ar('باقة سيلفر لإدارة السوشيال ميديا في قطر | أثر', 'Silver social media package in Qatar | Athar'),
            'meta_description' => $ar('8 منشورات و8 قصص شهريًا بتصميم احترافي وجدولة وتقرير أداء، بـ 1,500 ر.ق شهريًا. اطلبها عبر واتساب.', '8 posts and 8 stories a month, professionally designed, scheduled and reported, for QAR 1,500 a month. Order on WhatsApp.'),
            'meta_keywords' => $ar('إدارة انستقرام قطر, تصميم منشورات الدوحة, باقة سوشيال ميديا', 'instagram management qatar, social media package doha'),
        ],
        [
            'slug' => 'social-elite', 'category' => 'social', 'tier' => 'elite', 'sort_order' => 20, 'is_highlighted' => true,
            'name' => $ar('نمو وتفاعل', 'Growth & engagement'),
            'tagline' => $ar('محتوى أغنى، ريلز، وإدارة كاملة للتفاعل مع جمهورك.', 'Richer content, reels and full community management.'),
            'badge' => $ar('الأفضل قيمة', 'Best value'),
            'price' => 2800, 'currency' => 'QAR', 'price_prefix' => $ar('', ''), 'period' => $ar('/ شهريًا', '/ month'),
            'delivery' => $ar('بداية فورية بعد الاجتماع', 'Starts right after the kick-off'),
            'features' => $feat([
                ['16 تصميم منشور شهريًا', '16 designed posts per month'],
                ['20 قصة + 4 ريلز', '20 stories + 4 reels'],
                ['إدارة التعليقات والرسائل', 'Comments & DM management'],
                ['استراتيجية محتوى وهاشتاقات', 'Content & hashtag strategy'],
                ['منصتان (إنستغرام + تيك توك أو سناب)', 'Two platforms (Instagram + TikTok or Snapchat)'],
                ['تقرير أداء نصف شهري', 'Bi-weekly performance report'],
                ['إدارة حملة إعلانية (ميزانية الإعلان منفصلة)', 'One ad campaign managed (ad budget separate)'],
                ['جلسة تصوير شهرية', 'Monthly photoshoot', false],
            ]),
            'body' => $ar('<p>الباقة المناسبة لمن يريد نموًا حقيقيًا في المتابعين والطلبات. نمزج بين المنشورات المصممة والريلز القصيرة، ونتفاعل مع جمهورك يوميًا، مع حملة إعلانية مدارة شهريًا لتوسيع الوصول.</p>', '<p>The right package for real growth in followers and orders. We mix designed posts with short reels, engage with your audience daily, and run one managed ad campaign each month to widen reach.</p>'),
            'meta_title' => $ar('باقة إيليت لإدارة السوشيال ميديا في قطر | أثر', 'Elite social media package in Qatar | Athar'),
            'meta_description' => $ar('16 منشورًا، 20 قصة، 4 ريلز، إدارة التفاعل وحملة إعلانية بـ 2,800 ر.ق شهريًا. الأفضل قيمة للمطاعم في الدوحة.', '16 posts, 20 stories, 4 reels, community management and one ad campaign for QAR 2,800 a month. Best value for Doha restaurants.'),
            'meta_keywords' => $ar('إدارة سوشيال ميديا للمطاعم قطر, ريلز الدوحة, حملات إعلانية انستقرام', 'restaurant social media qatar, reels doha, instagram ads qatar'),
        ],
        [
            'slug' => 'social-premium', 'category' => 'social', 'tier' => 'premium', 'sort_order' => 30,
            'name' => $ar('إدارة كاملة مع تصوير', 'Full management with photoshoot'),
            'tagline' => $ar('فريق محتوى متكامل: تصوير شهري، ريلز، إعلانات، ومدير حساب مخصص.', 'A complete content team: monthly shoot, reels, ads and a dedicated account manager.'),
            'price' => 4500, 'currency' => 'QAR', 'price_prefix' => $ar('', ''), 'period' => $ar('/ شهريًا', '/ month'),
            'delivery' => $ar('بداية فورية بعد الاجتماع', 'Starts right after the kick-off'),
            'features' => $feat([
                ['كل ما في باقة إيليت', 'Everything in Elite'],
                ['24 منشورًا + قصص يومية + 8 ريلز', '24 posts + daily stories + 8 reels'],
                ['جلسة تصوير احترافية شهرية', 'Monthly professional photoshoot'],
                ['3 منصات', 'Three platforms'],
                ['إدارة حملتين إعلانيتين شهريًا', 'Two managed ad campaigns per month'],
                ['مدير حساب مخصص ورد خلال ساعة', 'Dedicated account manager, 1-hour response'],
                ['تغطية فعالية أو إطلاق واحدة شهريًا', 'One event / launch coverage per month'],
                ['تقرير أسبوعي واجتماع شهري', 'Weekly report & monthly review'],
            ]),
            'body' => $ar('<p>لعلامات تريد أن تتصدر السوشيال ميديا في قطر. فريق كامل يصوّر، يصمم، يكتب، ينشر، ويدير الإعلانات، مع مدير حساب مخصص واجتماع شهري لمراجعة النتائج والخطة.</p>', '<p>For brands that want to lead social media in Qatar. A full team shoots, designs, writes, publishes and manages ads, with a dedicated account manager and a monthly review of results and plan.</p>'),
            'meta_title' => $ar('باقة بريميوم لإدارة السوشيال ميديا مع تصوير في قطر | أثر', 'Premium social media package with photoshoot in Qatar | Athar'),
            'meta_description' => $ar('إدارة كاملة: 24 منشورًا، ريلز، تصوير شهري، إعلانات ومدير حساب مخصص بـ 4,500 ر.ق شهريًا.', 'Full management: 24 posts, reels, monthly shoot, ads and a dedicated account manager for QAR 4,500 a month.'),
            'meta_keywords' => $ar('إدارة حسابات مع تصوير قطر, وكالة سوشيال ميديا الدوحة', 'social media agency doha, content and photography package qatar'),
        ],
    ];
    foreach ($rows as $r) {
        $r += ['is_highlighted' => false, 'badge' => $ar('', ''), 'price_note' => $ar('', ''), 'whatsapp_message' => $ar('', ''), 'cta_text' => $ar('', ''), 'image' => '', 'status' => 'published'];
        Db::from('packages')->insert($r);
    }
    $out('packages seeded (' . count($rows) . ')');
} else {
    $out('packages already present — skipped');
}

// ---------------------------------------------------------------- "Packages" page (in the main navigation)
if (Db::from('pages')->select('id')->eq('slug', 'packages')->first() === null) {
    $blk = fn(string $type, array $data, string $theme = 'ink', string $anchor = '') => ['type' => $type, 'anchor' => $anchor, 'theme' => $theme, 'hidden' => false, 'data' => $data];
    $blocks = [
        $blk('packages', [
            'eyebrow' => $ar('باقاتنا', 'Our packages'),
            'heading' => $ar("اختر الباقة التي تناسب طموحك", 'Choose the package that fits your ambition'),
            'intro' => $ar('ثلاث فئات واضحة لكل خدمة: سيلفر، إيليت، وبريميوم. اختر باقتك واطلبها عبر واتساب برسالة جاهزة، وسنعود إليك خلال ساعات.', 'Three clear tiers for every service: Silver, Elite and Premium. Pick yours and order on WhatsApp with a ready-made message; we reply within hours.'),
            'category' => 'all', 'show_custom' => true,
        ], 'ink', 'packages'),
        $blk('faq', ['eyebrow' => $ar('أسئلة شائعة', 'FAQ'), 'heading' => $ar('قبل أن تختار', 'Before you choose'), 'intro' => $ar('', ''), 'items' => [
            ['question' => $ar('هل يمكن تعديل الباقة؟', 'Can a package be customised?'), 'answer' => $ar('نعم. الباقات نقطة انطلاق، ويمكن إضافة أو حذف عناصر بحسب نشاطك، ونؤكد السعر النهائي عبر واتساب.', 'Yes. Packages are a starting point; items can be added or removed to fit your business, and we confirm the final price on WhatsApp.')],
            ['question' => $ar('كيف يتم الدفع؟', 'How does payment work?'), 'answer' => $ar('50% عند البدء و50% عند التسليم لباقات المواقع، وشهريًا مقدمًا لباقات السوشيال ميديا.', '50% to start and 50% on delivery for website packages; monthly in advance for social media packages.')],
            ['question' => $ar('هل الأسعار تشمل الاستضافة والنطاق؟', 'Do prices include hosting and domain?'), 'answer' => $ar('باقات المواقع تشمل الاستضافة والنطاق للسنة الأولى، ثم تُجدَّد بتكلفة رمزية سنويًا.', 'Website packages include hosting and domain for the first year, then renew at a small yearly cost.')],
            ['question' => $ar('ما مدة الالتزام في باقات السوشيال ميديا؟', 'What is the commitment on social media packages?'), 'answer' => $ar('نوصي بثلاثة أشهر كحد أدنى لرؤية نتائج حقيقية، دون عقود طويلة.', 'We recommend a three-month minimum to see real results, with no long contracts.')],
        ]], 'ivory'),
        $blk('cta', ['heading' => $ar('لم تجد باقتك؟ نصمّمها معك', "Didn't find your package? Let's shape one together"), 'text' => $ar('', ''), 'button_text' => $ar('تواصل معنا', 'Contact us'), 'button_link' => '/contact', 'show_whatsapp' => true], 'ink'),
    ];
    Db::from('pages')->insert([
        'slug' => 'packages', 'template' => 'custom', 'title' => $ar('الباقات', 'Packages'), 'nav_label' => $ar('الباقات والأسعار', 'Packages & pricing'),
        'blocks' => $blocks,
        'meta_title' => $ar('الباقات والأسعار: تصميم مواقع وسوشيال ميديا في قطر | أثر', 'Packages & pricing: web design and social media in Qatar | Athar'),
        'meta_description' => $ar('باقات سيلفر وإيليت وبريميوم لتصميم المواقع وإدارة السوشيال ميديا في قطر بأسعار واضحة. اختر باقتك واطلبها عبر واتساب برسالة جاهزة.', 'Silver, Elite and Premium packages for web design and social media management in Qatar with clear prices. Pick yours and order on WhatsApp with a ready message.'),
        'meta_keywords' => $ar('أسعار تصميم المواقع قطر, باقات سوشيال ميديا الدوحة, باقة تصميم موقع', 'web design prices qatar, social media packages doha, website package qatar'),
        'show_in_nav' => true, 'show_in_footer' => true, 'nav_order' => 15, 'is_system' => false, 'status' => 'published', 'noindex' => false,
    ]);
    $out('packages page seeded');
} else {
    $out('packages page already present — skipped');
}
\App\Core\Cache::flush();
