<?php
header("Content-Type: application/json");
require_once '../lib/Telegram.php';
require_once '../config.php';

global $tg;

$tg = new Telegram($api_key, $botname);

if($tg->getMessage() == "/start") {
	$tg->sendMessage('Hi! I am Linky! Just add me to a group and I will send you the URLs for wikilinks.');
}

$wikilink = [];
$links = preg_split("~\[\[~", $tg->getText());
for($i = 0; $i < count($links); $i++) {
	if(!(strstr($links[$i], ']]') === false)) {
		$wikilink[] = substr($links[$i], 0, strpos($links[$i], ']]'));
	}
}

for($i = 0; $i < count($wikilink); $i++) {
	$tg->sendMessage('https://de.wikipedia.org/w/index.php?title=' . urlencode($wikilink[$i]));
}

echo $tg->webhookAnswer();
$tg->answer();