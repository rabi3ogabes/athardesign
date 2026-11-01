<?php
/**
 * Combined monthly packages (Silver / Elite / Premium): social media content,
 * and from Elite upwards a business website with domain and email.
 *
 * Idempotent and re-runnable:
 *   - first run: inserts the three bundles, parks the single-line packages as drafts
 *     and points the public Packages page at the bundle line;
 *   - later runs: refreshes the three bundles by slug (prices, items, copy, SEO) and
 *     keeps what the admin may have changed on them (status, image, custom WhatsApp text, CTA).
 *
 *   php database/seed-bundles.php
 */
declare(strict_types=1);
if (!defined('APP_PATH')) require __DIR__ . '/../app/bootstrap.php';

use App\Core\Db;

$ar = fn(string $a, string $e) => ['ar' => $a, 'en' => $e];
$feat = fn(array $rows) => array_map(fn($r) => ['ar' => $r[0], 'en' => $r[1], 'included' => $r[2] ?? true], $rows);
$out = fn(string $m) => print($m . PHP_EOL);

$period = $ar('/ شهريًا', '/ month');
$note = $ar('اشتراك شهري بالريال القطري. ميزانية الإعلانات الممولة غير مشمولة.', 'Monthly plan in Qatari riyal. Paid-ad budget not included.');
$siteDelivery = $ar('المحتوى خلال 5 أيام عمل، والموقع جاهز خلال 3 أسابيع', 'Content within 5 working days, website live within 3 weeks');

$rows = [
    [
        'slug' => 'bundle-silver', 'category' => 'bundle', 'tier' => 'silver', 'sort_order' => 10,
        'name' => $ar('انطلاقة سوشيال', 'Social Launch'),
        'tagline' => $ar(
            'محتوى شهري مصمم بهوية موحدة: 12 منشورًا، فيديوهان، وجلسة تصوير. حضور احترافي على إنستغرام وتيك توك وسناب شات دون أي عناء.',
            'A month of brand-consistent content: 12 posts, 2 videos and a photo session. A professional presence on Instagram, TikTok and Snapchat, without the hassle.'
        ),
        'price' => 1500, 'currency' => 'QAR', 'price_prefix' => $ar('', ''), 'period' => $period, 'price_note' => $note,
        'delivery' => $ar('يبدأ المحتوى خلال 5 أيام عمل', 'Content starts within 5 working days'),
        'features' => $feat([
            ['12 منشورًا مصممًا شهريًا بهوية علامتك', '12 designed posts a month, in your brand identity'],
            ['فيديوهان قصيران (ريلز) شهريًا', '2 short videos (Reels) a month'],
            ['جلسة تصوير احترافية لمدة ساعة', '1-hour professional photo session'],
            ['كتابة المحتوى بالعربية والإنجليزية مع الهاشتاقات', 'Arabic & English captions with hashtags'],
            ['خطة محتوى شهرية وجدولة النشر', 'Monthly content calendar & scheduling'],
            ['تقرير أداء شهري', 'Monthly performance report'],
            ['موقع إلكتروني مع نطاق باسم نشاطك', 'Business website with your own domain', false],
            ['بريد إلكتروني رسمي باسم نشاطك', 'Business email on your domain', false],
        ]),
        'body' => $ar(
            '<p>باقة شهرية لمن يريد حضورًا رقميًا احترافيًا في قطر دون أن يشغل نفسه بالتصميم والكتابة والتصوير. نتولى عنك محتوى حساباتك من الفكرة إلى النشر: 12 منشورًا مصممًا بهوية موحدة، فيديوهان قصيران يجذبان التفاعل، وجلسة تصوير لمدة ساعة تمنح صفحتك صورًا حقيقية لمنتجاتك ومكانك.</p><h3>ماذا تحصل عليه كل شهر؟</h3><ul><li>خطة محتوى واضحة قبل بداية الشهر، مبنية على مواسم السوق القطري ومناسباته.</li><li>تصاميم ونصوص بالعربية والإنجليزية تناسب جمهور الدوحة.</li><li>تقرير شهري يوضح نمو المتابعين والتفاعل وأفضل المنشورات أداءً.</li></ul><h3>لمن هذه الباقة؟</h3><p>المقاهي والمطاعم والعيادات والمتاجر الصغيرة التي تريد البدء بإدارة سوشيال ميديا احترافية في قطر بميزانية شهرية واضحة.</p>',
            '<p>A monthly package for businesses that want a professional digital presence in Qatar without worrying about design, copywriting and photography. We run your accounts from idea to publishing: 12 posts designed in one consistent identity, 2 short videos that drive engagement, and a one-hour photo session that gives your page real pictures of your products and venue.</p><h3>What you get every month</h3><ul><li>A clear content plan before the month starts, built around Qatar\'s seasons and occasions.</li><li>Designs and captions in Arabic and English that speak to the Doha audience.</li><li>A monthly report with follower growth, engagement and your best-performing posts.</li></ul><h3>Who is it for?</h3><p>Cafés, restaurants, clinics and small shops that want to start professional social media management in Qatar on a clear monthly budget.</p>'
        ),
        'meta_title' => $ar('باقة سيلفر: إدارة سوشيال ميديا في قطر بـ 1,500 ر.ق شهريًا | أثر', 'Silver package: social media management in Qatar for QAR 1,500 a month | Athar'),
        'meta_description' => $ar(
            '12 منشورًا مصممًا وفيديوهان وجلسة تصوير شهريًا بـ 1,500 ر.ق. باقة سيلفر لإدارة حسابات إنستغرام وتيك توك وسناب شات للمطاعم والمتاجر في الدوحة.',
            '12 designed posts, 2 videos and a photo session every month for QAR 1,500. The Silver social media management package for restaurants and shops in Doha.'
        ),
        'meta_keywords' => $ar('إدارة سوشيال ميديا قطر, سعر إدارة انستقرام الدوحة, باقة سوشيال ميديا شهرية', 'social media management qatar, instagram management price doha, monthly social media package'),
    ],
    [
        'slug' => 'bundle-elite', 'category' => 'bundle', 'tier' => 'elite', 'sort_order' => 20, 'is_highlighted' => true,
        'name' => $ar('نمو متكامل', 'Growth & Website'),
        'tagline' => $ar(
            '18 منشورًا و4 فيديوهات وجلسة تصوير ساعتين شهريًا، مع موقع إلكتروني احترافي ونطاق لسنة كاملة وبريدين رسميين. الخيار الأكثر طلبًا.',
            '18 posts, 4 videos and a 2-hour photo session every month, plus a professional website with a one-year domain and two business emails. The most requested option.'
        ),
        'badge' => $ar('الأكثر طلبًا', 'Most popular'),
        'price' => 2500, 'currency' => 'QAR', 'price_prefix' => $ar('', ''), 'period' => $period, 'price_note' => $note,
        'delivery' => $siteDelivery,
        'features' => $feat([
            ['18 منشورًا مصممًا شهريًا بهوية علامتك', '18 designed posts a month, in your brand identity'],
            ['4 فيديوهات قصيرة (ريلز) شهريًا', '4 short videos (Reels) a month'],
            ['جلسة تصوير احترافية لمدة ساعتين', '2-hour professional photo session'],
            ['موقع إلكتروني ثنائي اللغة متوافق مع الجوال', 'Bilingual, mobile-first business website'],
            ['نطاق واستضافة وشهادة أمان SSL للسنة الأولى', 'Domain, hosting & SSL for the first year'],
            ['حسابا بريد إلكتروني رسميان باسم نشاطك', '2 business email accounts on your domain'],
            ['زر واتساب ونموذج تواصل وإعداد SEO أساسي', 'WhatsApp button, contact form & basic SEO setup'],
            ['كتابة المحتوى بالعربية والإنجليزية مع الهاشتاقات', 'Arabic & English captions with hashtags'],
            ['خطة محتوى شهرية وجدولة النشر', 'Monthly content calendar & scheduling'],
            ['تقرير أداء شهري', 'Monthly performance report'],
        ]),
        'body' => $ar(
            '<p>الباقة التي يختارها معظم عملائنا في قطر: حسابات سوشيال ميديا نشطة بمحتوى أكثر وفيديوهات أكثر، وموقع إلكتروني احترافي يظهر في جوجل ويحوّل الزائر إلى عميل عبر واتساب. فريق واحد يدير هويتك على كل القنوات.</p><h3>ماذا يميزها؟</h3><ul><li>موقع تعريفي أنيق بالعربية والإنجليزية مع نطاق باسم نشاطك واستضافة لسنة كاملة.</li><li>بريد إلكتروني رسمي باسم نطاقك يمنح تعاملاتك مظهرًا موثوقًا.</li><li>جلسة تصوير ساعتين شهريًا تغذي الموقع والسوشيال ميديا بصور حقيقية.</li><li>محتوى السوشيال ميديا يقود الزوار إلى الموقع، والموقع يحوّلهم إلى عملاء.</li></ul><h3>لمن هذه الباقة؟</h3><p>المطاعم والعيادات والشركات الخدمية في الدوحة التي تريد موقعًا وحضورًا اجتماعيًا متكاملًا بسعر شهري واحد.</p>',
            '<p>The package most of our clients in Qatar choose: active social accounts with more content and more video, plus a professional website that ranks on Google and turns visitors into clients through WhatsApp. One team runs your identity across every channel.</p><h3>What makes it special?</h3><ul><li>An elegant bilingual website with a domain in your business name and hosting for a full year.</li><li>Business email on your own domain, so every message looks trustworthy.</li><li>A 2-hour photo session each month that feeds both the website and social media with real pictures.</li><li>Social content drives visitors to the website, and the website turns them into clients.</li></ul><h3>Who is it for?</h3><p>Restaurants, clinics and service companies in Doha that want a website and a complete social presence for one monthly price.</p>'
        ),
        'meta_title' => $ar('باقة إيليت: سوشيال ميديا + موقع إلكتروني في قطر بـ 2,500 ر.ق شهريًا | أثر', 'Elite package: social media + website in Qatar for QAR 2,500 a month | Athar'),
        'meta_description' => $ar(
            '18 منشورًا و4 فيديوهات وجلسة تصوير شهريًا مع موقع إلكتروني ونطاق لسنة وبريدين رسميين بـ 2,500 ر.ق شهريًا. الباقة الأكثر طلبًا في الدوحة.',
            '18 posts, 4 videos and a photo session every month, plus a website with a one-year domain and 2 business emails, for QAR 2,500 a month. The most popular package in Doha.'
        ),
        'meta_keywords' => $ar('باقة موقع وسوشيال ميديا قطر, تصميم موقع وإدارة انستقرام الدوحة, سعر موقع إلكتروني قطر', 'website and social media package qatar, web design and instagram management doha, website price qatar'),
    ],
    [
        'slug' => 'bundle-premium', 'category' => 'bundle', 'tier' => 'premium', 'sort_order' => 30,
        'name' => $ar('صدارة رقمية', 'Digital Leadership'),
        'tagline' => $ar(
            '26 منشورًا و4 فيديوهات وجلسة تصوير ساعتين شهريًا، مع موقع احترافي ونطاق لسنة و10 حسابات بريد رسمية. للعلامات التي تريد الصدارة في قطر.',
            '26 posts, 4 videos and a 2-hour photo session every month, plus a professional website with a one-year domain and 10 business emails. For brands that want to lead in Qatar.'
        ),
        'price' => 3500, 'currency' => 'QAR', 'price_prefix' => $ar('', ''), 'period' => $period, 'price_note' => $note,
        'delivery' => $siteDelivery,
        'features' => $feat([
            ['26 منشورًا مصممًا شهريًا (محتوى شبه يومي)', '26 designed posts a month (near-daily content)'],
            ['4 فيديوهات قصيرة (ريلز) شهريًا', '4 short videos (Reels) a month'],
            ['جلسة تصوير احترافية لمدة ساعتين', '2-hour professional photo session'],
            ['موقع إلكتروني ثنائي اللغة متوافق مع الجوال', 'Bilingual, mobile-first business website'],
            ['نطاق واستضافة وشهادة أمان SSL للسنة الأولى', 'Domain, hosting & SSL for the first year'],
            ['10 حسابات بريد إلكتروني رسمية لفريقك', '10 business email accounts for your team'],
            ['زر واتساب ونموذج تواصل وإعداد SEO أساسي', 'WhatsApp button, contact form & basic SEO setup'],
            ['كتابة المحتوى بالعربية والإنجليزية مع الهاشتاقات', 'Arabic & English captions with hashtags'],
            ['خطة محتوى شهرية وجدولة النشر', 'Monthly content calendar & scheduling'],
            ['تقرير أداء شهري مفصّل', 'Detailed monthly performance report'],
            ['أولوية في الرد والدعم عبر واتساب', 'Priority support on WhatsApp'],
        ]),
        'body' => $ar(
            '<p>أعلى مستوى من الحضور الرقمي: محتوى شبه يومي يبقي علامتك أمام جمهورها طوال الشهر، وموقع إلكتروني احترافي بنطاق باسمك، وبريد رسمي لعشرة أعضاء من فريقك. صُممت للعلامات والشركات التي تريد أن تتصدر في قطر.</p><h3>ماذا يميزها؟</h3><ul><li>26 منشورًا شهريًا تغطي المنتجات والعروض والمناسبات القطرية دون انقطاع.</li><li>4 فيديوهات قصيرة شهريًا مصممة للوصول والمشاركة على إنستغرام وتيك توك.</li><li>10 حسابات بريد رسمية تمنح كل قسم في شركتك عنوانًا موثوقًا.</li><li>تقرير مفصّل وأولوية في الرد والدعم.</li></ul><h3>لمن هذه الباقة؟</h3><p>الشركات والعلامات متعددة الفروع، والمطاعم والعيادات ذات الفرق الأكبر، وكل نشاط يريد حضورًا شبه يومي على السوشيال ميديا مع موقع يليق به.</p>',
            '<p>The highest level of digital presence: near-daily content that keeps your brand in front of its audience all month, a professional website on your own domain, and business email for ten members of your team. Built for brands and companies that want to lead in Qatar.</p><h3>What makes it special?</h3><ul><li>26 posts a month covering products, offers and Qatari occasions without a gap.</li><li>4 short videos a month made for reach and shares on Instagram and TikTok.</li><li>10 business email accounts, so every department has a trusted address.</li><li>A detailed report and priority support.</li></ul><h3>Who is it for?</h3><p>Multi-branch companies and brands, restaurants and clinics with larger teams, and any business that wants near-daily social media with a website to match.</p>'
        ),
        'meta_title' => $ar('باقة بريميوم: محتوى شبه يومي + موقع إلكتروني في قطر بـ 3,500 ر.ق شهريًا | أثر', 'Premium package: near-daily content + website in Qatar for QAR 3,500 a month | Athar'),
        'meta_description' => $ar(
            '26 منشورًا و4 فيديوهات وجلسة تصوير شهريًا مع موقع إلكتروني ونطاق لسنة و10 حسابات بريد رسمية بـ 3,500 ر.ق شهريًا. الباقة الشاملة للعلامات في الدوحة.',
            '26 posts, 4 videos and a photo session every month, plus a website with a one-year domain and 10 business emails, for QAR 3,500 a month. The complete package for brands in Doha.'
        ),
        'meta_keywords' => $ar('وكالة سوشيال ميديا قطر, باقة شاملة موقع وسوشيال ميديا الدوحة, إدارة حسابات انستقرام وتيك توك قطر', 'social media agency qatar, complete website and social media package doha, instagram tiktok management qatar'),
    ],
];

$defaults = ['is_highlighted' => false, 'badge' => $ar('', ''), 'price_note' => $ar('', ''), 'whatsapp_message' => $ar('', ''), 'cta_text' => $ar('', ''), 'image' => '', 'status' => 'published'];
// fields the admin may have changed by hand: never overwritten on a refresh
$adminOwned = ['status', 'image', 'whatsapp_message', 'cta_text'];

$existing = [];
foreach (Db::from('packages')->eq('category', 'bundle')->get() as $row) {
    $existing[$row['slug']] = $row;
}
$fresh = $existing === [];
$inserted = $updated = 0;
foreach ($rows as $r) {
    $r += $defaults;
    if (isset($existing[$r['slug']])) {
        foreach ($adminOwned as $k) unset($r[$k]);
        Db::from('packages')->eq('id', $existing[$r['slug']]['id'])->update($r);
        $updated++;
    } else {
        Db::from('packages')->insert($r);
        $inserted++;
    }
}
$out("bundle packages: {$inserted} inserted, {$updated} refreshed");

// the Packages page: point it at the bundle line (first run), refresh the intro while it is still ours
$introOld = [
    'ثلاث باقات مدمجة تجمع الموقع والسوشيال ميديا في عرض واحد: سيلفر، إيليت، وبريميوم. اختر باقتك واطلبها عبر واتساب برسالة جاهزة.',
    'Three combined packages that bring website and social media together in one offer: Silver, Elite and Premium. Pick yours and order on WhatsApp with a ready-made message.',
];
$introNew = $ar(
    'ثلاث باقات شهرية بسعر واحد واضح: سيلفر للمحتوى، وإيليت وبريميوم مع موقع إلكتروني ونطاق لسنة وبريد رسمي. اختر باقتك واطلبها عبر واتساب برسالة جاهزة.',
    'Three monthly packages with one clear price: Silver for content, Elite and Premium with a website, a one-year domain and business email. Pick yours and order on WhatsApp with a ready-made message.'
);
$page = Db::from('pages')->eq('slug', 'packages')->first();
if ($page) {
    $blocks = is_array($page['blocks']) ? $page['blocks'] : [];
    $changed = false;
    foreach ($blocks as &$blk) {
        if (($blk['type'] ?? '') !== 'packages') continue;
        if ($fresh) {
            $blk['data']['category'] = 'bundle';
            $blk['data']['intro'] = $introNew;
            $changed = true;
        } elseif (in_array((string)($blk['data']['intro']['ar'] ?? ''), $introOld, true) || in_array((string)($blk['data']['intro']['en'] ?? ''), $introOld, true) || empty($blk['data']['intro'])) {
            $blk['data']['intro'] = $introNew;
            $changed = true;
        }
    }
    unset($blk);
    if ($changed) {
        Db::from('pages')->eq('id', $page['id'])->update(['blocks' => $blocks]);
        $out('packages page intro updated');
    }
}

if ($fresh) {
    // park the single-line packages as drafts (kept for the admin to reuse)
    foreach (['website', 'social'] as $cat) {
        Db::from('packages')->eq('category', $cat)->update(['status' => 'draft']);
    }
    $out('single-line packages set to draft');
}
\App\Core\Cache::flush();
