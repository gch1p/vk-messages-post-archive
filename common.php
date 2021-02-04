<?php

require_once __DIR__.'/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('FAKE_USER_AGENT', 'User-Agent: VKDesktopMessenger/5.0.1 (darwin; 19.6.0; x64)');
define('ACCESS_TOKEN', '');
define('ARCHIVE_DIR', '');

function fatalError(string $message) {
    fprintf(STDERR, "error: ".$message."\n");
    exit(1);
}

/**
 * @param string $str
 * @param callable|null $message_callback
 * @param callable|null $photo_callback
 * @return simplehtmldom\HtmlDocument
 * @throws Exception
 */
function onEachMessageOrAttachment(string $str, ?callable $message_callback, ?callable $photo_callback) {
    $doc = new simplehtmldom\HtmlDocument($str,
        /* $lowercase       */ true,
        /* $forceTagsClosed */ true,
        /* $target_charset  */ simplehtmldom\DEFAULT_TARGET_CHARSET,
        /* $stripRN         */ false
    );
    if (!$doc)
        throw new Exception('failed to parse html');

    $nodes = $doc->find('.message');
    if (!count($nodes))
        throw new Exception('no message nodes found');

    foreach ($nodes as $node) {
        $kludges = $node->find('.kludges');
        if (empty($kludges))
            continue;

        $attachments = $kludges[0]->find('.attachment');
        if (empty($attachments))
            continue;

        $message_id = $node->getAttribute('data-id');
        if (!is_null($message_callback))
            $message_callback($doc, $message_id, $node);

        foreach ($attachments as $attachment) {
            $desc = $attachment->find('.attachment__description');
            if (empty($desc) || $desc[0]->innertext != 'Фотография')
                continue;

            $link_node = $attachment->find('a.attachment__link');
            if (!$link_node)
                continue;

            $href = $link_node[0]->href;
            if (strpos($href, 'https://vk.com/im?sel') !== false)
                continue;

            if (!is_null($photo_callback))
                $photo_callback($doc, $href, $link_node[0]);
        }
    }

    return $doc;
}

function httpPost(string $url, array $fields = []): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_USERAGENT, FAKE_USER_AGENT);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

function httpGet(string $url): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, FAKE_USER_AGENT);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}
