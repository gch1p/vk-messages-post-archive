<?php

require_once __DIR__.'/common.php';

$file = $argv[1] ?? '';
if (!$file)
    fatalError("no file provided");

$str = file_get_contents($file);
$str = iconv('windows-1251', 'utf-8//IGNORE', $str);

try {
    $doc = onEachMessageOrAttachment($str,
        null,
        function (simplehtmldom\HtmlDocument $doc, string $href, simplehtmldom\HtmlNode $link_node) {
            $local_href = '../../'.preg_replace('#^https?://#', '', $href);

            /** @var simplehtmldom\HtmlNode $parent */
            $parent = $link_node->parent();
            $link_node->remove();

            $img = $doc->createElement('img');
            $img->setAttribute('src', $local_href);
            $img->setAttribute('alt', $href);

            $parent->appendChild($doc->createElement('br'));
            $parent->appendChild($img);
        });
} catch (Exception $e) {
    fatalError($e->getMessage());
}

file_put_contents($file, iconv('utf-8', 'windows-1251//IGNORE', $doc->outertext));