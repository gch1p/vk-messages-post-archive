<?php

require_once __DIR__.'/common.php';

ini_set('memory_limit', '3072M');

function findAllAttachments(array $obj): array {
    $list = [];
    if (!empty($obj['attachments'])) {
        foreach ($obj['attachments'] as $attachment) {
            $list[] = $attachment;
            if ($attachment['type'] == 'wall' || $attachment['type'] == 'wall_reply') {
                $list = array_merge($list, findAllAttachments($attachment));
            }
        }
        $list = array_merge($list, $obj['attachments']);
    }
    if (!empty($obj['fwd_messages'])) {
        foreach ($obj['fwd_messages'] as $fwd_message) {
            $list = array_merge($list, findAllAttachments($fwd_message));
        }
    }
    $list = array_filter($list, function($attachment) {
        static $ids = [];

        $type = $attachment['type'];
        if (!isset($attachment[$type]))
            // weird
            return false;

        $attach = $attachment[$type];

        $id = $type;
        if (isset($attach['owner_id']))
            $id .= $attach['owner_id'].'_';
        if (isset($attach['id']))
            $id .= isset($attach['id']);

        if (isset($ids[$id]))
            return false;

        $ids[$id] = true;
        return true;
    });
    return $list;
}

$api_dir = ARCHIVE_DIR.'/messages/api';
foreach (scandir($api_dir) as $n) {
    if ($n == '.' || $n == '..')
        continue;

    foreach (scandir($api_dir.'/'.$n) as $file) {
        if (!preg_match('/^\d+\.txt$/', $file))
            continue;

        $obj = json_decode(file_get_contents($api_dir.'/'.$n.'/'.$file), true);
        $attachments = findAllAttachments($obj);

        $docs = array_filter($attachments, function($a) {
            return $a['type'] == 'doc';
        });
        if (empty($docs))
            continue;

        foreach ($docs as $doc) {
            $doc = $doc['doc']; // seriously?!
            $doc_id = $doc['owner_id'].'_'.$doc['id'];

            $doc_dir = ARCHIVE_DIR.'/messages/docs/'.$doc_id;
            if (!file_exists($doc_dir)) {
                if (!mkdir($doc_dir, 0755, true))
                    fatalError("failed to mkdir({$doc_dir})");
            }

            // TODO sanitize filename
            $doc_file = $doc_dir.'/'.$doc['title'];
            if (file_exists($doc_file)) {
                if (filesize($doc_file) == 56655)
                    unlink($doc_file);
                else {
                    echo "$doc_id already exists\n";
                    continue;
                }
            }

            list($code, $body) = httpGet($doc['url']);
            if ($code != 200) {
                fprintf(STDERR, "failed to download {$doc_id} ({$doc['url']})\n");
                rmdir($doc_dir);
                continue;
            }

            file_put_contents($doc_file, $body);
            echo "$doc_id saved, ".filesize($doc_file)." bytes\n";
            unset($body);
        }
    }
}