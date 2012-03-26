<?php

	$staticXML = new StaticXMLDatasource(array(), false);
	$staticXML->dsParamROOTELEMENT = $this->dsParamROOTELEMENT;
	$staticXML->dsParamSTATIC = $this->dsSTATIC;

	$result = $staticXML->execute($param_pool);

	return $result;
