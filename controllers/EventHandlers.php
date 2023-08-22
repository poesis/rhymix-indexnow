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

		// 설정 상태를 확인한다.
		$config = ConfigModel::getConfig();
		if (!isset($config->use_module) || !$config->use_module || !count($config->search_engines))
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
		foreach ($config->search_engines as $name => $unused)
		{
			$requests[] = [
				'url' => SearchEnginesModel::URLS[$name],
				'data' => $params,
			];
		}

		// 선택한 검색엔진에 IndexNow 요청을 전송한다.
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
