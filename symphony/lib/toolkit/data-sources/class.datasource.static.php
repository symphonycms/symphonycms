<?php

	Class StaticDatasource extends Datasource{

		public function execute() {
			include_once(TOOLKIT . '/class.xsltprocess.php');

			$this->dsParamSTATIC = stripslashes($this->dsParamSTATIC);

			if(!General::validateXML($this->dsParamSTATIC, $errors, false, new XsltProcess)) {
				$result->appendChild(
					new XMLElement('error', __('XML is invalid.'))
				);

				$element = new XMLElement('errors');
				foreach($errors as $e) {
					if(strlen(trim($e['message'])) == 0) continue;
					$element->appendChild(new XMLElement('item', General::sanitize($e['message'])));
				}
				$result->appendChild($element);
			}
			else {
				$result->setValue($this->dsParamSTATIC);
			}

			return $result;
		}
	}

?>