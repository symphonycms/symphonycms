<?php
	
	class SectionsDataSource extends DataSource {
		public function canCountAssociatedEntries() {
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
		
		public function getLimit() {
			return 20;
		}
		
		public function getOutputParams() {
			return array();
		}
		
		public function getRequiredURLParam() {
			return '';
		}
		
		public function getRootElement() {
			return 'sections';
		}
		
		public function getSortField() {
			return 'system:id';
		}
		
		public function getSortOrder() {
			return 'desc';
		}
		
		public function getStartPage() {
			return 1;
		}
		
		public function getTemplate() {
			return 'sections';
		}
		
		public function grab() {
			throw new Exception('TODO: Fix sections datasource template.');
			
			
		}
	}
	
?>