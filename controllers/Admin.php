<?php

namespace Rhymix\Modules\IndexNow\Controllers;

/**
 * 다른 네임스페이스에 있는 클래스나, 네임스페이스가 없는 클래스들은
 * use 문법으로 참조한다. 의미를 분명하게 하기 위해 이름을 변경할 수도 있다.
 * 같은 모듈 내의 모델 클래스는 컨트롤러 클래스와 이름이 중복되는 경우가 많으므로
 * 이름 뒤에 Model을 붙여 가져오는 것을 관례로 한다.
 */
use Context;
use Rhymix\Framework\Security;
use Rhymix\Framework\Storage;
use Rhymix\Modules\IndexNow\Models\Config as ConfigModel;
use Rhymix\Modules\IndexNow\Models\SearchEngines as SearchEnginesModel;

/**
 * 관리자 화면 및 설정 관리를 담당하는 컨트롤러.
 *
 * 기존 XE 모듈에서는 뷰컨트롤러(view controller)의 역할을 view 클래스가 갖고
 * controller 클래스는 폼 제출 등 POST 액션만 주로 처리했으나,
 * 네임스페이스 구조 도입으로 컨트롤러를 여러 클래스로 분리할 수 있게 되었으므로
 * 이제는 서로 연관된 기능들(폼 화면 표시 + 해당 폼 제출 처리)을
 * GET, POST 무관하게 동일한 컨트롤러에서 처리하는 것을 원칙으로 한다.
 *
 * 이 때, view는 템플릿만을 의미한다.
 * 관리자 화면 등 스킨으로 분리할 필요 없는 템플릿을 views 폴더에 넣는다.
 * 과거의 tpl 폴더와 같은 역할이다.
 *
 * 여기까지 하면 다른 프레임워크들의 MVC 구조와 사실상 동일하게 된다.
 */
class Admin extends Base
{
	/**
	 * 관리자 설정 화면
	 */
	public function dispIndexnowAdminConfig()
	{
		// 현재 설정 상태 불러오기
		$config = ConfigModel::getConfig();
		Context::set('config', $config);

		// 키가 설정되지 않은 경우 지금 생성
		if (!isset($config->key))
		{
			$config->key = Security::getRandom(32, 'hex');
			ConfigModel::setConfig($config);
		}

		// 키 파일 경로 및 내용 확인하기
		$keyfile_url = getFullUrl('') . $config->key . '.txt';
		$keyfile_path = \RX_BASEDIR . $config->key . '.txt';
		$keyfile_exists = Storage::exists($keyfile_path);
		if (!$keyfile_exists && Storage::isWritable(\RX_BASEDIR))
		{
			Storage::write($keyfile_path, $config->key . PHP_EOL);
			clearstatcache(true, $keyfile_path);
			$keyfile_exists = Storage::exists($keyfile_path);
		}
		$keyfile_content_check = ($keyfile_exists && trim(Storage::read($keyfile_path)) === $config->key);
		Context::set('keyfile_url', $keyfile_url);
		Context::set('keyfile_exists', $keyfile_exists);
		Context::set('keyfile_content_check', $keyfile_content_check);

		// 스킨 파일 지정
		$this->setTemplatePath($this->module_path . 'views/admin/');
		$this->setTemplateFile('config');
	}

	/**
	 * 관리자 설정 저장 액션
	 */
	public function procIndexnowAdminInsertConfig()
	{
		// 현재 설정 상태 불러오기
		$config = ConfigModel::getConfig();

		// 키가 설정되지 않은 경우 지금 생성
		if (!isset($config->key))
		{
			$config->key = Security::getRandom(32, 'hex');
		}

		// 제출받은 데이터 불러오기
		$vars = Context::getRequestVars();
		$config->use_module = ($vars->use_module === 'Y');
		$config->search_engines = [];
		foreach ($vars->search_engines as $name)
		{
			if (isset(SearchEnginesModel::URLS[$name]))
			{
				$config->search_engines[$name] = true;
			}
		}

		// 키 파일 재생성 시도
		$keyfile_path = \RX_BASEDIR . $config->key . '.txt';
		if (!Storage::exists($keyfile_path) || trim(Storage::read($keyfile_path)) !== $config->key)
		{
			Storage::write($keyfile_path, $config->key . PHP_EOL);
			clearstatcache(true, $keyfile_path);
		}

		// 변경된 설정을 저장
		$output = ConfigModel::setConfig($config);
		if (!$output->toBool())
		{
			return $output;
		}

		// 설정 화면으로 리다이렉트
		$this->setMessage('success_registed');
		$this->setRedirectUrl(Context::get('success_return_url'));
	}
}
