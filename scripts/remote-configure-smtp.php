#!/bin/bash
# Configure live SMTP + seed system_settings, then smoke-test send.
set -euo pipefail
APP_ROOT=/var/www/zbif
cd "$APP_ROOT"

# Keep .env mail block in sync (production template may already have it)
php -r '
require "bootstrap.php";
use App\Support\Settings;
use App\Notify\SmtpMailer;
use App\Support\Env;

Settings::ensureTable();
$pairs = [
  "smtp_host" => Env::get("MAIL_HOST", "smtp.gmail.com"),
  "smtp_port" => Env::get("MAIL_PORT", "587"),
  "smtp_user" => Env::get("MAIL_USERNAME", ""),
  "smtp_pass" => Env::get("MAIL_PASSWORD", ""),
  "smtp_encryption" => Env::get("MAIL_ENCRYPTION", "tls"),
  "mail_from" => Env::get("MAIL_FROM_ADDRESS", ""),
  "mail_from_name" => Env::get("MAIL_FROM_NAME", "ZBIF"),
  "mail_reply_to" => Env::get("MAIL_REPLY_TO", Env::get("MAIL_FROM_ADDRESS", "")),
];
foreach ($pairs as $k => $v) {
  if ($v !== null && $v !== "") Settings::set($k, (string)$v);
}
echo "MAIL_HOST=" . Env::get("MAIL_HOST") . PHP_EOL;
echo "smtp_host(db)=" . Settings::get("smtp_host") . PHP_EOL;
$to = $argv[1] ?? Env::get("MAIL_USERNAME", "");
if ($to === "") { fwrite(STDERR, "No test recipient\n"); exit(1); }
try {
  $r = SmtpMailer::sendWithDiagnostics($to, "ZBIF live SMTP OK", "<p>ZBIF OTP mail path is working.</p>", "ZBIF OTP mail path is working.");
  echo "SMTP_TEST_OK to={$to} detail=" . ($r["detail"] ?? "") . PHP_EOL;
} catch (Throwable $e) {
  echo "SMTP_TEST_FAIL: " . $e->getMessage() . PHP_EOL;
  exit(2);
}
' -- "${1:-}"
