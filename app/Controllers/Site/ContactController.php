<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Analytics;
use App\Core\Csrf;
use App\Core\I18n;
use App\Core\Log;
use App\Core\Mail;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Leads;
use App\Models\Settings;

final class ContactController extends SiteController
{
    public function submit(Request $request): void
    {
        $json = $request->wantsJson();
        $fail = function (string $message, int $status = 422, array $errors = []) use ($json, $request): never {
            if ($json) Response::json(['ok' => false, 'error' => $message, 'errors' => $errors], $status);
            Session::flash('error', $message);
            Session::keepOld($request->all());
            Response::back(I18n::url('/contact'));
        };

        Session::start();
        if (!Csrf::check($request->str('_token'))) {
            $fail(t('site.send_error'), 419);
        }
        // honeypot + time trap
        if ($request->str('website') !== '' || (time() - $request->int('_ts', time())) < 3) {
            if ($json) Response::json(['ok' => true]);
            Response::redirect(I18n::url('/contact') . '?sent=1');
        }

        $data = [
            'name' => $request->str('name'),
            'phone' => $request->str('phone'),
            'email' => $request->str('email'),
            'company' => $request->str('company'),
            'service' => $request->str('service'),
            'message' => $request->str('message'),
        ];
        $v = Validator::make($data, [
            'name' => 'required|min:2|max:80',
            'phone' => 'required|phone|max:30',
            'email' => 'email|max:120',
            'company' => 'max:120',
            'service' => 'max:120',
            'message' => 'max:3000',
        ]);
        if ($v->fails()) {
            $fail($v->first(), 422, $v->errors());
        }
        try {
            if (Leads::recentFromIp($request->ip(), 60) >= 5) {
                $fail(t('site.rate_limited'), 429);
            }
            $lead = Leads::create(array_merge($data, [
                'source' => 'form',
                'page' => mb_substr($request->referer() ?: '/contact', 0, 300),
                'lang' => I18n::lang(),
                'ip' => $request->ip(),
            ]));
            Analytics::click($request, 'form', '/contact', 'contact-form', '/contact', I18n::lang());
        } catch (\Throwable $e) {
            Log::error('Lead save failed: ' . $e->getMessage());
            $fail(t('site.send_error'), 500);
        }

        $notify = trim((string)Settings::get('notify_email', '')) ?: trim((string)Settings::get('email', ''));
        if ($notify !== '') {
            $rows = '';
            foreach ($data as $k => $val) {
                if ($val === '') continue;
                $rows .= '<tr><td style="padding:6px 12px;color:#777;font-weight:600">' . e(t('fields.' . $k)) . '</td><td style="padding:6px 12px">' . nl2br(e($val)) . '</td></tr>';
            }
            $html = '<div style="font-family:Arial,sans-serif;font-size:15px;color:#222"><h2 style="font-weight:600">' . e(t('site.new_lead')) . '</h2><table>' . $rows . '</table><p style="color:#999;font-size:12px">' . e(admin_url('leads/' . ($lead['id'] ?? ''))) . '</p></div>';
            Mail::send($notify, t('site.new_lead') . ' — ' . $data['name'], $html);
        }

        if ($json) Response::json(['ok' => true, 'message' => t('site.sent_text')]);
        Session::flash('success', t('site.sent_text'));
        Response::redirect(I18n::url('/contact') . '?sent=1#contact');
    }
}
