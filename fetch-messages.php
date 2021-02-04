<?php

require_once __DIR__.'/common.php';

$message_ids = array_slice($argv, 1);
if (empty($message_ids))
    fatalError('no message ids');

$url = 'https://api.vk.com/method/messages.getById';
$fields = [
    'message_ids' => implode(',', $message_ids),
    'access_token' => ACCESS_TOKEN,
    'v' => '5.109'
];
list($code, $body) = httpPost($url, $fields);

if ($code != 200)
    fatalError('api returned '.$code);

$response = json_decode($body, true);
if (!empty($response['error']))
    fatalError('api error: '.$response['error']['error_msg']);

foreach ($response['response']['items'] as $item) {
    $id = (int)$item['id'];

    $dir_n = $id % 100;
    $cur_dir = ARCHIVE_DIR.'/messages/api/'.$dir_n;

    if (!file_exists($cur_dir)) {
        if (!mkdir($cur_dir, 0755, true))
            fatalError('failed to mkdir('.$cur_dir.')');
    }

    file_put_contents($cur_dir.'/'.$id.'.txt', json_encode($item, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}

sleep(1);