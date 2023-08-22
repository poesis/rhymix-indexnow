<?php

namespace Rhymix\Modules\IndexNow\Models;

/**
 * 지원하는 검색엔진 목록을 담아둔 모델 클래스.
 *
 * 이런 종류의 데이터는 모델 클래스 상수나 static 메소드 등으로 만들어
 * 쉽게 끌어다 쓸 수 있도록 하는 것이 좋다.
 */
class SearchEngines
{
	/**
	 * 기본으로 활성화할 검색 엔진 목록.
	 */
	const DEFAULT_ENABLED = [
		'naver',
		'bing',
	];

	/**
	 * 지원하는 모든 검색 엔진 목록.
	 */
	const URLS = [
		'naver' => 'https://searchadvisor.naver.com/indexnow',
		'bing' => 'https://www.bing.com/indexnow',
		'seznam' => 'https://search.seznam.cz/indexnow',
		'yandex' => 'https://yandex.com/indexnow',
	];
}
