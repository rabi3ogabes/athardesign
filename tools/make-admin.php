<?php
/**
 * Create (or reset) an admin account from the command line.
 *
 *   ATHAR_ADMIN_EMAIL=you@example.com ATHAR_ADMIN_PASSWORD='secret' php tools/make-admin.php [--name="Full name"] [--role=owner|admin|editor] [--lang=ar|en]
 *
 * Credentials are read from the environment so they never land in shell history.
 */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use App\Models\Users;

$email = mb_strtolower(trim((string)getenv('ATHAR_ADMIN_EMAIL')));
$pass  = (string)getenv('ATHAR_ADMIN_PASSWORD');
$opts  = getopt('', ['name::', 'role::', 'lang::']);
$name  = trim((string)($opts['name'] ?? '')) ?: ucfirst(explode('@', $email)[0] ?? 'Admin');
$role  = in_array($opts['role'] ?? '', ['owner', 'admin', 'editor'], true) ? $opts['role'] : 'owner';
$lang  = ($opts['lang'] ?? '') === 'en' ? 'en' : 'ar';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { fwrite(STDERR, "ATHAR_ADMIN_EMAIL is missing or invalid\n"); exit(1); }
if (mb_strlen($pass) < 10) { fwrite(STDERR, "ATHAR_ADMIN_PASSWORD must be at least 10 characters\n"); exit(1); }

$data = ['name' => mb_substr($name, 0, 80), 'password_hash' => password_hash($pass, PASSWORD_DEFAULT), 'role' => $role, 'lang' => $lang, 'is_active' => true];
$existing = Users::findByEmail($email);
if ($existing) {
    Users::update($existing['id'], $data);
    echo "Updated existing admin {$email} (password reset, role {$role}, active)\n";
} else {
    $u = Users::create($data + ['email' => $email]);
    echo "Created admin {$email} (id " . ($u['id'] ?? '?') . ", role {$role})\n";
}
echo "Sign in at " . admin_url('login') . "\n";
