<?php

	Class SectionsDataSource extends DataSource {

		public function __construct(){
			// Set Default Values
			$this->_about = new StdClass;
			$this->_parameters = (object)array(
			   'root-element' => NULL,
			   'limit' => 20,
			   'page' => 1,
			   'section' => NULL,
			   'conditions' => array(),
			   'filter' => array(),
			   'redirect-404-on-empty' => false,
			   'append-pagination' => false,
			   'append-associated-entry-count' => false,
			   'html-encode' => false,
			   'sort-field' => 'system:id',
			   'sort-order' => 'desc',
			   'included-elements' => array(),
			   'parameter-output' => array(),
			);
		}

		public function save(MessageStack &$errors){

			if (strlen(trim($this->parameters()->limit)) == 0 || (is_numeric($this->parameters()->limit) && $this->parameters()->limit < 1)) {
				$errors->append('limit', __('A result limit must be set'));
			}

			if (strlen(trim($this->parameters()->page)) == 0 || (is_numeric($this->parameters()->page) && $this->parameters()->page < 1)) {
				$errors->append('page', __('A page number must be set'));
			}

			return parent::save($errors);
		}

		/*public function canAppendAssociatedEntryCount() {
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
		}*/

		final public function type(){
			return 'ds_sections';
		}

		public function template(){
			return EXTENSIONS . '/ds_sections/templates/datasource.php';
		}

		public function render(Register &$ParameterOutput){
			$doc = new XMLDocument;
			$doc->appendChild($doc->createElement($this->parameters()->{'root-element'}));
			
			$ParameterOutput->{'ds-fred-title'} = "I am the most awesome";
			
			return $doc;
		}


		public function prepareSourceColumnValue(){
			$section = Section::loadFromHandle($this->_parameters->section);

			if ($section instanceof Section) {
				return Widget::TableData(
					Widget::Anchor($section->name, URL . '/symphony/blueprints/sections/edit/' . $section->handle . '/', array(
						'title' => $section->handle
					))
				);
			}

			else {
				return Widget::TableData(__('None'), 'inactive');
			}
		}
	}
