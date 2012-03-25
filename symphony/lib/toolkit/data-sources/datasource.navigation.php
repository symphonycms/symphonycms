<?php

	$navigationDS = new NavigationDatasource(array(), false);

	$classParams = get_object_vars($this);
	foreach($classParams as $key => $value) {
		$navigationDS->$key = $value;
	}

	$result = $navigationDS->execute($param_pool);

	return $result;
