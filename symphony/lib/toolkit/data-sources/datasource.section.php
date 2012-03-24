<?php

	$sectionDS = new SectionDatasource(array(), false);
	$sectionDS->setSource($this->getSource());

	$classParams = get_object_vars($this);
	foreach($classParams as $key => $value) {
		$sectionDS->$key = $value;
	}

	$result = $sectionDS->execute($param_pool);

	return $result;