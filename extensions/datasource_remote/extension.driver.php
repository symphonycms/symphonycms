<?php

	require_once EXTENSIONS . '/datasource_remote/data-sources/datasource.remote.php';

	Class Extension_Datasource_Remote extends Extension {

		private static $provides = array();

		public static function registerProviders() {
			if(!empty(self::$providers)) return;

			self::$provides = array(
				'data-sources' => array(
					'DatasourceRemote' => DatasourceRemote::getName()
				)
			);
		}

		public static function providerOf($type = null) {
			self::registerProviders();

			if(is_null($type)) return self::$provides;

			if(!isset(self::$provides[$type])) return array();

			return self::$provides[$type];
		}

	}
