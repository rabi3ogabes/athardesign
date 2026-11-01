<?php
declare(strict_types=1);

/**
 * Seeds the starter content (pages, services, categories, 
 * draft testimonials, SEO tasks). Safe to re-run: skips tables that already have rows.
 *
 *   php database/seed.php
 */

if (!defined('APP_PATH')) require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Cache;
use App\Core\Db;

$ar = fn(string $a, string $e) => ['ar' => $a, 'en' => $e];
$count = fn(string $t) => Db::from($t)->count();
$out = fn(string $m) => print($m . PHP_EOL);

// ---------------------------------------------------------------- settings
if ($count('settings') === 0) {
    $rows = [];
    foreach (App\Models\Settings::groups() as $group => $keys) {
        foreach ($keys as $k) {
            $rows[] = ['key' => $k, 'value' => App\Models\Settings::defaults()[$k], 'group' => $group];
        }
    }
    Db::from('settings')->upsert($rows, 'key');
    $out('settings seeded');
}

// ---------------------------------------------------------------- services
if ($count('services') === 0) {
    $services = [
        ['web-design', 'layout', 'تصميم وتطوير المواقع', 'Website design & development',
            'مواقع فاخرة سريعة التحميل تعكس هوية علامتك وتحوّل الزائر إلى عميل.',
            'Luxury, fast-loading websites that reflect your brand and turn visitors into clients.',
            '<p>نبني مواقع تُصمَّم من الصفر حول هدف واحد: أن يفهم الزائر قيمتك خلال ثوانٍ ويتواصل معك. كل صفحة مدروسة من ناحية التصميم، سرعة التحميل، وتجربة الاستخدام على الجوال.</p><h3>ما الذي يميّز مواقعنا</h3><ul><li>تصميم حصري يعكس هوية علامتك، لا قوالب جاهزة.</li><li>ثنائية اللغة (عربي/إنجليزي) مع دعم كامل للاتجاه من اليمين إلى اليسار.</li><li>لوحة تحكم بسيطة لتعديل المحتوى بنفسك في أي وقت.</li><li>مهيأة لمحركات البحث منذ اليوم الأول.</li></ul>',
            '<p>We build websites designed around one goal: a visitor understands your value within seconds and reaches out. Every page is considered for design, loading speed and the mobile experience.</p><h3>What makes our websites different</h3><ul><li>Exclusive design that reflects your brand, never a template.</li><li>Bilingual (Arabic / English) with full right-to-left support.</li><li>A simple admin panel so you can edit content yourself at any time.</li><li>Search-engine ready from day one.</li></ul>',
            [['تصميم UI/UX حصري', 'Exclusive UI/UX design'], ['موقع ثنائي اللغة', 'Bilingual website'], ['لوحة تحكم لإدارة المحتوى', 'Content admin panel'], ['استضافة ونطاق وشهادة SSL', 'Hosting, domain & SSL'], ['تحسين السرعة والأداء', 'Speed & performance optimisation'], ['دعم فني بعد الإطلاق', 'Post-launch support']],
            'تصميم مواقع في قطر | أثر', 'Web design in Qatar | Athar',
            'شركة تصميم مواقع في الدوحة: مواقع فاخرة ثنائية اللغة للمطاعم والشركات مع لوحة تحكم وتحسين لمحركات البحث.',
            'Website design company in Doha: luxury bilingual websites for restaurants and companies with an admin panel and built-in SEO.',
            'تصميم مواقع قطر, تصميم موقع الدوحة, شركة تصميم مواقع', 'web design qatar, website design doha, web development qatar'],
        ['seo', 'magnifying-glass', 'تحسين محركات البحث (SEO)', 'Search engine optimisation (SEO)',
            'نجعل عملاءك يجدونك على جوجل عندما يبحثون عن خدماتك في قطر.',
            'We make sure clients find you on Google when they search for your services in Qatar.',
            '<p>الظهور في الصفحة الأولى من جوجل ليس صدفة. نعمل على تحليل الكلمات المفتاحية التي يبحث عنها عملاؤك في قطر، ونحسّن موقعك تقنيًا ومحتواه ليتصدر النتائج، مع تقارير شهرية واضحة.</p><h3>كيف نعمل</h3><ul><li>تدقيق تقني شامل للموقع وإصلاح المشاكل.</li><li>بحث الكلمات المفتاحية باللغتين العربية والإنجليزية.</li><li>تحسين الصفحات والمحتوى والصور.</li><li>تحسين ملف Google Business للظهور في الخرائط.</li><li>متابعة شهرية للترتيب والزيارات.</li></ul>',
            '<p>Reaching the first page of Google is no accident. We research the keywords your clients in Qatar actually search for, optimise your website technically and editorially, and send clear monthly reports.</p><h3>How we work</h3><ul><li>Full technical audit and fixes.</li><li>Arabic and English keyword research.</li><li>On-page, content and image optimisation.</li><li>Google Business Profile optimisation for Maps visibility.</li><li>Monthly ranking and traffic follow-up.</li></ul>',
            [['تدقيق تقني شامل', 'Technical SEO audit'], ['بحث الكلمات المفتاحية', 'Keyword research'], ['تحسين الصفحات والمحتوى', 'On-page & content optimisation'], ['SEO محلي وخرائط جوجل', 'Local SEO & Google Maps'], ['تقارير شهرية', 'Monthly reports']],
            'تحسين محركات البحث في قطر | أثر', 'SEO services in Qatar | Athar',
            'خدمات SEO في قطر: تصدّر نتائج جوجل بالعربية والإنجليزية مع تدقيق تقني وتحسين محلي وتقارير شهرية.',
            'SEO services in Qatar: rank on Google in Arabic and English with technical audits, local SEO and monthly reporting.',
            'تحسين محركات البحث قطر, SEO قطر, شركة سيو الدوحة', 'seo qatar, seo company doha, local seo qatar'],
        ['social-media-management', 'instagram-logo', 'إدارة صفحات التواصل الاجتماعي', 'Social media management',
            'نُدير حساباتك على إنستغرام وتيك توك وسناب شات بخطة محتوى شهرية ونشر منتظم.',
            'We run your Instagram, TikTok and Snapchat with a monthly content plan and consistent publishing.',
            '<p>حضور قوي على السوشيال ميديا يحتاج إلى استمرارية وهوية واضحة. نضع لك خطة محتوى شهرية، ننشر بانتظام، نتفاعل مع جمهورك، ونقيس النتائج لنطوّر الأداء شهرًا بعد شهر.</p><ul><li>خطة محتوى شهرية مع تقويم نشر.</li><li>كتابة المحتوى بالعربية والإنجليزية.</li><li>إدارة التعليقات والرسائل.</li><li>حملات إعلانية ممولة عند الحاجة.</li><li>تقرير أداء شهري.</li></ul>',
            '<p>A strong social presence needs consistency and a clear identity. We prepare a monthly content plan, publish regularly, engage with your audience and measure results to improve performance month after month.</p><ul><li>Monthly content plan with a publishing calendar.</li><li>Arabic and English copywriting.</li><li>Community management (comments and messages).</li><li>Paid campaigns when needed.</li><li>Monthly performance report.</li></ul>',
            [['خطة محتوى شهرية', 'Monthly content plan'], ['نشر منتظم', 'Consistent publishing'], ['إدارة التعليقات والرسائل', 'Community management'], ['إعلانات ممولة', 'Paid campaigns'], ['تقرير أداء', 'Performance reporting']],
            'إدارة سوشيال ميديا في قطر | أثر', 'Social media management in Qatar | Athar',
            'إدارة حسابات التواصل الاجتماعي للمطاعم والشركات في قطر: خطة محتوى، نشر منتظم، إعلانات وتقارير.',
            'Social media management for restaurants and companies in Qatar: content plans, consistent publishing, ads and reporting.',
            'إدارة سوشيال ميديا قطر, إدارة حسابات انستقرام الدوحة', 'social media management qatar, instagram management doha'],
        ['content-design', 'palette', 'تصميم منشورات السوشيال ميديا', 'Social media content design',
            'منشورات وقصص وريلز بتصميم فاخر يوقف التمرير ويعبّر عن علامتك.',
            'Posts, stories and reels with a luxury look that stops the scroll and speaks your brand.',
            '<p>التصميم هو أول ما يراه جمهورك. نصمّم منشورات، قصص، وريلز بهوية بصرية موحدة ومستوى فاخر يليق بعلامتك، مع قوالب جاهزة لفريقك إن رغبت.</p><ul><li>هوية بصرية موحدة للحسابات.</li><li>منشورات ثابتة ومتحركة (Motion).</li><li>تصميم القصص والهايلايت.</li><li>قوالب قابلة لإعادة الاستخدام.</li></ul>',
            '<p>Design is the first thing your audience sees. We create posts, stories and reels with a consistent visual identity and a premium standard worthy of your brand, with reusable templates for your team if you wish.</p><ul><li>Unified visual identity across accounts.</li><li>Static and animated (motion) posts.</li><li>Story and highlight design.</li><li>Reusable templates.</li></ul>',
            [['منشورات ثابتة ومتحركة', 'Static & motion posts'], ['تصميم القصص والهايلايت', 'Stories & highlights'], ['هوية بصرية موحدة', 'Unified visual identity'], ['قوالب جاهزة', 'Ready templates']],
            'تصميم منشورات سوشيال ميديا في قطر | أثر', 'Social media post design in Qatar | Athar',
            'تصميم منشورات وقصص وريلز فاخرة للمطاعم والشركات في قطر بهوية بصرية موحدة.',
            'Luxury social media post, story and reel design for restaurants and companies in Qatar.',
            'تصميم منشورات انستقرام قطر, تصميم سوشيال ميديا الدوحة', 'social media design qatar, instagram post design doha'],
        ['photography', 'camera', 'التصوير الفوتوغرافي والفيديو', 'Photography & video',
            'جلسات تصوير احترافية للمطاعم والأطباق والمنتجات والفرق في الدوحة.',
            'Professional shoots for restaurants, dishes, products and teams in Doha.',
            '<p>الصورة تبيع قبل الكلمة. نصوّر أطباقك، منتجاتك، فريقك ومساحتك بإضاءة واتجاه فني يُبرز جودة ما تقدّمه، مع مونتاج ريلز قصيرة جاهزة للنشر.</p><ul><li>تصوير أطباق ومنتجات.</li><li>تصوير الفريق والمكان.</li><li>فيديو وريلز قصيرة.</li><li>تعديل وتصحيح ألوان احترافي.</li></ul>',
            '<p>A photo sells before a word. We shoot your dishes, products, team and space with lighting and art direction that show the quality of what you offer, plus short reels edited and ready to post.</p><ul><li>Food and product photography.</li><li>Team and venue photography.</li><li>Short-form video and reels.</li><li>Professional retouching and colour grading.</li></ul>',
            [['تصوير أطباق ومنتجات', 'Food & product photography'], ['تصوير الفريق والمكان', 'Team & venue shoots'], ['ريلز وفيديو قصير', 'Reels & short video'], ['تعديل وتصحيح ألوان', 'Retouching & colour grading']],
            'تصوير مطاعم ومنتجات في الدوحة | أثر', 'Restaurant & product photography in Doha | Athar',
            'تصوير فوتوغرافي وفيديو احترافي للمطاعم والشركات في قطر: أطباق، منتجات، فرق عمل وريلز.',
            'Professional photography and video for restaurants and companies in Qatar: dishes, products, teams and reels.',
            'تصوير مطاعم الدوحة, مصور منتجات قطر, تصوير أطباق', 'restaurant photography doha, food photographer qatar, product photography qatar'],
        ['brand-identity', 'pen-nib', 'الهوية البصرية', 'Brand identity',
            'شعار وهوية بصرية متكاملة تُميّز علامتك في السوق القطري.',
            'A logo and complete visual identity that sets your brand apart in the Qatari market.',
            '<p>هويتك البصرية هي أثرك الدائم. نصمّم شعارًا ونظام ألوان وخطوط وتطبيقات متكاملة (قوائم، بطاقات، تغليف، لافتات) تمنح علامتك حضورًا متناسقًا في كل نقطة تماس.</p><ul><li>تصميم الشعار بعدة مفاهيم.</li><li>دليل الهوية البصرية.</li><li>تطبيقات مطبوعة ورقمية.</li></ul>',
            '<p>Your visual identity is your lasting mark. We design a logo, colour and typography system and complete applications (menus, cards, packaging, signage) that give your brand a consistent presence at every touchpoint.</p><ul><li>Logo design with several concepts.</li><li>Brand guidelines.</li><li>Print and digital applications.</li></ul>',
            [['تصميم الشعار', 'Logo design'], ['دليل الهوية', 'Brand guidelines'], ['تطبيقات مطبوعة ورقمية', 'Print & digital applications']],
            'تصميم هوية بصرية في قطر | أثر', 'Brand identity design in Qatar | Athar',
            'تصميم شعار وهوية بصرية متكاملة للمطاعم والشركات في قطر مع دليل هوية وتطبيقات.',
            'Logo and complete brand identity design for restaurants and companies in Qatar, with guidelines and applications.',
            'تصميم شعار قطر, هوية بصرية الدوحة', 'logo design qatar, brand identity doha'],
    ];
    $rows = [];
    foreach ($services as $i => $s) {
        $rows[] = [
            'slug' => $s[0], 'icon' => $s[1], 'title' => $ar($s[2], $s[3]), 'excerpt' => $ar($s[4], $s[5]), 'body' => $ar($s[6], $s[7]),
            'features' => array_map(fn($f) => $ar($f[0], $f[1]), $s[8]),
            'meta_title' => $ar($s[9], $s[10]), 'meta_description' => $ar($s[11], $s[12]), 'meta_keywords' => $ar($s[13], $s[14]),
            'image' => '', 'sort_order' => ($i + 1) * 10, 'is_featured' => true, 'status' => 'published',
        ];
    }
    Db::from('services')->insert($rows);
    $out('services seeded');
}

// ---------------------------------------------------------------- categories + projects
if ($count('project_categories') === 0) {
    Db::from('project_categories')->insert([
        ['slug' => 'restaurants', 'name' => $ar('مطاعم ومقاهي', 'Restaurants & cafés'), 'sort_order' => 10],
        ['slug' => 'companies', 'name' => $ar('شركات', 'Companies'), 'sort_order' => 20],
        ['slug' => 'brands', 'name' => $ar('علامات تجارية', 'Brands'), 'sort_order' => 30],
    ]);
    $out('categories seeded');
}

// ---------------------------------------------------------------- testimonials (drafts until edited)
if ($count('testimonials') === 0) {
    Db::from('testimonials')->insert([
        ['author_name' => 'اسم العميل', 'author_role' => $ar('مالك مطعم', 'Restaurant owner'), 'company' => '', 'quote' => $ar('استبدل هذا النص برأي عميل حقيقي من لوحة التحكم، ثم غيّر الحالة إلى "منشور".', 'Replace this text with a real client quote from the admin panel, then change the status to "Published".'), 'rating' => 5, 'sort_order' => 10, 'status' => 'draft'],
        ['author_name' => 'اسم العميل', 'author_role' => $ar('مدير تسويق', 'Marketing manager'), 'company' => '', 'quote' => $ar('رأي تجريبي ثانٍ. يظهر هذا المكوّن فقط عندما توجد آراء منشورة.', 'Second sample quote. The testimonials block only appears when published quotes exist.'), 'rating' => 5, 'sort_order' => 20, 'status' => 'draft'],
    ]);
    $out('testimonials seeded');
}

// ---------------------------------------------------------------- pages
if ($count('pages') === 0) {
    $blk = fn(string $type, array $data, string $theme = 'ink', string $anchor = '') => ['type' => $type, 'anchor' => $anchor, 'theme' => $theme, 'hidden' => false, 'data' => $data];
    $home = [
        $blk('hero', [
            'eyebrow' => $ar('وكالة رقمية في قطر', 'A digital agency in Qatar'),
            'heading' => $ar("نصنع لعلامتك أثرًا\nيبقى في الذاكرة", "We craft a presence\nthat leaves a mark"),
            'subheading' => $ar('تصميم مواقع فاخرة، تحسين محركات البحث، إدارة وتصميم محتوى السوشيال ميديا، وتصوير احترافي للمطاعم والشركات في الدوحة.', 'Luxury web design, SEO, social media management and content design, and professional photography for restaurants and companies in Doha.'),
            'primary_text' => $ar('ابدأ مشروعك', 'Start a project'), 'primary_link' => '/contact',
            'secondary_text' => $ar('شاهد أعمالنا', 'See our work'), 'secondary_link' => '/work',
                        'image_alt' => $ar('أعمال أثر لتصميم الويب', 'Athar Web Design work'),
            'stats' => [['value' => '120+', 'label' => $ar('مشروع منجز', 'Projects delivered')], ['value' => '98%', 'label' => $ar('عملاء يوصون بنا', 'Clients recommend us')], ['value' => '6', 'label' => $ar('خدمات متكاملة', 'Integrated services')]],
        ], 'ink', 'top'),
        $blk('intro', [
            'eyebrow' => $ar('من نحن', 'Who we are'),
            'heading' => $ar('شريكك الرقمي الذي يفهم السوق القطري', 'Your digital partner who understands the Qatari market'),
            'body' => $ar('<p>أثر وكالة متخصصة تجمع بين التصميم الفاخر والنتائج القابلة للقياس. نعمل مع المطاعم، المقاهي، العيادات، والشركات في الدوحة لنبني لها حضورًا رقميًا متكاملًا: موقع يبيع، ظهور في جوجل، وحسابات سوشيال ميديا تعبّر عن علامتها.</p>', '<p>Athar is a specialised agency that pairs luxury design with measurable results. We work with restaurants, cafés, clinics and companies in Doha to build a complete digital presence: a website that sells, visibility on Google, and social accounts that speak the brand.</p>'),
            
            'button_text' => $ar('تعرّف علينا أكثر', 'More about us'), 'button_link' => '/about',
        ], 'ivory', 'about'),
        $blk('packages', [
            'eyebrow' => $ar('الباقات والأسعار', 'Packages & pricing'),
            'heading' => $ar('باقات شهرية بسعر واضح، من المحتوى إلى الموقع', 'Monthly packages with a clear price, from content to website'),
            'intro' => $ar('سيلفر للمحتوى، وإيليت وبريميوم مع موقع إلكتروني ونطاق لسنة وبريد رسمي. وإن أردت أرقامًا مختلفة أو تطبيق جوال، فالباقة على مقاسك تُسعَّر مع فريق المبيعات.', 'Silver for content, Elite and Premium with a website, a one-year domain and business email. Need different numbers or a mobile app? The made-to-measure package is priced with our sales team.'),
            'category' => 'bundle', 'layout' => 'tabs', 'show_custom' => false,
        ], 'ink', 'packages'),
        $blk('services', ['eyebrow' => $ar('خدماتنا', 'Our services'), 'heading' => $ar('كل ما يحتاجه حضورك الرقمي، في مكان واحد', 'Everything your digital presence needs, in one place'), 'intro' => $ar('', ''), 'source' => 'all', 'limit' => 6, 'show_link' => true], 'ink', 'services'),
        $blk('portfolio', ['eyebrow' => $ar('أعمال مختارة', 'Selected work'), 'heading' => $ar('مشاريع تركت أثرًا', 'Projects that left a mark'), 'intro' => $ar('', ''), 'source' => 'featured', 'category' => '', 'limit' => 3, 'layout' => 'bento', 'show_filters' => false, 'show_link' => true], 'ink', 'work'),
        $blk('features', ['eyebrow' => $ar('لماذا أثر', 'Why Athar'), 'heading' => $ar('ثلاثة أسباب يختارنا بها عملاؤنا', 'Three reasons clients choose us'), 'intro' => $ar('', ''), 'items' => [
            ['icon' => 'crown-simple', 'title' => $ar('تصميم بمستوى فاخر', 'Luxury-grade design'), 'text' => $ar('لا نستخدم قوالب جاهزة. كل تفصيلة مصممة لتليق بعلامتك وتميّزها عن المنافسين.', 'No templates. Every detail is designed to suit your brand and set it apart from competitors.')],
            ['icon' => 'trend-up', 'title' => $ar('نتائج قابلة للقياس', 'Measurable results'), 'text' => $ar('نتابع الزيارات، النقرات على واتساب، والترتيب في جوجل، ونرسل لك تقارير واضحة.', 'We track visits, WhatsApp clicks and Google rankings, and send you clear reports.')],
            ['icon' => 'handshake', 'title' => $ar('فريق واحد لكل شيء', 'One team for everything'), 'text' => $ar('موقع، SEO، سوشيال ميديا وتصوير من فريق واحد ينسّق كل شيء بهوية موحدة.', 'Website, SEO, social media and photography from one team that keeps everything consistent.')],
        ]], 'ivory'),
        $blk('process', ['eyebrow' => $ar('كيف نعمل', 'How we work'), 'heading' => $ar('من الفكرة إلى الأثر في أربع خطوات', 'From idea to impact in four steps'), 'intro' => $ar('', ''), 'steps' => [
            ['title' => $ar('استكشاف', 'Discovery'), 'text' => $ar('نفهم نشاطك وجمهورك وأهدافك، وندرس منافسيك في السوق.', 'We understand your business, audience and goals, and study your competitors.')],
            ['title' => $ar('استراتيجية', 'Strategy'), 'text' => $ar('نضع خطة واضحة: الرسالة، القنوات، الكلمات المفتاحية، وجدول التنفيذ.', 'We set a clear plan: message, channels, keywords and a delivery schedule.')],
            ['title' => $ar('تصميم وتنفيذ', 'Design & build'), 'text' => $ar('نصمّم وننفّذ بمستوى فاخر، ونراجع معك كل مرحلة.', 'We design and deliver to a luxury standard, reviewing every stage with you.')],
            ['title' => $ar('إطلاق ومتابعة', 'Launch & follow-up'), 'text' => $ar('نطلق، نقيس، ونحسّن باستمرار لتحقيق نتائج أفضل.', 'We launch, measure and keep improving for better results.')],
        ]], 'ink'),
        $blk('testimonials', ['eyebrow' => $ar('آراء العملاء', 'Testimonials'), 'heading' => $ar('ما يقوله عملاؤنا', 'What our clients say'), 'intro' => $ar('', ''), 'limit' => 6], 'ivory'),
        $blk('faq', ['eyebrow' => $ar('أسئلة شائعة', 'FAQ'), 'heading' => $ar('أسئلة يطرحها عملاؤنا عادةً', 'Questions clients usually ask'), 'intro' => $ar('', ''), 'items' => [
            ['question' => $ar('كم يستغرق تصميم الموقع؟', 'How long does a website take?'), 'answer' => $ar('عادةً بين 3 و6 أسابيع حسب حجم الموقع وجاهزية المحتوى. نرسل لك جدولًا زمنيًا واضحًا قبل البدء.', 'Usually 3 to 6 weeks depending on the size of the website and content readiness. We send a clear timeline before starting.')],
            ['question' => $ar('هل يمكنني تعديل محتوى الموقع بنفسي؟', 'Can I edit the website content myself?'), 'answer' => $ar('نعم. كل مواقعنا تأتي مع لوحة تحكم بسيطة لتعديل النصوص والصور والصفحات دون أي خبرة تقنية.', 'Yes. All our websites come with a simple admin panel to edit text, images and pages without any technical experience.')],
            ['question' => $ar('متى تظهر نتائج تحسين محركات البحث؟', 'When do SEO results appear?'), 'answer' => $ar('التحسينات التقنية تظهر خلال أسابيع، أما التصدر المستقر فيحتاج عادةً من 3 إلى 6 أشهر من العمل المنتظم.', 'Technical improvements show within weeks, while stable top rankings usually take 3 to 6 months of consistent work.')],
            ['question' => $ar('هل تقدمون باقات شهرية للسوشيال ميديا؟', 'Do you offer monthly social media packages?'), 'answer' => $ar('نعم، باقات شهرية تشمل التصميم والنشر والإدارة والتقارير، ويمكن تخصيصها حسب احتياجك.', 'Yes, monthly packages covering design, publishing, management and reporting, customisable to your needs.')],
        ]], 'ink'),
        $blk('cta', ['heading' => $ar('جاهز لصناعة أثرك؟', 'Ready to make your mark?'), 'text' => $ar('أخبرنا عن مشروعك وسنعود إليك بخطة واضحة خلال يوم عمل.', 'Tell us about your project and we will come back with a clear plan within one business day.'), 'button_text' => $ar('ابدأ مشروعك', 'Start a project'), 'button_link' => '/contact', 'show_whatsapp' => true], 'ink', 'start'),
    ];
    $servicesPage = [
        $blk('intro', ['eyebrow' => $ar('خدماتنا', 'Our services'), 'heading' => $ar('حلول رقمية متكاملة للمطاعم والشركات في قطر', 'Integrated digital solutions for restaurants and companies in Qatar'), 'body' => $ar('<p>من الموقع إلى الصورة، ومن جوجل إلى إنستغرام: فريق واحد يبني حضورك الرقمي بهوية موحدة ومستوى فاخر.</p>', '<p>From the website to the photo, and from Google to Instagram: one team builds your digital presence with a unified identity and a luxury standard.</p>'), 'image' => '', 'image_alt' => $ar('', ''), 'button_text' => $ar('', ''), 'button_link' => ''], 'ink'),
        $blk('services', ['eyebrow' => $ar('', ''), 'heading' => $ar('', ''), 'intro' => $ar('', ''), 'source' => 'all', 'limit' => 12, 'show_link' => false], 'ink', 'list'),
        $blk('process', ['eyebrow' => $ar('كيف نعمل', 'How we work'), 'heading' => $ar('منهجية واضحة من أول اجتماع', 'A clear method from the first meeting'), 'intro' => $ar('', ''), 'steps' => [
            ['title' => $ar('استكشاف', 'Discovery'), 'text' => $ar('نفهم نشاطك وجمهورك وأهدافك.', 'We understand your business, audience and goals.')],
            ['title' => $ar('استراتيجية', 'Strategy'), 'text' => $ar('خطة واضحة بجدول تنفيذ.', 'A clear plan with a delivery schedule.')],
            ['title' => $ar('تصميم وتنفيذ', 'Design & build'), 'text' => $ar('تنفيذ فاخر مع مراجعات مشتركة.', 'Luxury delivery with shared reviews.')],
            ['title' => $ar('إطلاق ومتابعة', 'Launch & follow-up'), 'text' => $ar('قياس وتحسين مستمر.', 'Continuous measurement and improvement.')],
        ]], 'ivory'),
        $blk('cta', ['heading' => $ar('لنبدأ بمشروعك', "Let's start with your project"), 'text' => $ar('', ''), 'button_text' => $ar('تواصل معنا', 'Contact us'), 'button_link' => '/contact', 'show_whatsapp' => true], 'ink'),
    ];
    $workPage = [
        $blk('intro', ['eyebrow' => $ar('أعمالنا', 'Our work'), 'heading' => $ar('مشاريع تركت أثرًا لدى عملائنا', 'Projects that left a mark for our clients'), 'body' => $ar('<p>مختارات من مواقع، حملات، وجلسات تصوير نفّذناها لمطاعم وشركات في قطر.</p>', '<p>A selection of websites, campaigns and shoots we delivered for restaurants and companies in Qatar.</p>'), 'image' => '', 'image_alt' => $ar('', ''), 'button_text' => $ar('', ''), 'button_link' => ''], 'ink'),
        $blk('portfolio', ['eyebrow' => $ar('', ''), 'heading' => $ar('', ''), 'intro' => $ar('', ''), 'source' => 'all', 'category' => '', 'limit' => 60, 'layout' => 'grid', 'show_filters' => true, 'show_link' => false], 'ink', 'grid'),
        $blk('cta', ['heading' => $ar('مشروعك القادم يبدأ برسالة', 'Your next project starts with a message'), 'text' => $ar('', ''), 'button_text' => $ar('ابدأ مشروعك', 'Start a project'), 'button_link' => '/contact', 'show_whatsapp' => true], 'ink'),
    ];
    $aboutPage = [
        $blk('intro', ['eyebrow' => $ar('من نحن', 'About us'), 'heading' => $ar('أثر: وكالة قطرية تصنع حضورًا رقميًا يُحدث فرقًا', 'Athar: a Qatari agency building digital presence that makes a difference'), 'body' => $ar('<p>انطلقت أثر من الدوحة بفكرة بسيطة: أصحاب المطاعم والشركات يستحقون حضورًا رقميًا بمستوى ما يقدمونه لعملائهم. نجمع بين التصميم الفاخر، التقنية الحديثة، وفهم عميق للسوق المحلي.</p><p>نؤمن أن الأثر الحقيقي يُقاس بالنتائج: حجوزات أكثر، ظهور أعلى في جوجل، وجمهور متفاعل على السوشيال ميديا.</p>', '<p>Athar started in Doha with a simple idea: restaurant and company owners deserve a digital presence on the level of what they offer their customers. We combine luxury design, modern technology and a deep understanding of the local market.</p><p>We believe real impact is measured in results: more bookings, higher visibility on Google and an engaged social audience.</p>'), 'button_text' => $ar('', ''), 'button_link' => ''], 'ink'),
        $blk('stats', ['heading' => $ar('', ''), 'items' => [['value' => '120+', 'label' => $ar('مشروع منجز', 'Projects delivered')], ['value' => '60+', 'label' => $ar('عميل في قطر', 'Clients in Qatar')], ['value' => '5', 'label' => $ar('سنوات خبرة', 'Years of experience')], ['value' => '98%', 'label' => $ar('نسبة رضا العملاء', 'Client satisfaction')]]], 'ivory'),
        $blk('features', ['eyebrow' => $ar('قيمنا', 'Our values'), 'heading' => $ar('ما نؤمن به في كل مشروع', 'What we believe in every project'), 'intro' => $ar('', ''), 'items' => [
            ['icon' => 'medal', 'title' => $ar('الجودة قبل السرعة', 'Quality before speed'), 'text' => $ar('نسلّم عندما يكون العمل بمستوى يليق باسمك.', 'We deliver when the work is worthy of your name.')],
            ['icon' => 'shield-check', 'title' => $ar('شفافية كاملة', 'Full transparency'), 'text' => $ar('أسعار واضحة، تقارير دورية، ولا مفاجآت.', 'Clear pricing, regular reports and no surprises.')],
            ['icon' => 'rocket-launch', 'title' => $ar('تطوير مستمر', 'Continuous improvement'), 'text' => $ar('نقيس ونحسّن بعد الإطلاق، لا نكتفي بالتسليم.', 'We measure and improve after launch, not just deliver.')],
        ]], 'ink'),
        $blk('cta', ['heading' => $ar('لنتحدث عن مشروعك', "Let's talk about your project"), 'text' => $ar('', ''), 'button_text' => $ar('تواصل معنا', 'Contact us'), 'button_link' => '/contact', 'show_whatsapp' => true], 'ink'),
    ];
    $contactPage = [
        $blk('contact', ['eyebrow' => $ar('تواصل معنا', 'Contact us'), 'heading' => $ar('أخبرنا عن مشروعك', 'Tell us about your project'), 'intro' => $ar('املأ النموذج أو راسلنا على واتساب، وسنعود إليك خلال يوم عمل.', 'Fill in the form or message us on WhatsApp and we will get back to you within one business day.'), 'show_form' => true, 'show_info' => true, 'show_map' => true], 'ink', 'contact'),
        $blk('faq', ['eyebrow' => $ar('قبل أن تتواصل', 'Before you reach out'), 'heading' => $ar('أسئلة سريعة', 'Quick questions'), 'intro' => $ar('', ''), 'items' => [
            ['question' => $ar('ما المعلومات التي تحتاجونها لتقديم عرض سعر؟', 'What do you need to send a quote?'), 'answer' => $ar('نوع نشاطك، الخدمة المطلوبة، وأي مراجع تعجبك. نكمل الباقي في مكالمة قصيرة.', 'Your type of business, the service you need and any references you like. We cover the rest in a short call.')],
            ['question' => $ar('هل تعملون خارج الدوحة؟', 'Do you work outside Doha?'), 'answer' => $ar('نعم، نعمل مع عملاء في كل أنحاء قطر ودول الخليج عن بُعد.', 'Yes, we work with clients across Qatar and the Gulf remotely.')],
        ]], 'ivory'),
    ];
    $notFound = [
        $blk('cta', ['heading' => $ar('ربما تبحث عن إحدى هذه الصفحات', 'Perhaps you were looking for one of these pages'), 'text' => $ar('', ''), 'button_text' => $ar('العودة إلى الرئيسية', 'Back to home'), 'button_link' => '/', 'show_whatsapp' => true], 'ink'),
    ];
    $privacy = [
        $blk('text', ['heading' => $ar('سياسة الخصوصية', 'Privacy policy'), 'body' => $ar('<p>نحترم خصوصيتك. عند استخدام هذا الموقع نجمع بيانات تحليلية أساسية (الصفحات التي تزورها، نوع الجهاز، الدولة) لتحسين تجربتك، ونحتفظ بالبيانات التي ترسلها عبر نموذج التواصل للرد على استفسارك فقط. لا نبيع بياناتك لأي طرف ثالث.</p><p>للاستفسار أو طلب حذف بياناتك، تواصل معنا عبر البريد الإلكتروني أو واتساب.</p>', '<p>We respect your privacy. When you use this website we collect basic analytics (pages visited, device type, country) to improve your experience, and we keep the details you send through the contact form only to answer your enquiry. We never sell your data to third parties.</p><p>To ask a question or request deletion of your data, contact us by email or WhatsApp.</p>'), 'width' => 'narrow'], 'ink'),
    ];
    $pages = [
        ['home', 'home', $ar('الرئيسية', 'Home'), $home, $ar('أثر لتصميم الويب | تصميم مواقع وSEO وسوشيال ميديا في قطر', 'Athar Web Design | Web design, SEO & social media in Qatar'), $ar('أثر: وكالة قطرية لتصميم المواقع الفاخرة، تحسين محركات البحث، إدارة وتصميم محتوى السوشيال ميديا، والتصوير الاحترافي للمطاعم والشركات في الدوحة.', 'Athar: a Qatar agency for luxury website design, SEO, social media management and content design, and professional photography for restaurants and companies in Doha.'), $ar('تصميم مواقع قطر, شركة تصميم مواقع الدوحة, تحسين محركات البحث قطر, إدارة سوشيال ميديا قطر, تصوير مطاعم الدوحة, وكالة رقمية قطر', 'web design qatar, web design company doha, seo qatar, social media management qatar, restaurant photography doha, digital agency qatar'), true, false, 0, true],
        ['services', 'custom', $ar('الخدمات', 'Services'), $servicesPage, $ar('خدماتنا: تصميم مواقع، SEO، سوشيال ميديا وتصوير في قطر', 'Our services: web design, SEO, social media & photography in Qatar'), $ar('تعرّف على خدمات أثر في قطر: تصميم مواقع فاخرة، تحسين محركات البحث، إدارة وتصميم محتوى السوشيال ميديا، التصوير الاحترافي والهوية البصرية.', 'Discover Athar services in Qatar: luxury web design, SEO, social media management and content design, professional photography and brand identity.'), $ar('خدمات تصميم مواقع قطر, خدمات SEO الدوحة, إدارة سوشيال ميديا', 'web design services qatar, seo services doha, social media services qatar'), true, true, 10, true],
        ['work', 'custom', $ar('أعمالنا', 'Our work'), $workPage, $ar('أعمالنا: مشاريع مواقع وسوشيال ميديا وتصوير في قطر', 'Our work: website, social media & photography projects in Qatar'), $ar('استعرض مشاريع أثر لمطاعم وشركات في قطر: مواقع ثنائية اللغة، حملات سوشيال ميديا، وجلسات تصوير احترافية.', 'Browse Athar projects for restaurants and companies in Qatar: bilingual websites, social media campaigns and professional shoots.'), $ar('أعمال تصميم مواقع قطر, بورتفوليو وكالة رقمية الدوحة', 'web design portfolio qatar, digital agency work doha'), true, true, 20, true],
        ['about', 'custom', $ar('من نحن', 'About'), $aboutPage, $ar('من نحن | أثر لتصميم الويب', 'About us | Athar Web Design'), $ar('تعرّف على أثر: وكالة قطرية تجمع التصميم الفاخر والنتائج القابلة للقياس للمطاعم والشركات في الدوحة.', 'Meet Athar: a Qatari agency combining luxury design with measurable results for restaurants and companies in Doha.'), $ar('وكالة تصميم مواقع الدوحة, من نحن أثر', 'web design agency doha, about athar'), true, true, 30, false],
        ['contact', 'custom', $ar('تواصل معنا', 'Contact'), $contactPage, $ar('تواصل معنا | أثر لتصميم الويب', 'Contact us | Athar Web Design'), $ar('تواصل مع أثر في الدوحة لطلب عرض سعر لتصميم موقعك، تحسين محركات البحث، أو إدارة السوشيال ميديا. نرد خلال يوم عمل.', 'Contact Athar in Doha for a quote on your website, SEO or social media management. We reply within one business day.'), $ar('تواصل شركة تصميم مواقع قطر, طلب عرض سعر موقع الدوحة', 'contact web design company qatar, website quote doha'), true, true, 40, true],
        ['privacy', 'custom', $ar('سياسة الخصوصية', 'Privacy policy'), $privacy, $ar('سياسة الخصوصية', 'Privacy policy'), $ar('سياسة الخصوصية لموقع أثر لتصميم الويب.', 'Privacy policy of the Athar Web Design website.'), $ar('سياسة الخصوصية', 'privacy policy'), false, true, 90, false],
        ['404', '404', $ar('الصفحة غير موجودة', 'Page not found'), $notFound, $ar('الصفحة غير موجودة', 'Page not found'), $ar('الصفحة التي تبحث عنها غير موجودة.', 'The page you are looking for does not exist.'), $ar('', ''), false, false, 99, true],
    ];
    $rows = [];
    foreach ($pages as $p) {
        $rows[] = ['slug' => $p[0], 'template' => $p[1], 'title' => $p[2], 'nav_label' => $ar('', ''), 'blocks' => $p[3], 'meta_title' => $p[4], 'meta_description' => $p[5], 'meta_keywords' => $p[6], 'show_in_nav' => $p[7], 'show_in_footer' => $p[8], 'nav_order' => $p[9], 'is_system' => $p[10], 'status' => 'published', 'noindex' => $p[0] === '404'];
    }
    Db::from('pages')->insert($rows);
    $out('pages seeded');
}

// ---------------------------------------------------------------- SEO starter tasks
if ($count('seo_tasks') === 0) {
    Db::from('seo_tasks')->insert([
        ['title' => 'Connect Google Search Console and submit sitemap.xml', 'description' => 'Verify the domain in Search Console, then submit /sitemap.xml. Paste the verification code in Settings → SEO.', 'category' => 'technical', 'priority' => 'high', 'status' => 'todo', 'url' => '/sitemap.xml', 'sort_order' => 10, 'checklist' => [['text' => 'Verify domain', 'done' => false], ['text' => 'Submit sitemap', 'done' => false], ['text' => 'Check coverage report after 7 days', 'done' => false]]],
        ['title' => 'Create / optimise Google Business Profile', 'description' => 'Claim the business on Google Maps with the exact name, address, phone and category "Website designer" / "Marketing agency". Add photos and services.', 'category' => 'local', 'priority' => 'high', 'status' => 'todo', 'sort_order' => 20, 'checklist' => [['text' => 'Claim listing', 'done' => false], ['text' => 'Add 10+ photos', 'done' => false], ['text' => 'Ask 5 clients for reviews', 'done' => false]]],
        ['title' => 'Replace placeholder artwork with real photos', 'description' => 'Upload real project photos (WebP, under 400 KB) and add alt text in both languages for every image.', 'category' => 'content', 'priority' => 'high', 'status' => 'todo', 'page_slug' => 'work', 'sort_order' => 30, 'checklist' => []],
        ['title' => 'Set the WhatsApp number, phone and social links', 'description' => 'Settings → Contact & WhatsApp and Settings → Social media. These links are tracked in the dashboard.', 'category' => 'onpage', 'priority' => 'high', 'status' => 'todo', 'sort_order' => 40, 'checklist' => []],
        ['title' => 'Publish 2 case studies per month', 'description' => 'Each project page should have 300+ words describing challenge, solution and results, with Arabic and English text.', 'category' => 'content', 'priority' => 'medium', 'status' => 'todo', 'sort_order' => 50, 'checklist' => []],
        ['title' => 'Run PageSpeed Insights on the home page', 'description' => 'Target 90+ on mobile. Compress images, keep third-party scripts minimal.', 'category' => 'performance', 'priority' => 'medium', 'status' => 'todo', 'url' => '/', 'sort_order' => 60, 'checklist' => []],
        ['title' => 'List the agency in Qatar business directories', 'description' => 'Consistent name, address and phone (NAP) on Qatar directories and Instagram bio link to the website.', 'category' => 'offpage', 'priority' => 'low', 'status' => 'todo', 'sort_order' => 70, 'checklist' => []],
    ]);
    $out('seo tasks seeded');
}

Cache::flush();
$out('done');

// ---------------------------------------------------------------- packages (Silver / Elite / Premium)
require __DIR__ . '/seed-packages.php';

require __DIR__ . '/seed-bundles.php';
