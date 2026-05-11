<?php

function do_create_reference($item_id, $input, $file)
{
$item_id = (int)$item_id;
if (!$item_id || empty($file['tmp_name'])) return 0;

$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$type_map = ['pdf' => 'pdf', 'png' => 'image', 'jpg' => 'image', 'jpeg' => 'image',
             'gif' => 'image', 'webp' => 'image', 'svg' => 'image', 'md' => 'md'];
$file_type = $type_map[$ext] ?? null;
if (!$file_type) return 0;

$upload_dir = $GLOBALS['fbx']['site_root'] . 'uploads/' . $item_id . '/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) return 0;

$file_path     = db_esc('uploads/' . $item_id . '/' . $filename);
$name          = db_esc($input['name'] ?? $filename);
$display_order = (int)($input['display_order'] ?? 0);

mysqli_safe_query(
    "INSERT INTO item_references (item_id, name, file_type, file_path, display_order)
     VALUES ($item_id, '$name', '$file_type', '$file_path', $display_order)",
    __FILE__, __LINE__
);
return (int)mysqli_insert_id($GLOBALS['fbx']['dbh']);
}
