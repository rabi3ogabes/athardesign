<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/** Data access for the email marketing module (senders, lists, contacts, templates, campaigns, queue). */
final class Mail
{
    private static function uuid(string $id): bool { return (bool)preg_match('/^[0-9a-f\-]{36}$/', $id); }

    // ---------------- senders ----------------
    public static function senders(bool $activeOnly = false): array
    {
        $q = Db::from('mail_senders')->order('sort_order')->order('created_at');
        if ($activeOnly) $q->eq('is_active', true);
        return $q->get();
    }
    public static function sender(string $id): ?array { return self::uuid($id) ? Db::from('mail_senders')->eq('id', $id)->first() : null; }
    public static function createSender(array $d): array { return Db::from('mail_senders')->insert($d)[0] ?? []; }
    public static function updateSender(string $id, array $d): void { Db::from('mail_senders')->eq('id', $id)->update($d); }
    public static function deleteSender(string $id): void { Db::from('mail_senders')->eq('id', $id)->delete(); }
    public static function senderUsage(string $id): array { $r = Db::rpc('mail_sender_usage', ['p_sender' => $id]); return is_array($r) ? $r : ['hour' => 0, 'day' => 0]; }

    // ---------------- lists ----------------
    public static function lists(): array { return Db::from('mail_lists')->order('created_at', false)->get(); }
    public static function list(string $id): ?array { return self::uuid($id) ? Db::from('mail_lists')->eq('id', $id)->first() : null; }
    public static function createList(array $d): array { return Db::from('mail_lists')->insert($d)[0] ?? []; }
    public static function updateList(string $id, array $d): void { Db::from('mail_lists')->eq('id', $id)->update($d); }
    public static function deleteList(string $id): void { Db::from('mail_lists')->eq('id', $id)->delete(); }
    public static function listSummary(string $id): array { $r = Db::rpc('mail_list_summary', ['p_list' => $id]); return is_array($r) ? $r : []; }
    public static function refreshListCount(string $id): void
    {
        $n = Db::from('mail_contacts')->eq('list_id', $id)->count();
        Db::from('mail_lists')->eq('id', $id)->update(['contacts_count' => $n]);
    }

    // ---------------- contacts ----------------
    /** @return array{0:array,1:int} */
    public static function contacts(string $listId, int $page, int $per = 50, string $status = '', string $search = ''): array
    {
        $q = Db::from('mail_contacts')->eq('list_id', $listId)->order('created_at', false)->limit($per)->offset(($page - 1) * $per);
        if ($status !== '') $q->eq('status', $status);
        if ($search !== '') { $s = str_replace(['%', '*', ',', '(', ')', '"'], '', $search); $q->or('email.ilike.*' . $s . '*,name.ilike.*' . $s . '*,company.ilike.*' . $s . '*'); }
        return $q->getWithCount();
    }
    public static function contact(string $id): ?array { return self::uuid($id) ? Db::from('mail_contacts')->eq('id', $id)->first() : null; }
    /** Insert rows ignoring duplicates (list_id,email). */
    public static function importContacts(string $listId, array $rows): int
    {
        $added = 0;
        foreach (array_chunk($rows, 200) as $chunk) {
            $data = array_map(fn($r) => ['list_id' => $listId, 'email' => $r['email'], 'name' => $r['name'] ?: null, 'company' => $r['company'] ?: null], $chunk);
            // duplicates of (list_id, email) are skipped silently
            $added += count(Db::from('mail_contacts')->select('id')->insertIgnore($data));
        }
        self::refreshListCount($listId);
        return $added;
    }
    public static function pendingContacts(string $listId, int $limit = 25): array
    {
        return Db::from('mail_contacts')->eq('list_id', $listId)->eq('status', 'pending')->order('created_at')->limit($limit)->get();
    }
    public static function updateContact(string $id, array $d): void { Db::from('mail_contacts')->eq('id', $id)->update($d); }
    public static function deleteContact(string $id): void { Db::from('mail_contacts')->eq('id', $id)->delete(); }
    public static function deleteContactsByStatus(string $listId, string $status): int
    {
        return count(Db::from('mail_contacts')->eq('list_id', $listId)->eq('status', $status)->delete());
    }
    public static function resetVerification(string $listId): void
    {
        Db::from('mail_contacts')->eq('list_id', $listId)->in('status', ['valid', 'risky', 'invalid'])->update(['status' => 'pending', 'score' => null, 'checks' => null, 'verified_at' => null]);
    }
    public static function allContacts(string $listId): array
    {
        return Db::from('mail_contacts')->select('email,name,company,status,score,created_at')->eq('list_id', $listId)->order('created_at')->limit(20000)->get();
    }

    // ---------------- suppressions ----------------
    public static function suppress(string $email, string $reason): void
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') return;
        Db::from('mail_suppressions')->upsert([['email' => $email, 'reason' => $reason]], 'email');
        try { Db::from('mail_contacts')->eq('email', $email)->update(['status' => $reason === 'bounce' ? 'bounced' : 'unsubscribed', 'unsubscribed_at' => gmdate('c')]); } catch (\Throwable) {}
    }
    public static function suppressions(int $limit = 500): array { return Db::from('mail_suppressions')->order('created_at', false)->limit($limit)->get(); }
    public static function unsuppress(string $email): void { Db::from('mail_suppressions')->eq('email', mb_strtolower($email))->delete(); }

    // ---------------- templates ----------------
    public static function templates(): array { return Db::from('mail_templates')->order('updated_at', false)->get(); }
    public static function template(string $id): ?array { return self::uuid($id) ? Db::from('mail_templates')->eq('id', $id)->first() : null; }
    public static function createTemplate(array $d): array { return Db::from('mail_templates')->insert($d)[0] ?? []; }
    public static function updateTemplate(string $id, array $d): void { Db::from('mail_templates')->eq('id', $id)->update($d); }
    public static function deleteTemplate(string $id): void { Db::from('mail_templates')->eq('id', $id)->delete(); }

    // ---------------- campaigns ----------------
    public static function campaigns(): array
    {
        return Db::from('mail_campaigns')->select('id,name,subject,status,scheduled_at,started_at,finished_at,stats,score,sender_ids,list_ids,created_at,updated_at')->order('created_at', false)->get();
    }
    public static function campaign(string $id): ?array { return self::uuid($id) ? Db::from('mail_campaigns')->eq('id', $id)->first() : null; }
    public static function createCampaign(array $d): array { return Db::from('mail_campaigns')->insert($d)[0] ?? []; }
    public static function updateCampaign(string $id, array $d): void { Db::from('mail_campaigns')->eq('id', $id)->update($d); }
    public static function deleteCampaign(string $id): void { Db::from('mail_campaigns')->eq('id', $id)->delete(); }
    public static function campaignStats(string $id): array { $r = Db::rpc('mail_campaign_stats', ['p_campaign' => $id]); return is_array($r) ? $r : []; }
    public static function dueCampaigns(): array
    {
        return Db::from('mail_campaigns')->select('id,status,scheduled_at')->in('status', ['scheduled', 'sending'])->get();
    }
    public static function buildQueue(string $campaignId, array $listIds, bool $skipRisky, array $senderIds): int
    {
        $r = Db::rpc('mail_build_queue', ['p_campaign' => $campaignId, 'p_lists' => $listIds, 'p_skip_risky' => $skipRisky, 'p_senders' => $senderIds]);
        return (int)$r;
    }
    public static function clearQueue(string $campaignId): void { Db::from('mail_queue')->eq('campaign_id', $campaignId)->delete(); }

    // ---------------- queue ----------------
    public static function nextQueued(string $campaignId, int $limit): array
    {
        return Db::from('mail_queue')->eq('campaign_id', $campaignId)->eq('status', 'queued')->lt('attempts', 3)->order('created_at')->limit($limit)->get();
    }
    public static function updateQueue(string $id, array $d): void { Db::from('mail_queue')->eq('id', $id)->update($d); }
    public static function queueByToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{24}$/', $token)) return null;
        return Db::from('mail_queue')->eq('token', $token)->first();
    }
    /** @return array{0:array,1:int} */
    public static function queueRows(string $campaignId, int $page, int $per = 50, string $status = ''): array
    {
        $q = Db::from('mail_queue')->eq('campaign_id', $campaignId)->order('created_at')->limit($per)->offset(($page - 1) * $per);
        if ($status !== '') $q->eq('status', $status);
        return $q->getWithCount();
    }
    public static function event(string $campaignId, ?string $queueId, string $event, string $url = '', string $ip = '', string $ua = ''): void
    {
        try { Db::from('mail_events')->insert(['campaign_id' => $campaignId, 'queue_id' => $queueId, 'event' => $event, 'url' => mb_substr($url, 0, 500) ?: null, 'ip' => $ip ?: null, 'user_agent' => mb_substr($ua, 0, 300) ?: null]); } catch (\Throwable) {}
    }
    public static function events(string $campaignId, int $limit = 100): array
    {
        return Db::from('mail_events')->eq('campaign_id', $campaignId)->order('created_at', false)->limit($limit)->get();
    }
    public static function overview(): array
    {
        $c = self::campaigns();
        $sent = 0; $opened = 0; $clicked = 0;
        foreach ($c as $x) { $st = is_array($x['stats']) ? $x['stats'] : []; $sent += (int)($st['sent'] ?? 0); $opened += (int)($st['opened'] ?? 0); $clicked += (int)($st['clicked'] ?? 0); }
        return ['campaigns' => count($c), 'sent' => $sent, 'opened' => $opened, 'clicked' => $clicked, 'sending' => count(array_filter($c, fn($x) => in_array($x['status'], ['sending', 'scheduled'], true)))];
    }
}
