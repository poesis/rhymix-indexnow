<?php

namespace Rhymix\Modules\IndexNow\Models;

use ModuleController;
use ModuleModel;

/**
 * 모듈 설정 관리를 위한 Model 클래스.
 *
 * 설정 외에도 데이터를 불러오거나 저장하는 모든 코드는
 * model이 담당하는 것을 원칙으로 한다.
 *
 * 기존 XE 모듈 구조에서는 select만 model에서 하고
 * insert, update, delete는 controller가 담당하였다.
 * 이것은 controller가 비대해지는 가장 큰 원인이다.
 *
 * 대부분의 유명 프레임워크들이 사용하는 MVC 구조에서
 * 모든 요청은 일단 controller가 받아 인증과 유효성 검증 등을 거치고,
 * DB 조작은 select, insert, update, delete 모두 model에서 하는 것이 맞다.
 * ORM을 사용하는 프레임워크는 말할 것도 없고, 그렇지 않은 경우에도 마찬가지이다.
 */
class Config
{
	/**
	 * 모듈 설정 캐시를 위한 변수.
	 */
	protected static $_cache = null;

	/**
	 * 모듈 설정을 가져오는 함수.
	 *
	 * 캐시 처리되기 때문에 ModuleModel을 직접 호출하는 것보다 효율적이다.
	 * 모듈 내에서 설정을 불러올 때는 이 함수를 사용하도록 한다.
	 *
	 * @return object
	 */
	public static function getConfig()
	{
		if (self::$_cache === null)
		{
			self::$_cache = ModuleModel::getModuleConfig('indexnow') ?: new \stdClass;
		}
		return self::$_cache;
	}

	/**
	 * 모듈 설정을 저장하는 함수.
	 *
	 * 설정을 변경할 필요가 있을 때 ModuleController를 직접 호출하지 말고 이 함수를 사용한다.
	 * getConfig()으로 가져온 설정을 적절히 변경하여 setConfig()으로 다시 저장하는 것이 정석.
	 *
	 * @param object $config
	 * @return object
	 */
	public static function setConfig($config)
	{
		$oModuleController = ModuleController::getInstance();
		$output = $oModuleController->insertModuleConfig('indexnow', $config);
		if ($output->toBool())
		{
			self::$_cache = $config;
		}
		return $output;
	}
}
