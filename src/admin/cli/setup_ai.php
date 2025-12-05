<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');

$DB->set_debug(true);

$provider_class = 'aiprovider_ollama\\provider'; 
$provider_name = 'Ollama K8s Instance';          
$endpoint_url = 'http://3.25.141.189:8000/ollama'; 
$model_name = 'qwen2.5-coder:1.5b'; 

$config_array = [
    'aiprovider' => 'aiprovider_ollama',
    'name' => $provider_name,
    'endpoint' => $endpoint_url,
    'updateandreturn' => 'Update instance',
    'returnurl' => $CFG->wwwroot . '/admin/settings.php?section=aiprovider', 
    'id' => 1
];


$action_config_array = [
    'core_ai\\aiactions\\generate_text' => [
        'enabled' => 1,
        'settings' => [
            'model' => $model_name,
            'systeminstruction' => "Generate text based on request.",
            'providerid' => 1
        ],
        'modelsettings' => ['custom' => ['modelextraparams' => '']]
    ],
    'core_ai\\aiactions\\summarise_text' => [
        'enabled' => 1,
        'settings' => [
            'model' => $model_name,
            'systeminstruction' => "Summarize this text.",
            'providerid' => 1
        ],
        'modelsettings' => ['custom' => ['modelextraparams' => '']]
    ],
    'core_ai\\aiactions\\explain_text' => [
        'enabled' => 1,
        'settings' => [
            'model' => $model_name,
            'systeminstruction' => "Explain this text.",
            'providerid' => 1
        ],
        'modelsettings' => ['custom' => ['modelextraparams' => '']]
    ]
];

$record = new stdClass();
$record->name = $provider_name;
$record->provider = $provider_class; 
$record->enabled = 1;
$record->config = json_encode($config_array); 
$record->actionconfig = json_encode($action_config_array); 
$record->userid = 2;
$record->timecreated = time();
$record->timemodified = time();

try {
    $existing = $DB->get_record('ai_providers', ['name' => $provider_name]);

    if ($existing) {
        $record->id = $existing->id;
        $DB->update_record('ai_providers', $record);
        mtrace("UPDATED: Da tim thay ID {$existing->id}. Cap nhat thanh cong!");
    } else {
        $id = $DB->insert_record('ai_providers', $record);
        mtrace("INSERTED: Da tao moi cau hinh AI thanh cong (ID: $id)!");
    }
} catch (Exception $e) {
    mtrace("ERROR: " . $e->getMessage());
}


$placements = ['aiplacement_courseassist', 'aiplacement_editor'];

foreach ($placements as $placement) {
    try {
        $current_setting = $DB->get_record('config_plugins', ['plugin' => $placement, 'name' => 'enabled']);
        
        if ($current_setting && $current_setting->value == 1) {
            mtrace("SKIP: Placement '$placement' da duoc bat truoc do.");
        } else {
            set_config('enabled', 1, $placement);
            mtrace("SUCCESS: Da tu dong BAT (Enable) placement: $placement");
        }
    } catch (Exception $e) {
        mtrace("ERROR: Khong the bat $placement: " . $e->getMessage());
    }
}