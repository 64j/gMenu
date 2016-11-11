<?php
if(!defined('MODX_BASE_PATH')) {
	die();
}

$gmenu_base = $modx->config['base_path'] . "assets/snippets/gmenu/";
$userCfg = (isset($config)) ? "{$gmenu_base}configs/{$config}.config.php" : "{$gmenu_base}configs/default.config.php";
include_once("class.gmenu.php");

$gm = new gMenu();
$gm->userCfg = isset($config) ? true : false;

if(file_exists($userCfg)) {
	$config = array();
	include_once("$userCfg");
	$modx->event->params = array_merge($modx->event->params, $config);
}

$gm->_cfg = array(
	'id' => isset($startId) ? $startId : $modx->documentIdentifier,
	'ph' => isset($ph) ? $ph : false,
	'rowIdPrefix' => isset($rowIdPrefix) ? $rowIdPrefix : false,
	'hideSubMenus' => isset($hideSubMenus) ? $hideSubMenus : false,
	'hideFirstLevel' => isset($hideSubMenus) ? $hideSubMenus : false,
	'limit' => isset($limit) ? $limit : 0,
	'staticKeys' => isset($staticKeys) && $staticKeys == 1 ? true : false,
	'cachefile' => isset($cachefile) ? $cachefile : MODX_BASE_PATH . 'assets/cache/gmenu3.pageCache.php',
	'level' => isset($level) ? $level : '',
	'tvList' => isset($tvList) ? $tvList : ''
);

$gm->_tpl = array(
	'outerTpl' => isset($outerTpl) ? $outerTpl : '',
	'rowTpl' => isset($rowTpl) ? $rowTpl : '',
	'parentRowTpl' => isset($parentRowTpl) ? $parentRowTpl : '',
	'parentRowHereTpl' => isset($parentRowHereTpl) ? $parentRowHereTpl : '',
	'hereTpl' => isset($hereTpl) ? $hereTpl : '',
	'innerTpl' => isset($innerTpl) ? $innerTpl : '',
	'innerRowTpl' => isset($innerRowTpl) ? $innerRowTpl : '',
	'innerHereTpl' => isset($innerHereTpl) ? $innerHereTpl : '',
	'activeParentRowTpl' => isset($activeParentRowTpl) ? $activeParentRowTpl : '',
	'categoryFoldersTpl' => isset($categoryFoldersTpl) ? $categoryFoldersTpl : '',
	'startItemTpl' => isset($startItemTpl) ? $startItemTpl : ''
);

$gm->_css = array(
	'first' => isset($firstClass) ? $firstClass : '',
	'last' => isset($lastClass) ? $lastClass : 'last',
	'here' => isset($hereClass) ? $hereClass : 'active',
	'parent' => isset($parentClass) ? $parentClass : 'parent',
	'row' => isset($rowClass) ? $rowClass : '',
	'level' => isset($levelClass) ? $levelClass : '',
	'outer' => isset($outerClass) ? $outerClass : '',
	'inner' => isset($innerClass) ? $innerClass : ''
);

foreach ($modx->event->params as $key => $value) {
	if (substr($key, -3, 3) == 'Tpl') {
		$gm->_tpl[$key] = $value;
	}
}

$output = $gm->run();

return $output;
