<?php /** $status (campaign) */ $map = ['draft' => 'muted', 'scheduled' => 'status-new', 'sending' => 'warn', 'paused' => 'muted', 'sent' => 'ok', 'cancelled' => 'muted']; ?>
<span class="pill pill--<?= e($map[$status] ?? 'muted') ?>"><?= e(t('mail.status_' . $status)) ?></span>
