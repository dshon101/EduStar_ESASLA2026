<?php
error_reporting(0);
ini_set("display_errors", 0);
require_once __DIR__ . "/../config/helpers.php";
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(204); exit; }
if ($_SERVER["REQUEST_METHOD"] !== "POST") { echo json_encode(["ok"=>false,"error"=>"POST only"]); exit; }

$raw  = file_get_contents("php://input");
$body = json_decode($raw ?: "{}", true) ?? [];
$messages     = $body["messages"]  ?? [];
$systemPrompt = $body["system"]    ?? "You are a helpful African educational AI tutor.";
if (empty($messages)) { echo json_encode(["ok"=>false,"error"=>"No messages provided"]); exit; }

$pollinationsMessages = [["role"=>"system","content"=>$systemPrompt]];
foreach ($messages as $m) {
    if (!empty($m["role"]) && !empty($m["content"])) {
        $pollinationsMessages[] = ["role"=>$m["role"],"content"=>$m["content"]];
    }
}

$payload = json_encode(["model"=>"openai","messages"=>$pollinationsMessages,"seed"=>42,"private"=>true]);
$ch = curl_init("https://text.pollinations.ai/openai");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr || $httpCode !== 200) {
    $lastMsg = end($messages)["content"] ?? "Hello";
    $encoded = urlencode($lastMsg);
    $ch2 = curl_init("https://text.pollinations.ai/" . $encoded);
    curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>false]);
    $fallback = curl_exec($ch2);
    curl_close($ch2);
    if ($fallback) { echo json_encode(["ok"=>true,"content"=>[["type"=>"text","text"=>$fallback]]]); exit; }
    echo json_encode(["ok"=>false,"error"=>"AI service temporarily unavailable."]);
    exit;
}

$data = json_decode($response, true);
$text = $data["choices"][0]["message"]["content"] ?? null;
if (!$text) { echo json_encode(["ok"=>false,"error"=>"Empty AI response."]); exit; }
echo json_encode(["ok"=>true,"content"=>[["type"=>"text","text"=>$text]]]);
