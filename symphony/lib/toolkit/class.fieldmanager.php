<?php
	/*
	**	SOME DBC INTEGRATION HAS BEEN DONE ON THIS PAGE
	*/

	require_once(TOOLKIT . '/class.field.php');

	Class FieldManager implements Singleton{

		static private $_instance;

		private static $_initialiased_fields = array();
		private static $_pool = array();

		public static function instance() {
			if (!(self::$_instance instanceof self)) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

	    function __find($type){

		    $extensions = ExtensionManager::instance()->listInstalledHandles();

			if(is_array($extensions) && !empty($extensions)){
				foreach($extensions as $e){
					if(is_file(EXTENSIONS . "/{$e}/fields/field.{$type}.php")) return EXTENSIONS . "/{$e}/fields";
				}
			}

			return false;
	    }

        function __getClassName($type){
	        return 'field' . $type;
        }

        function __getClassPath($type){
	        return $this->__find($type);
        }

        function __getDriverPath($type){
	        return $this->__getClassPath($type) . "/field.{$type}.php";
        }

		public function create($type){

			if(!isset(self::$_pool[$type])){

		        $classname = $this->__getClassName($type);
		        $path = $this->__getDriverPath($type);

		        if(!file_exists($path)){
			        throw new Exception(
						__(
							'Could not find Field <code>%1$s</code> at <code>%2$s</code>. If the Field was provided by an Extension, ensure that it is installed, and enabled.',
							array($type, $path)
						)
					);
			        return false;
		        }

				if(!class_exists($classname)){
					require_once($path);
				}

				self::$_pool[$type] = new $classname($this);

				if(self::$_pool[$type]->canShowTableColumn() && !self::$_pool[$type]->get('show_column')){
					self::$_pool[$type]->set('show_column', 'yes');
				}
			}

			return clone self::$_pool[$type];
		}

/*
		public function fetchFieldTypeFromID($id){
			return Symphony::Database()->fetchVar('type', 0, "SELECT `type` FROM `tbl_fields` WHERE `id` = '$id' LIMIT 1");
		}

		## section_id allows for disambiguation
		public function fetchFieldIDFromElementName($element_name, $section_id=NULL){
			return Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_fields` WHERE `element_name` = '$element_name' ".($section_id ? " AND `parent_section` = '$section_id' " : '')." LIMIT 1");
		}

		//function fetchTypeIDFromHandle($handle){
		//	return Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_fields_types` WHERE `handle` = '$handle' LIMIT 1");
		//}

		public function fetchHandleFromElementName($id){
			return Symphony::Database()->fetchVar('element_name', 0, "SELECT `element_name` FROM `tbl_fields` WHERE `id` = '$id' LIMIT 1");
		}
*/

		public function fetchTypes() {
			$extensions = ExtensionManager::instance()->listInstalledHandles();
			$structure = array(
				'filelist'	=> array()
			);

			if (is_array($extensions) && !empty($extensions)) {
				foreach($extensions as $handle){
					if(is_dir(EXTENSIONS . '/' . $handle . '/fields')){
						$tmp = General::listStructure(EXTENSIONS . '/' . $handle . '/fields', '/field.[a-z0-9_-]+.php/i', false, 'asc', EXTENSIONS . '/' . $handle . '/fields');

						if (is_array($tmp['filelist']) && !empty($tmp['filelist'])) {
							$structure['filelist'] = array_merge($structure['filelist'], $tmp['filelist']);
						}
					}
				}

				$structure['filelist'] = General::array_remove_duplicates($structure['filelist']);

			}

			$types = array();

			foreach($structure['filelist'] as $filename) {
				$types[] = str_replace(array('field.', '.php'), '', $filename);
			}
			return $types;
		}

		public function fetch($id=NULL, $section_id=NULL, $order='ASC', $sortfield='sortorder', $type=NULL, $location=NULL, $where=NULL, $restrict=Field::__FIELD_ALL__){

			$obj = NULL;
			$ret = array();

			if(!is_null($id) && is_numeric($id)){
				$returnSingle = true;
			}

			if(!is_null($id) && is_numeric($id) && isset(self::$_initialiased_fields[$id]) && self::$_initialiased_fields[$id] instanceof Field){
				$ret[] = $obj = clone self::$_initialiased_fields[$id];
			}

			else{

				$sql = "SELECT t1.* "
					 . "FROM tbl_fields as t1 "
					 . "WHERE 1 "
					 . ($type ? " AND t1.`type` = '{$type}' " : NULL)
					 . ($location ? " AND t1.`location` = '{$location}' " : NULL)
					 . ($section_id ? " AND t1.`parent_section` = '{$section_id}' " : NULL)
					 . $where
					 . ($id ? " AND t1.`id` = '{$id}' LIMIT 1" : " ORDER BY t1.`{$sortfield}` {$order}");

				if(!$fields = Symphony::Database()->fetch($sql)) return false;

				foreach($fields as $f){

					if(isset(self::$_initialiased_fields[$f['id']]) && self::$_initialiased_fields[$f['id']] instanceof Field){
						$obj = clone self::$_initialiased_fields[$f['id']];
					}
					else{
						$obj = $this->create($f['type']);

						$obj->setArray($f);

						$context = Symphony::Database()->fetchRow(0, sprintf(
							"SELECT * FROM `tbl_fields_%s` WHERE `field_id` = '%s' LIMIT 1", $obj->handle(), $obj->get('id')
						));

						unset($context['id']);
						$obj->setArray($context);

						self::$_initialiased_fields[$obj->get('id')] = clone $obj;
					}

					if($restrict == Field::__FIELD_ALL__
							|| ($restrict == Field::__TOGGLEABLE_ONLY__ && $obj->canToggle())
							|| ($restrict == Field::__UNTOGGLEABLE_ONLY__ && !$obj->canToggle())
							|| ($restrict == Field::__FILTERABLE_ONLY__ && $obj->canFilter())
							|| ($restrict == Field::__UNFILTERABLE_ONLY__ && !$obj->canFilter())
					):
						$ret[] = $obj;
					endif;

				}
			}

			return (count($ret) <= 1 && $returnSingle ? $ret[0] : $ret);
		}

		public function add($fields){

			if(!isset($fields['sortorder'])){
		        $next = Symphony::Database()->fetchVar("next", 0, 'SELECT MAX(`sortorder`) + 1 AS `next` FROM tbl_fields LIMIT 1');
				$fields['sortorder'] = ($next ? $next : '1');
			}

			$field_id = Symphony::Database()->insert('tbl_fields', $fields);

			if($field_id == 0 || !$field_id) return false;

			return $field_id;
		}

		public function edit($id, $fields){

			## Clean up if we are changing types
			/*$existing = $this->fetch($id);
			if($fields['type'] != $existing->handle()) {
				Symphony::Database()->query("DELETE FROM `tbl_fields_".$existing->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			}*/

			if(!Symphony::Database()->update("tbl_fields", $fields, array($id), "`id` = '%d'")) return false;

			return true;
		}

		public function delete($id){

			$existing = $this->fetch($id);

			Symphony::Database()->delete('tbl_fields', array($id), " `id` = '%d'");
			Symphony::Database()->delete('tbl_fields_'.$existing->handle(), array($id), " `field_id` = '%d'");
			Symphony::Database()->delete('tbl_sections_association', array($id), " `child_section_field_id` = '%d'");

			Symphony::Database()->query('DROP TABLE `tbl_entries_data_%d`', array($id));

			return true;
		}
	}
