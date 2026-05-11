<?php
$fbx['settings'] = array(
    'name'                => 'Things',
    'password'            => 'things',
    'default_action'      => 'ui.items',
    'development_plugins' => array('debug', 'profiler', 'queries', 'menu'),
    'production_plugins'  => array(),
    'pre'                 => array(),
    'post'                => array(),
    'mysqli_host'         => 'localhost',
    'mysqli_database'     => 'things',
    'mysqli_user'         => 'things',
    'mysqli_pass'         => 'things',
    'anthropic_api_key'  => '',
    'anthropic_model'    => 'claude-sonnet-4-6',
);

// Local overrides — gitignored, never committed. Put secrets here.
if (file_exists(__DIR__ . '/settings.local.php'))
    require __DIR__ . '/settings.local.php';
