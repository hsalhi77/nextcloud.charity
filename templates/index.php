<?php
$appManager = \OC::$server->get(\OCP\App\IAppManager::class);
$info = $appManager->getAppInfo('charity');
$version = $info['version'] ?? '1';
$scriptUrl = \OC::$server->get(\OCP\IURLGenerator::class)->linkTo('charity', 'js/charity.js') . '?v=' . $version;
emit_script_tag($scriptUrl);
?>
<div id="app"></div>
