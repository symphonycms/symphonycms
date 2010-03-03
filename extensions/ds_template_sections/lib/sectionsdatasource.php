<?php
	
	class SectionsDataSource extends DataSource {
		public function canAppendAssociatedEntryCount() {
			return false;
		}
		
		public function canAppendPagination() {
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
	}
	
?>