<?php

	$authorDS = new AuthorDatasource(array(), false);

	$classParams = get_object_vars($this);
	foreach($classParams as $key => $value) {
		$authorDS->$key = $value;
	}

	$result = $authorDS->execute($param_pool);

	return $result;
