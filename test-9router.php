<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Override base_url agar bisa akses dari host Windows (port di-expose Docker)
config(['services.nine_router.base_url' => 'http://localhost:20128']);

echo "===== TEST 4: executeTool() - eksekusi tool get_featured_kos =====\n";
$context = app(\App\Services\WhatsappBotContextService::class);
$result = $context->executeTool('get_featured_kos', [], null);
echo "Tool result (JSON):\n";
echo $result . "\n\n";

echo "===== TEST 5: Full loop - chat + tool + final reply =====\n";
$router = app(\App\Services\NineRouterService::class);
$tools = $context->getToolsForRole('public');
$messages = [
    ['role' => 'system', 'content' => $context->buildSystemPrompt(null, 'public')],
    ['role' => 'user', 'content' => 'kos rekomendasi apa saja?'],
];

$r1 = $router->chatWithTools($messages, $tools);
echo "Step 1 - LLM tool_call: " . ($r1['tool_calls'][0]['function']['name'] ?? 'none') . "\n";

if (!empty($r1['tool_calls'])) {
    $toolName = $r1['tool_calls'][0]['function']['name'];
    $toolArgs = json_decode($r1['tool_calls'][0]['function']['arguments'] ?? '{}', true) ?? [];
    $toolCallId = $r1['tool_calls'][0]['id'] ?? '';

    $toolResult = $context->executeTool($toolName, $toolArgs, null);

    $messages[] = ['role' => 'assistant', 'content' => null, 'tool_calls' => $r1['tool_calls']];
    $messages[] = ['role' => 'tool', 'tool_call_id' => $toolCallId, 'content' => $toolResult];

    echo "Step 2 - Tool executed, sending result back to LLM...\n";
    $r2 = $router->chatWithTools($messages, $tools);

    echo "Step 3 - Final reply from bot:\n";
    echo "----------------------------------------\n";
    echo ($r2['content'] ?? '(empty)') . "\n";
    echo "----------------------------------------\n";
    echo "Tokens used: " . ($r2['tokens'] ?? 0) . "\n";
    echo "Finish reason: " . ($r2['finish_reason'] ?? 'null') . "\n";
}
