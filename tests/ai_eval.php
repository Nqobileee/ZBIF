<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Ai\RegistrationChat;
use App\Ai\Rag;

$failed = 0;

function check(bool $ok, string $msg): void
{
    global $failed;
    echo ($ok ? 'PASS ' : 'FAIL ') . $msg . "\n";
    if (!$ok) {
        $failed++;
    }
}

$start = RegistrationChat::start('registration');
check(!empty($start['session_token']), 'registration chat starts');
$msg = RegistrationChat::message($start['session_token'], 'I am an innovator');
check(isset($msg['messages']), 'persona detection reply');
$faq = Rag::answer('When is ZBIF and where is the venue?');
check(isset($faq['answer']) && $faq['answer'] !== '', 'FAQ rag returns answer');
check(!str_contains($faq['answer'], '—') && !str_contains($faq['answer'], '–'), 'no em-dashes in answer');

echo $failed ? "FAILED\n" : "AI eval OK\n";
exit($failed ? 1 : 0);
