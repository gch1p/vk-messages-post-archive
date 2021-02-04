<?php

require_once __DIR__.'/common.php';

$file = $argv[1] ?? '';
if (!$file)
    fatalError("no file provided");

$str = file_get_contents($file);
$str = iconv('windows-1251', 'utf-8//IGNORE', $str);

$is_modified = false;

try {
    $doc = onEachMessageOrAttachment($str, function (simplehtmldom\HtmlDocument $doc, int $id, simplehtmldom\HtmlNode $node) {
        global $is_modified;

        $file = ARCHIVE_DIR.'/messages/api/'.($id % 100).'/'.$id.'.txt';
        if (!file_exists($file))
            return;

        $obj = file_get_contents($file);

        $a = $doc->createElement('a');
        $a->setAttribute('href', 'javascript:void(0)');
        $a->setAttribute('onclick', "this.nextSibling.style.display = (this.nextSibling.style.display === 'none' ? 'block' : 'none')");
        $a->appendChild($doc->createTextNode('Показать/скрыть объект API'));

        $div = $doc->createElement('div');
        $div->setAttribute('style', 'display: none; font-size: 11px; font-family: monospace; background-color: #edeef0; padding: 10px; white-space: pre; overflow: auto;');
        $div->appendChild($doc->createTextNode($obj));

        $node->appendChild($doc->createElement('br'));
        $node->appendChild($a);
        $node->appendChild($div);

        $is_modified = true;
    }, null);
} catch (Exception $e) {
    fatalError($e->getMessage());
}

if ($is_modified)
    file_put_contents($file, iconv('utf-8', 'windows-1251//IGNORE', $doc->outertext));