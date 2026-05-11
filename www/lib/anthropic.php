<?php

function fetch_url_content($url)
{
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_USERAGENT      => 'Mozilla/5.0',
]);
$content  = curl_exec($ch);
$mime     = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if (!$content) return [null, null];

// Determine file_type from MIME or URL extension
$mime = strtolower(strtok($mime ?: '', ';'));
if ($mime === 'application/pdf')     $file_type = 'pdf';
elseif (str_starts_with($mime, 'image/')) $file_type = 'image';
else
{
$ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
$map = ['pdf' => 'pdf', 'png' => 'image', 'jpg' => 'image',
        'jpeg' => 'image', 'gif' => 'image', 'webp' => 'image', 'svg' => 'image'];
$file_type = $map[$ext] ?? 'pdf'; // assume PDF if unknown
}

return [$file_type, $content];
}

function call_anthropic($file_type, $file_content, $query)
{
$api_key = $GLOBALS['fbx']['settings']['anthropic_api_key'] ?? '';
if (!$api_key) return null;

$model = $GLOBALS['fbx']['settings']['anthropic_model'] ?? 'claude-sonnet-4-6';

if ($file_type === 'pdf')
{
$doc_block = [
    'type'   => 'document',
    'source' => ['type' => 'base64', 'media_type' => 'application/pdf',
                 'data' => base64_encode($file_content)],
];
}
elseif ($file_type === 'image')
{
$mime      = (new finfo(FILEINFO_MIME_TYPE))->buffer($file_content) ?: 'image/jpeg';
$doc_block = [
    'type'   => 'image',
    'source' => ['type' => 'base64', 'media_type' => $mime,
                 'data' => base64_encode($file_content)],
];
}
else
{
$doc_block = ['type' => 'text', 'text' => "Document:\n\n" . $file_content];
}

$instruction = $query . "\n\n"
    . "Respond with a JSON array where each element has:\n"
    . "  \"name\": short display label (1-5 words)\n"
    . "  \"content\": full markdown for that section\n"
    . "Use an array even when there is only one result. "
    . "Do not wrap the JSON in a code fence.";

$payload = [
    'model'      => $model,
    'max_tokens' => 8192,
    'messages'   => [[
        'role'    => 'user',
        'content' => [$doc_block, ['type' => 'text', 'text' => $instruction]],
    ]],
];

$ch = curl_init('https://api.anthropic.com/v1/messages');

curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 120,
]);

$response = curl_exec($ch);
$curl_err  = curl_error($ch);
curl_close($ch);

if (!$response || $curl_err) return null;

$data = json_decode($response, true);
$text = $data['content'][0]['text'] ?? null;
if (!$text) return null;

// Strip accidental code fences, then parse JSON
$text    = trim(preg_replace('/^```(?:json)?\s*|\s*```$/s', '', trim($text)));
$results = json_decode($text, true);

// Fallback: treat whole response as a single result
if (!is_array($results) || empty($results) || !isset($results[0]['content']))
{
$results = [['name' => mb_substr($query, 0, 60), 'content' => $text]];
}

return $results;
}
