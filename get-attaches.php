<?php

require_once __DIR__.'/common.php';

$str = file_get_contents('php://stdin');
if (!$str)
    fatalError("no input");

$message_ids = [];
$photo_urls = [];

$on_message = function($doc, $message_id) {
    global $message_ids;
    $message_ids[] = $message_id;
};
$on_photo = function($doc, $href, $link_node) {
    global $photo_urls;
    $photo_urls[] = $href;
};

try {
    onEachMessageOrAttachment($str, $on_message, $on_photo);
} catch (Exception $e) {
    fatalError($e->getMessage());
}

if (!empty($message_ids))
    echo implode("\n", $message_ids)."\n";

if (!empty($photo_urls))
    echo implode("\n", $photo_urls)."\n";