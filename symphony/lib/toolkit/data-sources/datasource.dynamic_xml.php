<?php

	$dynamicXML = new DynamicXMLDatasource(array(), false);

	$classParams = get_object_vars($this);
	foreach($classParams as $key => $value) {
		$dynamicXML->$key = $value;
	}

	$result = $dynamicXML->execute($param_pool);

	return $result;