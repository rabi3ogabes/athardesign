<?php
/**
 * Combined "Website + social media" packages (Silver / Elite / Premium).
 * Idempotent: inserts the three bundles only when none exist, then points the
 * public Packages page at the bundle line and parks the single-line packages as drafts
 * (they stay in the admin for later use).
 *
 *   php database/seed-bundles.php
 */
declare(strict_types=1);
if (!defined('APP_PATH')) require __DIR__ . '/../app/bootstrap.php';

use App\Core\Db;

$ar = fn(string $a, string $e) => ['ar' => $a, 'en' => $e];
$feat = fn(array $rows) => array_map(fn($r) => ['ar' => $r[0], 'en' => $r[1], 'included' => $r[2] ?? true], $rows);
$out = fn(string $m) => print($m . PHP_EOL);

if (Db::from('packages')->eq('category', 'bundle')->count() === 0) {
    $rows = [
        [
            'slug' => 'bundle-silver', 'category' => 'bundle', 'tier' => 'silver', 'sort_order' => 10,
            'name' => $ar('انطلاقة متكاملة', 'Complete launch'),
            'tagline' => $ar('موقع تعريفي أنيق + حضور منتظم على السوشيال ميديا. كل ما يحتاجه نشاط جديد ليبدأ بثقة.', 'An elegant landing website plus a consistent social presence. Everything a new business needs to start with confidence.'),
            'price' => 3500, 'currency' => 'QAR', 'price_prefix' => $ar('الموقع', 'Website'), 'period' => $ar('+ 1,200 ر.ق / شهريًا للسوشيال ميديا', '+ QAR 1,200 / month for social media'),
            'price_note' => $ar('وفّر 800 ر.ق مقارنة بالباقتين منفصلتين.', 'Save QAR 800 compared to buying both separately.'),
            'delivery' => $ar('الموقع خلال 10 أيام عمل', 'Website in 10 working days'),
            'features' => $feat([
                ['موقع تعريفي: صفحة رئيسية حتى 5 أقسام', 'Landing website: one home page, up to 5 sections'],
                ['تصميم حصري عربي / إنجليزي متوافق مع الجوال', 'Exclusive bilingual, mobile-first design'],
                ['زر واتساب ونموذج تواصل', 'WhatsApp button & contact form'],
                ['إعداد SEO أساسي + استضافة ونطاق للسنة الأولى', 'Basic SEO + hosting & domain for the first year'],
                ['سوشيال ميديا: 8 منشورات + 8 قصص شهريًا', 'Social: 8 posts + 8 stories per month'],
                ['كتابة المحتوى وجدولة النشر', 'Copywriting & scheduling'],
                ['تقرير أداء شهري', 'Monthly performance report'],
                ['لوحة تحكم لتعديل الموقع', 'Website admin panel', false],
                ['ريلز وإدارة التفاعل', 'Reels & community management', false],
            ]),
            'body' => $ar('<p>باقة واحدة تجمع أساسيات الحضور الرقمي: موقع فاخر يعرّف بنشاطك ويحوّل الزائر إلى عميل، وحساب سوشيال ميديا نشط بمحتوى مصمم بهوية موحدة.</p><h3>لمن هذه الباقة؟</h3><ul><li>مطعم أو مقهى أو عيادة جديدة تريد الانطلاق بحضور احترافي كامل.</li><li>نشاط قائم يريد توحيد هويته على الموقع والسوشيال ميديا بميزانية مدروسة.</li></ul>', '<p>One package with the essentials of a digital presence: a luxury website that introduces your business and turns visitors into clients, and an active social account with content designed under one identity.</p><h3>Who is it for?</h3><ul><li>A new restaurant, café or clinic that wants to launch with a complete professional presence.</li><li>An existing business that wants one identity across website and social media on a sensible budget.</li></ul>'),
            'meta_title' => $ar('باقة سيلفر: موقع + سوشيال ميديا في قطر | أثر', 'Silver package: website + social media in Qatar | Athar'),
            'meta_description' => $ar('موقع تعريفي فاخر ثنائي اللغة بـ 3,500 ر.ق + إدارة سوشيال ميديا بـ 1,200 ر.ق شهريًا. باقة الانطلاقة المتكاملة للمطاعم والشركات في الدوحة.', 'Luxury bilingual landing website for QAR 3,500 + social media management for QAR 1,200 a month. The complete launch package for restaurants and companies in Doha.'),
            'meta_keywords' => $ar('باقة موقع وسوشيال ميديا قطر, سعر موقع وانستقرام الدوحة', 'website and social media package qatar, web and instagram bundle doha'),
        ],
        [
            'slug' => 'bundle-elite', 'category' => 'bundle', 'tier' => 'elite', 'sort_order' => 20, 'is_highlighted' => true,
            'name' => $ar('نمو متكامل', 'Integrated growth'),
            'tagline' => $ar('موقع متعدد الصفحات بلوحة تحكم + إدارة كاملة للسوشيال ميديا مع ريلز وإعلانات. الخيار الأكثر طلبًا.', 'A multi-page website with admin panel plus full social media management with reels and ads. The most requested option.'),
            'badge' => $ar('الأكثر طلبًا', 'Most popular'),
            'price' => 6500, 'currency' => 'QAR', 'price_prefix' => $ar('الموقع', 'Website'), 'period' => $ar('+ 2,400 ر.ق / شهريًا للسوشيال ميديا', '+ QAR 2,400 / month for social media'),
            'price_note' => $ar('وفّر 1,400 ر.ق مقارنة بالباقتين منفصلتين.', 'Save QAR 1,400 compared to buying both separately.'),
            'delivery' => $ar('الموقع خلال 3 أسابيع', 'Website in 3 weeks'),
            'features' => $feat([
                ['موقع حتى 8 صفحات بلوحة تحكم كاملة', 'Website up to 8 pages with full admin panel'],
                ['SEO لكل صفحة + تتبّع الزوار ونقرات واتساب', 'Per-page SEO + visitor & WhatsApp click tracking'],
                ['تصميم حصري مع حركة (Motion)', 'Exclusive design with motion'],
                ['استضافة ونطاق للسنة الأولى + دعم 3 أشهر', 'Hosting & domain for the first year + 3 months support'],
                ['سوشيال ميديا: 16 منشورًا + 20 قصة + 4 ريلز شهريًا', 'Social: 16 posts + 20 stories + 4 reels per month'],
                ['إدارة التعليقات والرسائل', 'Comments & DM management'],
                ['منصتان + استراتيجية محتوى وهاشتاقات', 'Two platforms + content & hashtag strategy'],
                ['حملة إعلانية مدارة شهريًا (ميزانية الإعلان منفصلة)', 'One managed ad campaign per month (ad budget separate)'],
                ['تقرير أداء نصف شهري', 'Bi-weekly performance report'],
                ['جلسة تصوير شهرية', 'Monthly photoshoot', false],
            ]),
            'body' => $ar('<p>الباقة التي يختارها معظم عملائنا: موقع يبيع ويظهر في جوجل، وحسابات سوشيال ميديا تنمو بمحتوى مصمم وريلز وحملات إعلانية مدارة. فريق واحد، هوية واحدة، وتقارير واضحة.</p><h3>ماذا يميزها؟</h3><ul><li>لوحة تحكم تعرض لك من زار موقعك ومن ضغط على واتساب.</li><li>محتوى السوشيال ميديا يقود الزوار إلى الموقع، والموقع يحوّلهم إلى عملاء.</li><li>حملة إعلانية شهرية مدارة لتوسيع الوصول.</li></ul>', '<p>The package most of our clients choose: a website that sells and ranks on Google, and social accounts that grow with designed content, reels and managed ad campaigns. One team, one identity, clear reports.</p><h3>What makes it special?</h3><ul><li>An admin panel that shows who visited and who tapped WhatsApp.</li><li>Social content drives visitors to the website, and the website turns them into clients.</li><li>One managed ad campaign a month to widen reach.</li></ul>'),
            'meta_title' => $ar('باقة إيليت: موقع بلوحة تحكم + سوشيال ميديا في قطر | أثر', 'Elite package: website with admin panel + social media in Qatar | Athar'),
            'meta_description' => $ar('موقع متكامل بلوحة تحكم بـ 6,500 ر.ق + إدارة سوشيال ميديا مع ريلز وإعلانات بـ 2,400 ر.ق شهريًا. الباقة الأكثر طلبًا في الدوحة.', 'Complete website with admin panel for QAR 6,500 + social media management with reels and ads for QAR 2,400 a month. The most popular package in Doha.'),
            'meta_keywords' => $ar('باقة نمو رقمي قطر, موقع وإدارة سوشيال ميديا الدوحة', 'digital growth package qatar, website and social media management doha'),
        ],
        [
            'slug' => 'bundle-premium', 'category' => 'bundle', 'tier' => 'premium', 'sort_order' => 30,
            'name' => $ar('هيمنة رقمية', 'Digital leadership'),
            'tagline' => $ar('هوية كاملة، تصوير، منصة فاخرة، وفريق محتوى متكامل مع مدير حساب مخصص. للعلامات التي تريد الصدارة.', 'Full identity, photoshoot, luxury platform and a complete content team with a dedicated account manager. For brands that want to lead.'),
            'price' => 11000, 'currency' => 'QAR', 'price_prefix' => $ar('الموقع والهوية', 'Website & identity'), 'period' => $ar('+ 3,900 ر.ق / شهريًا للسوشيال ميديا', '+ QAR 3,900 / month for social media'),
            'price_note' => $ar('وفّر 2,100 ر.ق مقارنة بالباقتين منفصلتين.', 'Save QAR 2,100 compared to buying both separately.'),
            'delivery' => $ar('الهوية والموقع خلال 5 – 6 أسابيع', 'Identity & website in 5 – 6 weeks'),
            'features' => $feat([
                ['كل ما في باقة إيليت', 'Everything in Elite'],
                ['هوية بصرية كاملة (شعار، ألوان، خطوط)', 'Full brand identity (logo, colours, type)'],
                ['منصة فاخرة بصفحات غير محدودة + مدونة', 'Luxury platform with unlimited pages + blog'],
                ['قائمة طعام / كتالوج رقمي + حجز أو طلب', 'Digital menu / catalogue + booking or ordering'],
                ['SEO شهري لمدة 3 أشهر ودعم سنة كاملة', 'Monthly SEO for 3 months and a full year of support'],
                ['سوشيال ميديا: 24 منشورًا + قصص يومية + 8 ريلز', 'Social: 24 posts + daily stories + 8 reels'],
                ['جلسة تصوير احترافية شهرية', 'Monthly professional photoshoot'],
                ['3 منصات + حملتان إعلانيتان شهريًا', 'Three platforms + two ad campaigns per month'],
                ['مدير حساب مخصص ورد خلال ساعة', 'Dedicated account manager, 1-hour response'],
                ['تقرير أسبوعي واجتماع مراجعة شهري', 'Weekly report & monthly review meeting'],
            ]),
            'body' => $ar('<p>تجربة متكاملة من الهوية إلى الصدارة. نصمم الهوية، نصوّر المكان والمنتجات، نبني منصة فاخرة بالحجز والكتالوج، ونتولى السوشيال ميديا بالكامل بفريق مخصص وتصوير شهري وحملات إعلانية.</p><h3>النتيجة</h3><p>علامة متسقة ومهيمنة في كل نقطة تماس: الموقع، جوجل، إنستغرام، تيك توك، وسناب شات.</p>', '<p>An end-to-end experience from identity to leadership. We design the identity, shoot the venue and products, build a luxury platform with booking and catalogue, and run social media entirely with a dedicated team, monthly shoots and ad campaigns.</p><h3>The result</h3><p>A consistent, leading brand at every touchpoint: website, Google, Instagram, TikTok and Snapchat.</p>'),
            'meta_title' => $ar('باقة بريميوم: هوية وموقع وسوشيال ميديا في قطر | أثر', 'Premium package: identity, website & social media in Qatar | Athar'),
            'meta_description' => $ar('الباقة الشاملة للعلامات الفاخرة في قطر: هوية بصرية ومنصة متكاملة بـ 11,000 ر.ق + إدارة سوشيال ميديا كاملة مع تصوير شهري بـ 3,900 ر.ق شهريًا.', 'The complete package for luxury brands in Qatar: brand identity and full platform for QAR 11,000 + full social media management with monthly shoots for QAR 3,900 a month.'),
            'meta_keywords' => $ar('وكالة رقمية شاملة قطر, هوية وموقع وسوشيال ميديا الدوحة', 'full-service digital agency qatar, identity website social media doha'),
        ],
    ];
    foreach ($rows as $r) {
        $r += ['is_highlighted' => false, 'badge' => $ar('', ''), 'price_note' => $ar('', ''), 'whatsapp_message' => $ar('', ''), 'cta_text' => $ar('', ''), 'image' => '', 'status' => 'published'];
        Db::from('packages')->insert($r);
    }
    $out('bundle packages seeded (' . count($rows) . ')');

    // Park the single-line packages as drafts (kept for the admin to reuse)
    foreach (['website', 'social'] as $cat) {
        Db::from('packages')->eq('category', $cat)->update(['status' => 'draft']);
    }
    $out('single-line packages set to draft');

    // Point the public page at the bundle line
    $page = Db::from('pages')->eq('slug', 'packages')->first();
    if ($page) {
        $blocks = is_array($page['blocks']) ? $page['blocks'] : [];
        foreach ($blocks as &$blk) {
            if (($blk['type'] ?? '') === 'packages') {
                $blk['data']['category'] = 'bundle';
                $blk['data']['intro'] = $ar('ثلاث باقات مدمجة تجمع الموقع والسوشيال ميديا في عرض واحد: سيلفر، إيليت، وبريميوم. اختر باقتك واطلبها عبر واتساب برسالة جاهزة.', 'Three combined packages that bring website and social media together in one offer: Silver, Elite and Premium. Pick yours and order on WhatsApp with a ready-made message.');
            }
        }
        unset($blk);
        Db::from('pages')->eq('id', $page['id'])->update(['blocks' => $blocks]);
        $out('packages page now shows the combined line');
    }
} else {
    $out('bundle packages already present — skipped');
}
\App\Core\Cache::flush();
