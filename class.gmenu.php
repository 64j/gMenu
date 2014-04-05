<?php
if (!defined('MODX_BASE_PATH'))
  die();
class gMenu {
  var $_cfg;
  var $_tpl;
  var $_css;
  var $_map;
  var $_here;
  var $userCfg;
  var $parentTree = array();
  var $placeHolders = array('rowLevel' => array('%wf.wrapper%', '%wf.classes%', '%wf.link%', '%wf.title%', '%wf.linktext%', '%wf.id%', '%wf.alias%', '%wf.attributes%', '%wf.docid%', '%wf.linktext_hash%'), 'wrapperLevel' => array('%wf.wrapper%', '%wf.classes%'));
	var $aliasList;
  function run() {
    global $modx, $gm_Map;
    $this->modx         = $modx;
    $this->this_id      = $this->modx->documentIdentifier;
    if (!$this->_map) {
			if($this->_cfg['staticKeys']) {
				// generator keys
			} else {
				$fields = "sc.menutitle, sc.pagetitle, sc.parent, sc.id, IF(sc.type='reference',(SELECT alias FROM ".$this->modx->getFullTableName("site_content")." WHERE id=sc.content),sc.alias) as alias, sc.link_attributes";
				$sql = $this->query("SELECT ". $fields ."  FROM ". $this->modx->getFullTableName("site_content") ." sc WHERE sc.hidemenu=0 AND deleted=0 ORDER BY sc.parent, sc.menuindex");
				while ($row = mysql_fetch_array($sql)) {
					$this->_map[$row['id']] = $row['parent'];
					$this->aliasList[$row['id']] = array('id'=>$row['id'], 'parent'=>$row['parent'], 'alias'=>$row['alias'], 'pagetitle'=>$row['pagetitle'], 'menutitle'=>$row['menutitle'], 'link_attributes'=>$row['link_attributes']);
				}
			}
    }
    $this->parentTree   = $this->_getParentIds($this->this_id);
    $this->parentTree[] = $this->this_id;
    if (!$this->userCfg) {
      $this->setTemplates(array_filter($this->_tpl));
    }
    $this->checkTemplates();
    if (in_array($this->this_id, array_keys($this->_map))) {
      $this->_here = $this->this_id;
    } else {
      $this->_here = $this->modx->db->getValue($this->query("SELECT parent FROM ". $this->modx->getFullTableName("site_content") ." WHERE id=" . $this->this_id . " LIMIT 1"));
    }
    return $this->buildMenu($this->_cfg['id']);
  }
	private	function _getParentIds($id, $height= 10) {
			$parents= array ();
			while ( $id && $height-- ) {
					$thisid = $id;
					$id = $this->aliasList[$id]['parent'];
					if (!$id) break;
					$parents[$thisid] = $id;
			}
			return $parents;
	}
	private function query($sql) {
		return $this->modx->dbQuery($sql);
	}
  private function setTemplates($chunks) {
    $tpls     = array();
    $out_tpls = array();
    if (is_array($chunks)) {
      $chunks = '"' . implode('","', $chunks) . '"';
    }
    $sql      = "SELECT snippet, name FROM " . $this->modx->getFullTableName("site_htmlsnippets") . " WHERE name IN (" . $chunks . ");";
    $result   = $this->modx->db->query($sql);
    while ($row = $this->modx->db->getRow($result)) {
      $tpls[$row['name']] = str_replace(array('[+', '+]'), array('%', '%'), $row['snippet']);
    }
	/*			foreach ($chunks as $k => $v) {
					$chunk = file_get_contents(MODX_BASE_PATH . "assets/cache/chunks/" . $v . "_" . md5($v) . ".db");
					$tpls[$v] = str_replace(array('[+', '+]'), array('%', '%'), $chunk);
				}*/
    foreach ($this->_tpl as $k => $v) {
      $out_tpls[$k] = $tpls[$v];
    }
    $this->_tpl = $out_tpls;
  }
  private function checkTemplates() {
    foreach ($this->_tpl as $n => $v) {
      $templateCheck = $this->fetch($n);
      if (empty($v) || !$templateCheck) {
        if ($n === 'outerTpl') {
          $this->_tpl[$n] = '<ul%wf.classes%>%wf.wrapper%</ul>';
        } elseif ($n === 'rowTpl') {
          $this->_tpl[$n] = '<li%wf.id%%wf.classes%><a href="%wf.link%" title="%wf.title%" %wf.attributes%>%wf.linktext%</a>%wf.wrapper%</li>';
        } elseif ($n === 'startItemTpl') {
          $this->_tpl[$n] = '<h2%wf.id%%wf.classes%>%wf.linktext%</h2>%wf.wrapper%';
        } else {
          $this->_tpl[$n] = FALSE;
        }
      } else {
        $this->_tpl[$n] = $templateCheck;
      }
    }
  }
  private function setItemClass($classType, $docId = 0, $first = 0, $last = 0, $level = 0, $isFolder = 0) {
    $returnClass = '';
    if ($classType === 'outercls' && !empty($this->_css['outer'])) {
      $returnClass .= $this->_css['outer'];
    } elseif ($classType === 'innercls' && !empty($this->_css['inner'])) {
      $returnClass .= $this->_css['inner'];
    } elseif ($classType === 'rowcls') {
      if (!empty($this->_css['row'])) {
        $returnClass .= $this->_css['row'];
      }
      if ($first && !empty($this->_css['first'])) {
        $returnClass .= $returnClass ? ' ' . $this->_css['first'] : $this->_css['first'];
      }
      if ($last && !empty($this->_css['last'])) {
        $returnClass .= $returnClass ? ' ' . $this->_css['last'] : $this->_css['last'];
      }
      if (!empty($this->_css['level'])) {
        $returnClass .= $returnClass ? ' ' . $this->_css['level'] . $level : $this->_css['level'] . $level;
      }
      if ($isFolder && !empty($this->_css['parent'])) {
        $returnClass .= $returnClass ? ' ' . $this->_css['parent'] : $this->_css['parent'];
      }
      if (!empty($this->_css['here']) && $this->isHere($docId)) {
        $returnClass .= $returnClass ? ' ' . $this->_css['here'] : $this->_css['here'];
      }
    }
    return $returnClass;
  }
  private function isHere($id) {
    return in_array($id, $this->parentTree);
  }
  private function renderRow($doc) {
    if ($this->_cfg['displayStart'] && $doc['level'] == 0) {
      $usedTemplate = 'startItemTpl';
    } elseif ($doc['id'] == $this->this_id && $doc['isfolder'] && $this->_tpl['parentRowHereTpl'] && ($doc['level'] < $this->_cfg['level'] || $this->_cfg['level'] == 0)) {
      $usedTemplate = 'parentRowHereTpl';
    } elseif ($doc['id'] == $this->this_id && $this->_tpl['innerHereTpl'] && $doc['level'] > 1) {
      $usedTemplate = 'innerHereTpl';
    } elseif ($doc['id'] == $this->_here && $this->_tpl['hereTpl']) {
      $usedTemplate = 'hereTpl';
    } elseif ($doc['isfolder'] && $this->_tpl['activeParentRowTpl'] && ($doc['level'] < $this->_cfg['level'] || $this->_cfg['level'] == 0) && $this->isHere($doc['id'])) {
      $usedTemplate = 'activeParentRowTpl';
    } elseif ($doc['isfolder'] && ($doc['template'] == "0" || is_numeric(strpos($doc['link_attributes'], 'rel="category"'))) && $this->_tpl['categoryFoldersTpl'] && ($doc['level'] < $this->_cfg['level'] || $this->_cfg['level'] == 0)) {
      $usedTemplate = 'categoryFoldersTpl';
    } elseif ($doc['isfolder'] && $this->_tpl['parentRowTpl'] && ($doc['level'] < $this->_cfg['level'] || $this->_cfg['level'] == 0)) {
      $usedTemplate = 'parentRowTpl';
    } elseif ($doc['level'] > 1 && $this->_tpl['innerRowTpl']) {
      $usedTemplate = 'innerRowTpl';
    } else {
      $usedTemplate = 'rowTpl';
    }
    if ($this->_cfg['rowIdPrefix']) {
      $useId = ' id="' . $this->_cfg['rowIdPrefix'] . $doc['id'] . '"';
    } else {
      $useId = '';
    }
    $linktext        = $this->aliasList[$doc['id']]['menutitle'] ? $this->aliasList[$doc['id']]['menutitle'] : $this->aliasList[$doc['id']]['pagetitle'];
    $phArray         = array(
      $doc['submenu'] ? $this->buildMenu($doc['id']) : "",
      $doc['class'],
      "/" . $this->modx->config['friendly_url_prefix'] . $this->aliasList[$doc['id']]['alias'] . $this->modx->config['friendly_url_suffix'],
      $this->aliasList[$doc['id']]['pagetitle'],
      $linktext,
      $useId,
      $this->aliasList[$doc['id']]['alias'],
      $this->aliasList[$doc['id']]['link_attributes'],
      $doc['id'],
      hash(md5, $linktext)
    );
    $usePlaceholders = $this->placeHolders['rowLevel'];
    return str_replace($usePlaceholders, $phArray, $this->_tpl[$usedTemplate]);
  }
  private function buildMenu($id) {
    $subMenuOutput = '';
    $firstItem     = 1;
    $counter       = 1;
    $level         = count($this->_getParentIds($id)) + 1;
    $sub           = array_keys($this->_map, $id);
    $numSubItems   = count($sub);
    foreach ($sub as $key) {
      $lastItem = $counter == $numSubItems && $numSubItems > 1 ? 1 : 0;
      $sub2     = array_keys($this->_map, $key);
      $isFolder = count($sub2) ? 1 : 0;
      if ($this->_cfg['hideSubMenus']) {
        $submenu = $this->isHere($key) ? 1 : '';
      } elseif ($this->_cfg['limit']) {
        $submenu = $this->_cfg['limit'] > $level ? 1 : '';
      } elseif ($this->_cfg['hideFirstLevel']) {
        $submenu = $this->isHere($key) || $level > 1 ? 1 : '';
      } else {
        $submenu = $isFolder ? 1 : '';
      }
      $classNames      = $this->setItemClass('rowcls', $key, $firstItem, $lastItem, $level, $isFolder);
      $doc['class']    = $classNames ? ' class="' . $classNames . '"' : '';
      $doc['id']       = $key;
      $doc['level']    = $level;
      $doc['submenu']  = $submenu ? 1 : '';
      $doc['isfolder'] = $isFolder;
      $subMenuOutput .= $this->renderRow($doc);
      $firstItem = 0;
      $counter++;
    }
    if ($level > 0) {
      if ($this->_tpl['innerTpl'] && $level > 1) {
        $useChunk = $this->_tpl['innerTpl'];
      } else {
        $useChunk = $this->_tpl['outerTpl'];
      }
      if ($level > 1) {
        $wrapperClass = 'innercls';
      } else {
        $wrapperClass = 'outercls';
      }
      $classNames    = $this->setItemClass($wrapperClass);
      $useClass      = $classNames ? ' class="' . $classNames . '"' : '';
      $phArray       = array(
        $subMenuOutput,
        $useClass
      );
      $subMenuOutput = str_replace($this->placeHolders['wrapperLevel'], $phArray, $useChunk);
      return $subMenuOutput;
    }
  }
  private function fetch($tpl) {
    if ($this->_tpl[$tpl] && !$this->userCfg) {
      $template = $this->_tpl[$tpl];
    } else if ($this->userCfg && substr($this->_tpl[$tpl], 0, 6) == "@CODE:") {
      $template = str_replace(array('[+', '+]'), array('%', '%'), substr($this->_tpl[$tpl], 6));
    } else {
      $template = false;
    }
    return $template;
  }
}
