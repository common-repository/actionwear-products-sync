<?php

namespace AC_SYNC\Includes\Classes\Sql {
	if (!defined('ABSPATH'))
		exit; // Exit if accessed directly
	use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;

	class Action_Wear_Sql_Versioning
	{
		const REGISTERED_VERSIONS = [
			'1.0.0' => '1_0_0',
			'1.0.1' => '1_0_1',
			'1.0.2' => '1_0_2',
		];

		public static function getClassName($version_class)
		{
			return "\AC_SYNC\Includes\Classes\Sql\Version\Action_Wear_Sql_Version_" . $version_class;
		}

		public static function getCurrentVersioningScript()
		{
			$current_version = get_option('_ACTIONWEAR_DB_VERSION');

			foreach (self::REGISTERED_VERSIONS as $version => $version_class) {
				if (version_compare($version, $current_version, "<="))
					continue;
				$class_name = self::getClassName($version_class);
				$v = new $class_name;
				try {
					$v::versioningScript();
					$v->updateVersionDb($version);
				} catch (\Throwable $th) {
					// log error to db
					Log::write("Error during DB upgrade, " . $th->getMessage(), Log::CRITICAL, Log::CONTEXT_GENERAL);
				}
			}
		}
	}
}
