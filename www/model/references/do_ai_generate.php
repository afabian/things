<?php

function do_ai_generate($item_id, $ref_id, $query, $url = '')
{
$item_id = (int)$item_id;
$ref_id  = (int)$ref_id;

if ($url)
{
list($file_type, $file_content) = fetch_url_content($url);
if (!$file_content) return ['error' => 'Could not fetch URL'];
}
else
{
$ref = query('qry_get_reference', $ref_id);
if (!$ref || (int)$ref['item_id'] !== $item_id) return ['error' => 'Reference not found'];

$file_path = $GLOBALS['fbx']['site_root'] . $ref['file_path'];
if (!file_exists($file_path)) return ['error' => 'Source file not found on disk'];

$file_content = file_get_contents($file_path);
if ($file_content === false) return ['error' => 'Could not read source file'];

$file_type = $ref['file_type'];
}

$results = call_anthropic($file_type, $file_content, $query);
if ($results === null) return ['error' => 'AI request failed — check anthropic_api_key in settings.php'];

$upload_dir = $GLOBALS['fbx']['site_root'] . 'uploads/' . $item_id . '/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$cnt = mysqli_fetch_assoc(mysqli_safe_query(
    "SELECT COUNT(*) AS n FROM item_references WHERE item_id = $item_id",
    __FILE__, __LINE__
));
$next_order = (int)$cnt['n'];

$created = [];

foreach ($results as $idx => $result)
{
$name    = trim($result['name'] ?? 'AI Result');
$content = trim($result['content'] ?? '');
if (!$content) continue;

$filename  = 'ai_' . time() . '_' . uniqid() . '.md';
file_put_contents($upload_dir . $filename, $content);

$file_path_db  = db_esc('uploads/' . $item_id . '/' . $filename);
$name_db       = db_esc($name);
$display_order = $next_order + $idx;

mysqli_safe_query(
    "INSERT INTO item_references (item_id, name, file_type, file_path, display_order)
     VALUES ($item_id, '$name_db', 'md', '$file_path_db', $display_order)",
    __FILE__, __LINE__
);

$created[] = query('qry_get_reference', (int)mysqli_insert_id($GLOBALS['fbx']['dbh']));
}

return $created;
}
