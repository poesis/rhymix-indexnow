<?php

namespace Rhymix\Modules\IndexNow\Controllers;

/**
 * 다른 네임스페이스에 있는 클래스나, 네임스페이스가 없는 클래스들은
 * use 문법으로 참조한다. 의미를 분명하게 하기 위해 이름을 변경할 수도 있다.
 * 같은 모듈 내의 모델 클래스는 컨트롤러 클래스와 이름이 중복되는 경우가 많으므로
 * 이름 뒤에 Model을 붙여 가져오는 것을 관례로 한다.
 */
use ModuleModel;
use Rhymix\Framework\HTTP;
use Rhymix\Modules\IndexNow\Models\Config as ConfigModel;
use Rhymix\Modules\IndexNow\Models\SearchEngines as SearchEnginesModel;

/**
 * 이벤트 핸들러(트리거)를 모아 놓은 클래스.
 *
 * 기존 XE 모듈에서는 controller에 트리거 함수를 넣는 것이 일반적이었으나,
 * 이제는 controller를 여러 개의 클래스로 자유롭게 나눌 수 있으므로
 * 이벤트를 받아서 처리하는 기능은 별도의 클래스에 모으는 것을 추천한다.
 *
 * 어떤 이벤트를 어느 클래스, 어느 메소드로 보낼지는
 * module.xml의 <eventHandlers> 섹션에서 정의하고
 * 관리자 대시보드에서 "모듈 설정 완료"를 클릭하면 자동 적용된다.
 * 일일이 addTrigger()를 호출할 필요가 없다.
 */
class EventHandlers extends Base
{
	/**
	 * 문서 작성/수정 후 트리거.
	 *
	 * @param object $obj
	 */
	public function afterInsertUpdateDocument($obj)
	{
		// 비밀글, 임시저장글 등은 전송하지 않는다.
		if ($obj->status !== 'PUBLIC')
		{
			return;
		}

		// 모듈 설정 상태를 확인한다.
		// 사용하지 않도록 되어 있거나, 선택한 검색엔진이 없다면 리턴한다.
		$config = ConfigModel::getConfig();
		if (!isset($config->use_module) || !$config->use_module || !count($config->search_engines))
		{
			return;
		}

		// 로봇이 접근할 수 없는 게시판이라면 리턴한다.
		$module_grants = ModuleModel::getModuleGrants($obj->module_srl)->data ?? [];
		foreach ($module_grants as $grant)
		{
			if (in_array($grant->name, ['access', 'view']) && $grant->group_srl != 0)
			{
				return;
			}
		}

		// 상담 게시판이라면 리턴한다.
		$module_info = ModuleModel::getModuleInfoByModuleSrl($obj->module_srl) ?? new \stdClass;
		if (isset($module_info->consultation) && $module_info->consultation === 'Y')
		{
			return;
		}

		// 요청 파라미터를 생성한다.
		$requests = [];
		$params = [
			'key' => $config->key,
			'url' => getNotEncodedFullUrl([
				'mid' => ModuleModel::getMidByModuleSrl($obj->module_srl),
				'document_srl' => $obj->document_srl,
			]),
		];

		// 라이믹스가 루트에 설치되지 않은 경우, keyLocation 파라미터를 추가한다.
		if (\RX_BASEURL !== '/')
		{
			$params['keyLocation'] = getFullUrl('') . $config->key . '.txt';
		}

		// 한 번에 묶어서 전송할 요청들을 생성한다.
		foreach ($config->search_engines as $name => $unused)
		{
			$requests[] = [
				'url' => SearchEnginesModel::URLS[$name],
				'data' => $params,
			];
		}

		// 선택한 검색엔진에 IndexNow 요청을 전송한다.
		// 여러 검색엔진에 순차적으로 요청하느라 시간이 오래 걸리지 않도록
		// HTTP::multiple() 기능을 사용하여 동시에 전송한다.
		try
		{
			HTTP::multiple($requests);
		}
		catch (\Exception $e)
		{
			// pass
		}
	}
}
