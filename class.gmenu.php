<?php
if(!defined('MODX_BASE_PATH')) {
	die();
}

class gMenu {
	var $_cfg;
	var $_tpl;
	var $_css;
	var $_map;
	var $_here;
	var $this_id;
	var $userCfg;
	var $parentTree = array();
	var $placeHolders = array(
		'rowLevel' => array(
			'[+wf.wrapper+]',
			'[+wf.classes+]',
			'[+wf.link+]',
			'[+wf.title+]',
			'[+wf.linktext+]',
			'[+wf.id+]',
			'[+wf.alias+]',
			'[+wf.attributes+]',
			'[+wf.docid+]',
			'[+wf.linktext_hash+]'
		),
		'wrapperLevel' => array(
			'[+wf.wrapper+]',
			'[+wf.classes+]'
		)
	);

	public function run() {
		global $modx;

		$this->this_id = $modx->documentIdentifier;

		if(empty($modx->gmenu_aliasListing)) {
			$fields = "sc.id, sc.parent, sc.pagetitle, sc.longtitle, sc.menutitle, sc.type, IF(sc.type='reference',(SELECT alias FROM " . $modx->getFullTableName("site_content") . " WHERE id=sc.content),sc.alias) as alias, sc.link_attributes, sc.template";

			$sql = $modx->db->query("SELECT " . $fields . "  FROM " . $modx->getFullTableName("site_content") . " sc WHERE sc.hidemenu=0 AND sc.deleted=0 AND sc.published=1 ORDER BY sc.parent, sc.menuindex");

			while($row = $modx->db->getRow($sql)) {

				if($modx->config['friendly_urls'] == 1 && $modx->config['use_alias_path'] == 1) {
					$tmpPath = $this->getParents($row['parent']);
					$row['alias'] = (strlen($tmpPath) > 0 ? $tmpPath . "/" : '') . $row['alias'];
					$this->documentListing[$row['alias']] = $row['id'];
				} else {
					$this->documentListing[$row['alias']] = $row['id'];
				}

				if(empty($modx->aliasListing[$row['id']])) {
					$modx->aliasListing[$row['id']] = array(
						'id' => $row['id'],
						'parent' => $row['parent'],
						'pagetitle' => $row['pagetitle'],
						'longtitle' => $row['longtitle'],
						'menutitle' => $row['menutitle'],
						'path' => $row['alias'],
						'type' => $row['type'],
						'alias' => $row['alias'],
						'link_attributes' => $row['link_attributes'],
						'template' => $row['template']
					);
					$this->documentMap[] = array($row['parent'] => $row['id']);
					$this->documentListing[$row['alias']] = $row['id'];
				} else {
					$modx->aliasListing[$row['id']] = array_merge($modx->aliasListing[$row['id']], array(
						'pagetitle' => $row['pagetitle'],
						'longtitle' => $row['longtitle'],
						'menutitle' => $row['menutitle'],
						'path' => $row['alias'],
						'type' => $row['type'],
						'link_attributes' => $row['link_attributes'],
						'template' => $row['template']
					));
				}

				$modx->gmenu_documentMap[$row['id']] = $row['parent'];
			}

			$modx->gmenu_aliasListing = 1;
		}

		if($modx->gmenu_documentMap && $this->_cfg['tvList']) {
			$tvList = '"' . str_replace(',', '","', $this->_cfg['tvList']) . '"';

			$sql = $modx->db->query('SELECT tv.name, tvc.* FROM ' . $modx->getFullTableName('site_tmplvars') . ' AS tv
				LEFT JOIN ' . $modx->getFullTableName('site_tmplvar_contentvalues') . ' AS tvc ON tv.id=tvc.tmplvarid
				WHERE tv.name IN (' . $tvList . ') AND tvc.contentid IN (' . implode(',', array_keys($modx->gmenu_documentMap)) . ')');
			while($row = $modx->db->getRow($sql)) {
				$modx->aliasListing[$row['contentid']]['tv'][$row['name']] = $row['value'];
			}
		}

		if(!$this->userCfg) {
			$this->setTemplates();
		}

		$this->checkTemplates();

		if(in_array($this->this_id, array_keys($modx->aliasListing))) {
			$this->_here = $this->this_id;
		} else {
			if(in_array($modx->documentObject['parent'], array_keys($modx->aliasListing))) {
				$this->_here = $this->this_id = $modx->documentObject['parent'];
			} else {
				$this->_here = $this->this_id = empty($modx->aliasListing[$modx->documentObject['parent']]['parent']) ? 0 : $modx->aliasListing[$modx->documentObject['parent']]['parent'];
			}
		}

		$this->parentTree = $this->_getParentIds($this->this_id);
		$this->parentTree[] = $this->this_id;

		return $this->buildMenu($this->_cfg['id']);
	}

	private function getParents($id, $path = '') { // modx:returns child's parent
		global $modx;

		if(empty($this->aliases)) {
			$qh = $modx->db->select('id, IF(alias=\'\', id, alias) AS alias, parent, alias_visible', $modx->getFullTableName('site_content'));
			while($row = $modx->db->getRow($qh)) {
				$this->aliases[$row['id']] = $row['alias'];
				$this->parents[$row['id']] = $row['parent'];
				$this->aliasVisible[$row['id']] = $row['alias_visible'];
			}
		}
		if(isset($this->aliases[$id])) {
			$path = ($this->aliasVisible[$id] == 1 ? $this->aliases[$id] . ($path != '' ? '/' : '') . $path : $path);
			return $this->getParents($this->parents[$id], $path);
		}
		return $path;
	}

	private function setTemplates() {
		global $modx;
		foreach($this->_tpl as $k => $v) {
			$this->_tpl[$k] = empty($v) ? $v : str_replace('[+' . $k . '+]', $v, $modx->chunkCache[$v]);
		}
	}

	private function checkTemplates() {
		$nonWayfinderFields = array();
		foreach($this->_tpl as $n => $v) {
			$templateCheck = $this->fetch($n);
			if(empty($v) || !$templateCheck) {
				if($n === 'outerTpl') {
					$this->_tpl[$n] = '<ul[+wf.classes+]>[+wf.wrapper+]</ul>';
				} elseif($n === 'rowTpl') {
					$this->_tpl[$n] = '<li[+wf.id+][+wf.classes+]><a href="[+wf.link+]" title="[+wf.title+]" [+wf.attributes+]>[+wf.linktext+]</a>[+wf.wrapper+]</li>';
				} elseif($n === 'startItemTpl') {
					$this->_tpl[$n] = '<h2[+wf.id+][+wf.classes+]>[+wf.linktext+]</h2>[+wf.wrapper+]';
				} else {
					$this->_tpl[$n] = false;
				}
			} else {
				$this->_tpl[$n] = $templateCheck;
			}
		}
	}

	private function fetch($tpl) {
		if($this->_tpl[$tpl] && !$this->userCfg) {
			$template = $this->_tpl[$tpl];
		} else if($this->userCfg && substr($this->_tpl[$tpl], 0, 6) == "@CODE:") {
			$template = substr($this->_tpl[$tpl], 6);
		} else {
			$template = false;
		}

		return $template;
	}

	private function _getParentIds($id, $height = 10) {
		global $modx;
		$parents = array();
		while($id && $height--) {
			$thisid = $id;
			$id = isset($modx->aliasListing[$id]['parent']) ? $modx->aliasListing[$id]['parent'] : '';
			if(!$id) {
				break;
			}
			$parents[$thisid] = $id;
		}

		return $parents;
	}

	private function buildMenu($id) {
		global $modx;

		$subMenuOutput = '';
		$firstItem = 1;
		$counter = 1;
		$level = count($this->_getParentIds($id)) + 1;
		$sub = array_keys($modx->gmenu_documentMap, $id);
		$numSubItems = count($sub);

		foreach($sub as $key) {
			$lastItem = $counter == $numSubItems && $numSubItems > 1 ? 1 : 0;
			$sub2 = array_keys($modx->gmenu_documentMap, $key);
			$isFolder = count($sub2) ? 1 : 0;
			if($this->_cfg['hideSubMenus']) {
				$submenu = $this->isHere($key) ? 1 : '';
			} elseif($this->_cfg['limit']) {
				$submenu = $this->_cfg['limit'] > $level ? 1 : '';
			} elseif($this->_cfg['hideFirstLevel']) {
				$submenu = $this->isHere($key) || $level > 1 ? 1 : '';
			} else {
				$submenu = $isFolder ? 1 : '';
			}
			$classNames = $this->setItemClass('rowcls', $key, $firstItem, $lastItem, $level, $isFolder);
			$doc['class'] = $classNames ? ' class="' . $classNames . '"' : '';
			$doc['id'] = $key;
			$doc['level'] = $level;
			$doc['submenu'] = $submenu ? 1 : '';
			$doc['isfolder'] = $isFolder;
			$doc['link_attributes'] = $modx->aliasListing[$key]['link_attributes'];
			$doc['template'] = $modx->aliasListing[$key]['template'];
			$subMenuOutput .= $this->renderRow($doc);
			$firstItem = 0;
			$counter++;
		}

		if($level > 0) {
			if($this->_tpl['innerTpl'] && $level > 1) {
				if(!empty($this->_tpl['inner' . $level . 'Tpl'])) {
					$useChunk = $this->_tpl['inner' . $level . 'Tpl'];
				} else {
					$useChunk = $this->_tpl['innerTpl'];
				}
			} else {
				$useChunk = $this->_tpl['outerTpl'];
			}

			if($level > 1) {
				$wrapperClass = 'innercls';
			} else {
				$wrapperClass = 'outercls';
			}

			$classNames = $this->setItemClass($wrapperClass);
			$useClass = $classNames ? ' class="' . $classNames . '"' : '';
			$phArray = array(
				$subMenuOutput,
				$useClass
			);

			$subMenuOutput = str_replace($this->placeHolders['wrapperLevel'], $phArray, $useChunk);

			return $subMenuOutput;
		}
	}

	private function isHere($id) {
		return in_array($id, $this->parentTree);
	}

	private function setItemClass($classType, $docId = 0, $first = 0, $last = 0, $level = 0, $isFolder = 0) {
		$returnClass = '';
		if($classType === 'outercls' && !empty($this->_css['outer'])) {
			$returnClass .= $this->_css['outer'];
		} elseif($classType === 'innercls' && !empty($this->_css['inner'])) {
			$returnClass .= $this->_css['inner'];
		} elseif($classType === 'rowcls') {
			if(!empty($this->_css['row'])) {
				$returnClass .= $this->_css['row'];
			}
			if($first && !empty($this->_css['first'])) {
				$returnClass .= $returnClass ? ' ' . $this->_css['first'] : $this->_css['first'];
			}
			if($last && !empty($this->_css['last'])) {
				$returnClass .= $returnClass ? ' ' . $this->_css['last'] : $this->_css['last'];
			}
			if(!empty($this->_css['level'])) {
				$returnClass .= $returnClass ? ' ' . $this->_css['level'] . $level : $this->_css['level'] . $level;
			}
			if($isFolder && !empty($this->_css['parent'])) {
				$returnClass .= $returnClass ? ' ' . $this->_css['parent'] : $this->_css['parent'];
			}
			if(!empty($this->_css['here']) && $this->isHere($docId)) {
				$returnClass .= $returnClass ? ' ' . $this->_css['here'] : $this->_css['here'];
			}
		}

		return $returnClass;
	}

	private function renderRow($doc) {
		global $modx;
		$out = '';

		if(isset($this->_cfg['displayStart']) && $doc['level'] == 0) {
			$usedTemplate = 'startItemTpl';
		} elseif($doc['id'] == $this->this_id && $doc['isfolder'] && $this->_tpl['parentRowHereTpl'] && ($doc['level'] < $this->_cfg['level'] || $this->_cfg['level'] == 0)) {
			if(!empty($this->_tpl['parentRowHere' . $doc['level'] . 'Tpl'])) {
				$usedTemplate = 'parentRowHere' . $doc['level'] . 'Tpl';
			} else {
				$usedTemplate = 'parentRowHereTpl';
			}
		} elseif($doc['id'] == $this->this_id && $this->_tpl['innerHereTpl'] && $doc['level'] > 1) {
			if(!empty($this->_tpl['innerHere' . $doc['level'] . 'Tpl'])) {
				$usedTemplate = 'innerHere' . $doc['level'] . 'Tpl';
			} else {
				$usedTemplate = 'innerHereTpl';
			}
		} elseif($doc['id'] == $this->_here && $this->_tpl['hereTpl']) {
			if(!empty($this->_tpl['here' . $doc['level'] . 'Tpl'])) {
				$usedTemplate = 'here' . $doc['level'] . 'Tpl';
			} else {
				$usedTemplate = 'hereTpl';
			}
		} elseif($doc['isfolder'] && $this->_tpl['activeParentRowTpl'] && ($doc['level'] < $this->_cfg['level'] || $this->_cfg['level'] == 0) && $this->isHere($doc['id'])) {
			if(!empty($this->_tpl['activeParentRow' . $doc['level'] . 'Tpl'])) {
				$usedTemplate = 'activeParentRow' . $doc['level'] . 'Tpl';
			} else {
				$usedTemplate = 'activeParentRowTpl';
			}
		} elseif($doc['isfolder'] && ($doc['template'] == "0" || is_numeric(strpos($doc['link_attributes'], 'rel="category"'))) && $this->_tpl['categoryFoldersTpl'] && ($doc['level'] < $this->_cfg['level'] || $this->_cfg['level'] == 0)) {
			if(!empty($this->_tpl['categoryFolders' . $doc['level'] . 'Tpl'])) {
				$usedTemplate = 'categoryFolders' . $doc['level'] . 'Tpl';
			} else {
				$usedTemplate = 'categoryFoldersTpl';
			}
		} elseif($doc['isfolder'] && $this->_tpl['parentRowTpl'] && ($doc['level'] < $this->_cfg['level'] || $this->_cfg['level'] == 0)) {
			if(!empty($this->_tpl['parentRow' . $doc['level'] . 'Tpl'])) {
				$usedTemplate = 'parentRow' . $doc['level'] . 'Tpl';
			} else {
				$usedTemplate = 'parentRowTpl';
			}
		} elseif($doc['level'] > 1 && ($this->_tpl['innerRowTpl'] || !empty($this->_tpl['innerRow' . $doc['level'] . 'Tpl']))) {
			if(!empty($this->_tpl['innerRow' . $doc['level'] . 'Tpl'])) {
				$usedTemplate = 'innerRow' . $doc['level'] . 'Tpl';
			} else {
				$usedTemplate = 'innerRowTpl';
			}
		} else {
			if(!empty($this->_tpl['row' . $doc['level'] . 'Tpl'])) {
				$usedTemplate = 'row' . $doc['level'] . 'Tpl';
			} else {
				$usedTemplate = 'rowTpl';
			}
		}

		if($this->_cfg['rowIdPrefix']) {
			$useId = ' id="' . $this->_cfg['rowIdPrefix'] . $doc['id'] . '"';
		} else {
			$useId = '';
		}

		$title = $modx->aliasListing[$doc['id']]['longtitle'] ? $modx->aliasListing[$doc['id']]['longtitle'] : $modx->aliasListing[$doc['id']]['pagetitle'];
		$linktext = $modx->aliasListing[$doc['id']]['menutitle'] ? $modx->aliasListing[$doc['id']]['menutitle'] : $modx->aliasListing[$doc['id']]['pagetitle'];
		$phArray = array(
			$doc['submenu'] ? $this->buildMenu($doc['id']) : "",
			$doc['class'],
			$modx->config['friendly_url_prefix'] . $modx->aliasListing[$doc['id']]['path'] . $modx->config['friendly_url_suffix'],
			$title,
			$linktext,
			$useId,
			$modx->aliasListing[$doc['id']]['alias'],
			$modx->aliasListing[$doc['id']]['link_attributes'],
			$doc['id'],
			md5($linktext)
		);

		$usePlaceholders = $this->placeHolders['rowLevel'];
		$out = str_replace($usePlaceholders, $phArray, $this->_tpl[$usedTemplate]);

		if(!empty($modx->aliasListing[$doc['id']]['tv'])) {
			foreach($modx->aliasListing[$doc['id']]['tv'] as $key => $value) {
				$out = str_replace('[+' . $key . '+]', $value, $out);
			}
		}

		return $out;
	}
}
