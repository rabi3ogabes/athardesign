<?php
declare(strict_types=1);

/**
 * Re-runnable: shorter, keyword-led meta titles and fuller meta descriptions (2026-11-01).
 * A field is only replaced when it still holds the previous seed text, so anything the admin edited is kept.
 * Run: php database/seed-seo-meta.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Cache;
use App\Core\Db;

$out = fn(string $m) => print($m . "\n");
$changes = [
    ["pages", "services", "meta_title", "خدماتنا: تصميم مواقع، SEO، سوشيال ميديا وتصوير في قطر", "Our services: web design, SEO, social media & photography in Qatar", "خدماتنا: تصميم مواقع وSEO وسوشيال ميديا في قطر | أثر", "Services: Web Design, SEO & Social Media in Qatar | Athar"],
    ["pages", "work", "meta_title", "أعمالنا: مشاريع مواقع وسوشيال ميديا وتصوير في قطر", "Our work: website, social media & photography projects in Qatar", "أعمالنا: مشاريع مواقع وسوشيال ميديا وتصوير في قطر | أثر", "Our Work: Web & Social Media Projects in Qatar | Athar"],
    ["pages", "work", "meta_description", "استعرض مشاريع أثر لمطاعم وشركات في قطر: مواقع ثنائية اللغة، حملات سوشيال ميديا، وجلسات تصوير احترافية.", "Browse Athar projects for restaurants and companies in Qatar: bilingual websites, social media campaigns and professional shoots.", "استعرض مشاريع أثر لمطاعم وشركات في قطر: مواقع ثنائية اللغة، حملات سوشيال ميديا، وجلسات تصوير احترافية بنتائج قابلة للقياس.", "Browse Athar projects for restaurants and companies in Qatar: bilingual websites, social media campaigns and professional photo shoots with measurable results."],
    ["pages", "about", "meta_title", "من نحن | أثر لتصميم الويب", "About us | Athar Web Design", "من نحن: وكالة تصميم مواقع وتسويق رقمي في الدوحة | أثر", "About Us: Web Design & Marketing Agency in Doha | Athar"],
    ["pages", "about", "meta_description", "تعرّف على أثر: وكالة قطرية تجمع التصميم الفاخر والنتائج القابلة للقياس للمطاعم والشركات في الدوحة.", "Meet Athar: a Qatari agency combining luxury design with measurable results for restaurants and companies in Doha.", "تعرّف على أثر: وكالة قطرية في الدوحة تجمع التصميم الفاخر والتقنية الحديثة والنتائج القابلة للقياس للمطاعم والشركات، من الموقع إلى السوشيال ميديا.", "Meet Athar: a Doha-based agency combining luxury design, modern technology and measurable results for restaurants and companies, from website to social media."],
    ["pages", "contact", "meta_title", "تواصل معنا | أثر لتصميم الويب", "Contact us | Athar Web Design", "تواصل معنا: اطلب عرض سعر لموقعك أو سوشيال ميديا | أثر", "Contact Us: Web Design & Social Media Quotes in Qatar | Athar"],
    ["pages", "contact", "meta_description", "تواصل مع أثر في الدوحة لطلب عرض سعر لتصميم موقعك، تحسين محركات البحث، أو إدارة السوشيال ميديا. نرد خلال يوم عمل.", "Contact Athar in Doha for a quote on your website, SEO or social media management. We reply within one business day.", "تواصل مع أثر في الدوحة لطلب عرض سعر لتصميم موقعك، تحسين محركات البحث، أو إدارة السوشيال ميديا. نرد خلال يوم عمل عبر واتساب أو البريد.", "Contact Athar in Doha for a quote on your website, SEO or social media management. We reply within one business day on WhatsApp or by email."],
    ["pages", "privacy", "meta_title", "سياسة الخصوصية", "Privacy policy", "سياسة الخصوصية | أثر لتصميم الويب", "Privacy Policy | Athar Web Design"],
    ["pages", "privacy", "meta_description", "سياسة الخصوصية لموقع أثر لتصميم الويب.", "Privacy policy of the Athar Web Design website.", "كيف يجمع موقع أثر لتصميم الويب بياناتك ويستخدمها ويحميها، وما حقوقك، وكيف تتواصل معنا بخصوص الخصوصية أو حذف بياناتك.", "How the Athar Web Design website collects, uses and protects your data, what your rights are, and how to contact us about privacy or data deletion."],
    ["services", "web-design", "meta_title", "تصميم مواقع في قطر | أثر", "Web design in Qatar | Athar", "شركة تصميم مواقع في قطر: مواقع فاخرة ثنائية اللغة | أثر", "Website Design Company in Qatar | Athar Web Design"],
    ["services", "web-design", "meta_description", "شركة تصميم مواقع في الدوحة: مواقع فاخرة ثنائية اللغة للمطاعم والشركات مع لوحة تحكم وتحسين لمحركات البحث.", "Website design company in Doha: luxury bilingual websites for restaurants and companies with an admin panel and built-in SEO.", "شركة تصميم مواقع في الدوحة: مواقع فاخرة ثنائية اللغة للمطاعم والشركات مع لوحة تحكم سهلة، سرعة عالية وتحسين مدمج لمحركات البحث.", "Website design company in Doha: luxury bilingual websites for restaurants and companies with an easy admin panel, fast loading and built-in SEO."],
    ["services", "seo", "meta_title", "تحسين محركات البحث في قطر | أثر", "SEO services in Qatar | Athar", "شركة تحسين محركات البحث SEO في قطر | أثر", "SEO Company in Qatar: Arabic & English Rankings | Athar"],
    ["services", "seo", "meta_description", "خدمات SEO في قطر: تصدّر نتائج جوجل بالعربية والإنجليزية مع تدقيق تقني وتحسين محلي وتقارير شهرية.", "SEO services in Qatar: rank on Google in Arabic and English with technical audits, local SEO and monthly reporting.", "خدمات SEO في قطر: تصدّر نتائج جوجل بالعربية والإنجليزية مع تدقيق تقني، تحسين محلي على خرائط جوجل، محتوى مستهدف وتقارير شهرية واضحة.", "SEO services in Qatar: rank on Google in Arabic and English with technical audits, local SEO on Google Maps, targeted content and clear monthly reports."],
    ["services", "social-media-management", "meta_title", "إدارة سوشيال ميديا في قطر | أثر", "Social media management in Qatar | Athar", "إدارة سوشيال ميديا للمطاعم والشركات في قطر | أثر", "Social Media Management Company in Qatar | Athar"],
    ["services", "social-media-management", "meta_description", "إدارة حسابات التواصل الاجتماعي للمطاعم والشركات في قطر: خطة محتوى، نشر منتظم، إعلانات وتقارير.", "Social media management for restaurants and companies in Qatar: content plans, consistent publishing, ads and reporting.", "إدارة حسابات التواصل الاجتماعي للمطاعم والشركات في قطر: خطة محتوى شهرية، نشر منتظم، إعلانات ممولة، تفاعل مع الجمهور وتقارير واضحة.", "Social media management for restaurants and companies in Qatar: monthly content plans, consistent publishing, paid ads, community engagement and clear reports."],
    ["services", "content-design", "meta_title", "تصميم منشورات سوشيال ميديا في قطر | أثر", "Social media post design in Qatar | Athar", "تصميم منشورات وريلز سوشيال ميديا في قطر | أثر", "Social Media Post & Reel Design in Qatar | Athar"],
    ["services", "content-design", "meta_description", "تصميم منشورات وقصص وريلز فاخرة للمطاعم والشركات في قطر بهوية بصرية موحدة.", "Luxury social media post, story and reel design for restaurants and companies in Qatar.", "تصميم منشورات وقصص وريلز فاخرة للمطاعم والشركات في قطر بهوية بصرية موحدة، وقوالب جاهزة لكل منصة من إنستغرام إلى تيك توك وسناب شات.", "Luxury social media post, story and reel design for restaurants and companies in Qatar, with a unified brand look and ready templates for every platform."],
    ["services", "photography", "meta_description", "تصوير فوتوغرافي وفيديو احترافي للمطاعم والشركات في قطر: أطباق، منتجات، فرق عمل وريلز.", "Professional photography and video for restaurants and companies in Qatar: dishes, products, teams and reels.", "تصوير فوتوغرافي وفيديو احترافي للمطاعم والشركات في قطر: أطباق، منتجات، فرق عمل وريلز بجودة تليق بعلامتك، مع تعديل احترافي وتسليم سريع.", "Professional photography and video for restaurants and companies in Qatar: dishes, products, teams and reels, with professional editing and fast delivery."],
    ["services", "brand-identity", "meta_title", "تصميم هوية بصرية في قطر | أثر", "Brand identity design in Qatar | Athar", "تصميم شعار وهوية بصرية في قطر | أثر", "Logo & Brand Identity Design in Qatar | Athar"],
    ["services", "brand-identity", "meta_description", "تصميم شعار وهوية بصرية متكاملة للمطاعم والشركات في قطر مع دليل هوية وتطبيقات.", "Logo and complete brand identity design for restaurants and companies in Qatar, with guidelines and applications.", "تصميم شعار وهوية بصرية متكاملة للمطاعم والشركات في قطر: عدة مفاهيم للشعار، دليل هوية، ألوان وخطوط وتطبيقات مطبوعة ورقمية.", "Logo and brand identity design for restaurants and companies in Qatar: several logo concepts, brand guidelines, colours, fonts and print and digital applications."],
];
$done = 0;
foreach ($changes as [$table, $slug, $field, $oldAr, $oldEn, $newAr, $newEn]) {
    $row = Db::from($table)->select('id,' . $field)->eq('slug', $slug)->first();
    if (!$row) continue;
    $cur = is_array($row[$field]) ? $row[$field] : (json_decode((string)$row[$field], true) ?: []);
    $val = ['ar' => ($cur['ar'] ?? '') === $oldAr ? $newAr : ($cur['ar'] ?? ''), 'en' => ($cur['en'] ?? '') === $oldEn ? $newEn : ($cur['en'] ?? '')];
    if ($val !== $cur) { Db::from($table)->eq('id', $row['id'])->update([$field => $val]); $done++; }
}
Cache::flush();
$out("seo meta: $done field(s) updated");
