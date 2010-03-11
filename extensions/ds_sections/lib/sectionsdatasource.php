<?php
	
	Abstract Class SectionsDataSource extends DataSource {
		public function canAppendAssociatedEntryCount() {
			return false;
		}
		
		public function canAppendPagination() {
			return false;
		}
		
		public function canHTMLEncodeText() {
			return false;
		}
		
		public function canRedirectOnEmpty() {
			return false;
		}
		
		public function getFilters() {
			return array();
		}
		
		public function getGroupField() {
			return '';
		}
		
		public function getIncludedElements() {
			return array();
		}
		
		public function getOutputParams() {
			return array();
		}
		
		public function getPaginationLimit() {
			return '20';
		}
		
		public function getPaginationPage() {
			return '1';
		}
		
		public function getRequiredURLParam() {
			return '';
		}
		
		public function getRootElement() {
			return 'sections';
		}
		
		public function getSection() {
			return null;
		}
		
		public function getSortField() {
			return 'system:id';
		}
		
		public function getSortOrder() {
			return 'desc';
		}
		
		public function getTemplate() {
			return 'sections';
		}
		
		public function grab() {
			throw new Exception('TODO: Fix sections datasource template.');
		}
		
		public function prepareSourceColumnValue(){
			$section = SectionManager::instance()->fetch($this->getSection());
		
			if ($section instanceof Section) {
				$section = $section->_data;
				return Widget::TableData(Widget::Anchor(
					$section['name'],
					URL . '/symphony/blueprints/sections/edit/' . $section['id'] . '/',
					$section['handle']
				));
			}
		
			else {
				return Widget::TableData(__('None'), 'inactive');
			}
		}
	}
