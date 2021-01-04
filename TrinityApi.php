<?php

namespace mackiavelly\trinitytv;

use Exception;
use yii\base\BaseObject;
use yii\helpers\Json;
use yii\httpclient\Client;

class TrinityApi extends BaseObject {

	const RESUME = 'resume';

	const SUSPEND = 'suspend';

	public static $action;

	public static $apiUrl = 'http://partners.trinity-tv.net/partners/user/';

	public static $requestParams;

	/**
	 * Debug mode
	 *
	 * @var string|bool
	 */
	public $debug;

	/**
	 * Базовые настройки для провайдера (выдаются при старте проекта)
	 * идентификатор партнера
	 *
	 * @var string
	 */
	public $partnerId;

	/**
	 * ключ для формирования запроса на авторизацию (выдаются при старте проекта)
	 *
	 * @var string
	 */
	public $salt;

	/**
	 * масив тарифных планов для пользователей (выдаются при старте проекта)
	 *
	 * @var array
	 */
	public $serviceId = [
		'id' => 'name',
	];

	/**
	 * Авторизация MAC/UUID устройства по коду
	 * В случе если пользоавтель не авторизован а на экране виджета он видет 4х значный код можно авторизовать мак его
	 * устройства по 4х значному коду Для авторизации устройств пользователей необходимо передать информацию о
	 * пользователе и коде в следующем формате: GET
	 * /partners/user/autorizebycode?requestid={requestid}&partnerid={partnerid}&localid={localid}&code={code}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Cтрока, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {localid} Cтрока , Идентификатор абонента в сети партнера. Числовое значение.
	 * {code}Строка, который видит абонент на экране своего телевизора. 4 символа в диапазоне от 0000 до 9999.
	 * {hash} Формируется md5 hash из строк requestid+partnerid+localid+code+salt . Salt выдается менеджером нашей
	 * компании В случае успешной авторизации по коду в ответе содержится Мак адрес устройства.
	 * {
	 * "requestid": "100",
	 * "result": "success",
	 * "mac": "112233445566"
	 * "uuid": "aaaaaabbbbbcccccddddd"
	 * }
	 * либо
	 * {
	 * "requestid":"100",
	 * "result":"wrongorexpiredcode"
	 * }
	 * Пример подключения устройства к аккаунту
	 * http://partners.trinity-tv.net/partners/user/autorizebycode?requestid=100&partnerid=73&localid=4&code=1122&hash=f1f45e4eeb809439941baa952815a311
	 *
	 * @param string $localId
	 * @param string $code
	 * @param string $note
	 *
	 * @return bool|mixed
	 */
	public function autorizeByCode($localId, $code, $note = '') {
		$this::$action = 'autorizebycode';
		$this::$requestParams = [
			'localid' => $localId,
			'code'    => $code,
			'note' => $note,
		];
		return $this->sendRequest();
	}

	/**
	 * Авторизация MAC/UUID устройства
	 * Для авторизации устройств пользователей необходимо передать информацию о пользователе и устройстве в следующем
	 * формате: GET
	 * /partners/user/autorizedevice?requestid={requestid}&partnerid={partnerid}&localid={localid}&mac={mac}&uuid={uuid}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Cтрока, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {localid} Cтрока , Идентификатор абонента в сети партнера. Числовое значение.
	 * {mac}Строка, mac конечного устройства абонента 12 символов в верхнем регистре присутствует не на всех устройствах
	 * {uuid}Строка, uuid уникальный идентификатор конечного устройства абонента присутствует не на всех устройствах.
	 * Обязательно присутствие одного из полей mac или uuid, возможно наличие обоих.
	 * {hash} Формируется md5 hash из строк requestid+partnerid+localid+mac+salt . Salt выдается менеджером нашей
	 * компании
	 * {
	 * "requestid": "12345",
	 * "result": "success"
	 * }
	 * В случае ошибки авторизации будет возвращен объект с соответствующим ошибке кодом, например:
	 * {
	 * "requestid":"100",
	 * "result":"wrongparameters"
	 * }
	 * Возможные ошибки в ответе:
	 * "wrongparameters"  - один из параметров не задан или задан неверно
	 * "parametersnotset"  - обязательный параметр не установлен
	 * "wronghash"    - неверный hash
	 * "wrongpartnerid"  - несуществующий или неверный partner id
	 * "wronglocalid"    - несуществующий или неверный local id
	 * "wrongmac"    - мак указан  неверно
	 * "dbconnerr"    - биллинг отверг запрос
	 * ВАЖНО!
	 * Если используете данный метод, запрещено использовать метод “Авторизация MAC/UUID устройства по коду” во избежания
	 * дублирования записей в базе, ввиду того, что эти методы друг друга исключают. Пример подключения устройства к
	 * аккаунту
	 * http://partners.trinity-tv.net/partners/user/autorizedevice?requestid=126&partnerid=1&localid=100&mac=a1b2c3a1b2c3&hash=572192f7f971cc1f5f8ee390991808e
	 *
	 * @param string $localId
	 * @param string $mac
	 * @param string $uuid
	 *
	 * @return bool|mixed
	 */
	public function autorizeDevice($localId, $mac = '', $uuid = '') {
		if (empty($mac) && empty($uuid)) {
			throw new Exception('Не указаны обязательные поля: mac или uuid');
		}
		$this::$action = 'autorizedevice';
		$this::$requestParams = [
			'localid' => $localId,
		];
		if (!empty($mac)) {
			if (preg_match('/^[0-9a-fA-F]{12}$/', trim($mac))) {
				$this::$requestParams += ['mac' => strtoupper(trim($mac))];
			} else {
				throw new Exception('Неправильный формат: mac');
			}
		}
		if (!empty($uuid)) {
			$this::$requestParams += ['uuid' => trim($uuid)];
		}
		return $this->sendRequest();
	}

	/**
	 * Авторизация MAC/UUID устройства с примечанием
	 * Для авторизации устройств пользователей с примечанием необходимо передать информацию о пользователе и устройстве в
	 * следующем формате: GET
	 * /partners/user/autorizedevice_note?requestid={requestid}&partnerid={partnerid}&localid={localid}&mac={mac}&uuid={uuid}&note={note}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Строка, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {localid} Строка , Идентификатор абонента в сети партнера. Числовое значение.
	 * {mac}Строка, mac конечного устройства абонента 12 символов в верхнем регистре присутствует не на всех устройствах
	 * {uuid}Строка, uuid уникальный идентификатор конечного устройства абонента присутствует не на всех устройствах.
	 * Обязательно присутствие одного из полей mac или uuid, возможно наличие обоих.
	 * {note} Строка, примечание для конечного устройства
	 * {hash} Формируется md5 hash из строк requestid+partnerid+localid+mac+salt . Salt выдается менеджером нашей компании
	 * {
	 * "requestid": "12345",
	 * "result": "success"
	 * }
	 * В случае ошибки авторизации будет возвращен объект с соответствующим ошибке кодом, например:
	 * {
	 * "requestid":"100",
	 * "result":"wrongparameters"
	 * }
	 * Возможные ошибки в ответе:
	 * "wrongparameters"  - один из параметров не задан или задан неверно
	 * "parametersnotset"  - обязательный параметр не установлен
	 * "wronghash"    - неверный hash
	 * "wrongpartnerid"  - несуществующий или неверный partner id
	 * "wronglocalid"    - несуществующий или неверный local id
	 * "wrongmac"    - мак указан  неверно
	 * "dbconnerr"    - биллинг отверг запрос
	 * ВАЖНО!
	 * Если используете данный метод, запрещено использовать метод “Авторизация MAC/UUID устройства по коду” во избежания
	 * дублирования записей в базе, ввиду того, что эти методы друг друга исключают.
	 *
	 * @param        $localId
	 * @param string $mac
	 * @param string $uuid
	 * @param string $note
	 *
	 * @return false
	 * @throws \Exception
	 */

	public function autorizeDeviceNote($localId, $mac = '', $uuid = '', $note = '') {
		if (empty($mac) && empty($uuid)) {
			throw new Exception('Не указаны обязательные поля: mac или uuid');
		}
		$this::$action = 'autorizedevice_note';
		$this::$requestParams = [
			'localid' => $localId,
		];
		if (!empty($mac)) {
			if (preg_match('/^[0-9a-fA-F]{12}$/', trim($mac))) {
				$this::$requestParams += ['mac' => strtoupper(trim($mac))];
			} else {
				throw new Exception('Неправильный формат: mac');
			}
		}
		if (!empty($uuid)) {
			$this::$requestParams += ['uuid' => trim($uuid)];
		}
		if (!empty($note)) {
			$this::$requestParams += ['note' => trim($note)];
		}
		return $this->sendRequest();
	}

	public function code($code) {
		$codeParams = [
			'updateuser'          => ['firstname', 'lastname', 'middlename', 'address'],
			/*'autorizebycode'      => ['note'],
			'autorizedevice_note' => ['note'],
			'updatenotebydevice'  => ['note'],*/
		];
		foreach ($codeParams as $action => $fields) {
			if ($this::$action == $action) {
				foreach ($this::$requestParams as $param => &$requestParam) {
					if (in_array($param, $fields)) {
						if ($code) {
							$requestParam = urlencode($requestParam);
						} else {
							$requestParam = urldecode($requestParam);
						}
					}
				}
			}
		}
	}

	/**
	 * Подключение услуги (подписки) пользователем на стороне Партнера
	 * На сайте партнера или в Личном кабинете пользователя на сайте Партнера в предусмотренном для этого разделе должны
	 * быть размещены: информация  о предлагаемых сервисе/услугах  TRINITY-TV краткая  инструкция по покупке и активации
	 * сервиса кнопка/ссылка  (например, «Подключить услугу») При нажатии пользователем кнопки/ссылки «Подключить
	 * услугу»: Партнер фиксирует  в своем биллинге факт оплаты доступа  к платному сервису (списывает средства  со счета
	 * пользователя) Партнер осуществляет  передачу в TRINITY-TV  со своего сервера уведомлений о  подключении сервиса
	 * абоненту: GET
	 * /partners/user/create?requestid={requestid}&partnerid={partnerid}&localid={localid}&subscrid={subscrid}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Cтрока, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {localid} Cтрока , Идентификатор абонента в сети партнера. Числовое значение.
	 * {subscrid}Строка, Числовой идентификатор тарифного плана, выдается менеджером нашей компании
	 * {hash} Формируется md5 hash из строк requestid+partnerid+localid+subscrid+salt . Salt выдается менеджером нашей
	 * компании Ответ в формате json при успешном выполнении запроса:
	 * {
	 * "requestid": "12345",
	 * "result": "success",
	 * "contracttrinity":396409
	 * }
	 * Пример URL на создание аккаунта
	 * http://partners.trinity-tv.net/partners/user/create?requestid=12345&partnerid=1&localid=100&subscrid=111&hash=bd6ee1e51bf3bcb7e940fe12f19c057f
	 * После получения  положительного ответа на это уведомление  от TRINITY-TV  - Партнер фиксирует у себя факт успешного
	 * подключения услуги абоненту. При этом кнопка/ссылка «Подключить услугу» в личном кабинете на сайте Партнера (или в
	 * другом месте на сайте Партнера) должна быть заменена на другую (например, «Информационный раздел»
	 * - может включать в себя информацию о тарифе, подключенные устройства).
	 *
	 * @param string $localId
	 * @param string $subscrId
	 *
	 * @return bool|mixed
	 */
	public function create($localId, $subscrId) {
		$this::$action = 'create';
		$this::$requestParams = [
			'localid'  => $localId,
			'subscrid' => $subscrId,
		];
		return $this->sendRequest();
	}

	public function createHash() {
		$hashParams = ['note' => 'delete'];
		$this->code(true);
		$md5 = md5(implode('', array_diff_key($this::$requestParams, $hashParams) + ['salt' => $this->salt]));
		$this->code(false);
		return $md5;
	}

	/**
	 * Деавторизация  MAC/UUID устройства
	 * Для деавторизации устройств пользователей необходимо передать информацию о пользователе и устройстве в следующем
	 * формате: GET
	 * /partners/user/deletedevice?requestid={requestid}&partnerid={partnerid}&localid={localid}&mac={mac}&uuid={uuid}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Cтрока, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {localid} Cтрока , Идентификатор абонента в сети партнера. Числовое значение.
	 * {mac}Строка, mac конечного устройства абонента 12 символов в верхнем регистре присутствует не на всех устройствах
	 * {uuid}Строка, uuid уникальный идентификатор конечного устройства абонента присутствует не на всех устройствах.
	 * {hash} Формируется md5 hash из строк requestid+partnerid+localid+mac +uuid+salt . Salt выдается менеджером нашей
	 * компании
	 * {
	 * "requestid": "12345",
	 * "result": "success"
	 * }
	 * Пример удаления MAC устройства пользователя из подписки
	 * http://partners.trinity-tv.net/partners/user/deletedevice?requestid=126&partnerid=1&localid=100&mac=a1b2c3a1b2c4&hash=5666938ac15179a3642bb3bec014e221
	 *
	 * @param string $localId
	 * @param string $mac
	 * @param string $uuid
	 *
	 * @return bool|mixed
	 */
	public function deleteDevice($localId, $mac = '', $uuid = '') {
		if (empty($mac) && empty($uuid)) {
			throw new Exception('Не указаны обязательные поля: mac или uuid');
		}
		$this::$action = 'deletedevice';
		$this::$requestParams = [
			'localid' => $localId,
		];
		if (!empty($mac)) {
			if (preg_match('/^[0-9a-fA-F]{12}$/', trim($mac))) {
				$this::$requestParams += ['mac' => strtoupper(trim($mac))];
			} else {
				throw new Exception('Неправильный формат: mac');
			}
		}
		if (!empty($uuid)) {
			$this::$requestParams += ['uuid' => trim($uuid)];
		}
		return $this->sendRequest();
	}

	/**
	 * Получение списка авторизованных MAC/UUID устройств
	 * GET/partners/user/devicelist?requestid={requestid}&partnerid={partnerid}&localid={localid}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Cтрока, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {localid} Cтрока , Идентификатор абонента в сети партнера. Числовое значение.
	 * {hash} Формируется md5 hash из строк requestid+partnerid+localid+salt . Salt выдается менеджером нашей компании
	 * http://partners.trinity-tv.net/partners/user/devicelist?requestid=8521502178981&partnerid=58&localid=852&hash=4935ad5fbb57ef4a05e3d108590f55f5
	 * {
	 * "requestid": "8521502178981",
	 * "result": "success",
	 * "devices": [{
	 * "mac": "0019DBA3FEBC",
	 * "uuid": “aabbccddeeddaa”
	 * }]
	 * }
	 *
	 * @param string $localId
	 *
	 * @return bool|mixed
	 */
	public function deviceList($localId) {
		$this::$action = 'devicelist';
		$this::$requestParams = [
			'localid' => $localId,
		];
		return $this->sendRequest();
	}

	/**
	 * Получение Playlist’а каналов
	 * GET/partners/user/getplaylist?requestid={requestid}&partnerid={partnerid}&localid={localid}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Cтрока, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {localid} Cтрока , Идентификатор абонента в сети партнера. Числовое значение.
	 * {hash} Формируется md5 hash из строк requestid+partnerid+localid+salt . Salt выдается менеджером нашей компании
	 * {
	 * "requestid": "8521502178981",
	 * "result": "success",
	 * "playlist": {
	 * "status": true,
	 * "uuid": "http://p1.sweet.tv/p/5talzcaeml.m3u8"
	 * }
	 *
	 * @param string $localId
	 *
	 * @return bool|mixed
	 */
	public function getPlayList($localId) {
		$this::$action = 'getplaylist';
		$this::$requestParams = [
			'localid' => $localId,
		];
		return $this->sendRequest();
	}

	/**
	 * Получение даты начала/старта последней сессии пользователей
	 * Для получения сессий необходимо передать информацию в следующем формате:
	 * GET /partners/user/getsessionsdate?requestid={requestid}&partnerid={partnerid}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Cтрока, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {hash} Формируется md5 hash из строк requestid+partnerid+salt . Salt выдается менеджером нашей компании
	 * Пример запроса на получение сессий пользователей:
	 * http://partners.trinity-tv.net/partners/user/getsessionsdate?requestid=126&partnerid=73&hash=46eb5d6204b9d0f474727061251f8b06
	 * {
	 * "requestid": "126",
	 * "result": "success",
	 * "sessions": [{
	 * "contract": 205445,
	 * "localid": 2445,
	 * "last_session_date": "2020-02-03 14:17:17"
	 * }, {
	 * "contract": 860901,
	 * "localid": 2446,
	 * "last_session_date": "2020-02-16 00:38:46"
	 * }]
	 * }
	 * В случае ошибки будет возвращен объект с соответствующим ошибке кодом, например:
	 * {
	 * "requestid":"100",
	 * "result":"wrongparameters"
	 * }
	 * Возможные ошибки в ответе:
	 * "wrongparameters"  - один из параметров не задан или задан неверно
	 * "parametersnotset"  - обязательный параметр не установлен
	 * "wronghash"    - неверный hash
	 * "wrongpartnerid"  - несуществующий или неверный partner id
	 *
	 * @return bool|mixed
	 */
	public function getSessionsDate() {
		$this::$action = 'getsessionsdate';
		$this::$requestParams = [];
		return $this->sendRequest();
	}

	public function init() {
		if (!$this->partnerId || !$this->salt) {
			throw new Exception('Не указаны обязательные поля: partnerId и salt');
		}
	}

	/**
	 * Получение отчета за прошлый месяц в JSON
	 * GET/partners/user/listreport?requestid={requestid}&partnerid={partnerid}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Cтрока, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {hash} Формируется md5 hash из строк requestid+partnerid+salt . Salt выдается менеджером нашей компании
	 * {
	 * "requestid": "4",
	 * "result": "success",
	 * "report": [
	 * "fio": " Тест Тест Тест",
	 * "contract": "205228",
	 * "contract_ott": "3",
	 * "mac_address": "70F3951A6B0F",
	 * "device": null,
	 * "date_last": null,
	 * "date_contract": "08/09/2016",
	 * "date_block": "",
	 * "tarif_caption": "\"Trinity-TV ОТТ Максимум+HD\" - 70 грн",
	 * "tarif_summa_trinity": "35",
	 * "tarif_summa_provider": "35",
	 * "is_blocked": "Действующий     ",
	 * "spis": "35",
	 * "spis_all": "70.00",
	 * "spis_provider": "35.00"
	 * }
	 *
	 * @return bool|mixed
	 */
	public function listReport() {
		$this::$action = 'listreport';
		$this::$requestParams = [];
		return $this->sendRequest();
	}

	/**
	 * Изменение Договора Партнера.
	 * Изменение договора производится передачей запроса вида:
	 * GET
	 * /partners/user/newcontract?requestid={requestid}&partnerid={partnerid}&localid={localid}&newcontract={newcontract}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Cтрока, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {localid} Cтрока, Идентификатор абонента в сети партнера. Числовое значение.
	 * {newcontract}Имя абонента кодированное  в urlencoded.
	 * {hash} Формируется md5 hash из строк requestid+partnerid+localid+newcontract+salt . Salt выдается менеджером нашей
	 * компании ВАЖНО MD5 берется из строки с символами urlencoded Для успешного выполнения операции требуется
	 * обязательное наличие всех параметров.
	 * http://partners.trinity-tv.net/partners/user/newcontract?requestid=571&partnerid=73&localid=1&newcontract=232323&hash=6914414849bf66de15326d4e38d318c7
	 * {
	 * "requestid": "571",
	 * "result": "success"
	 * }
	 * В случае ошибки будет возвращен объект с соответствующим ошибке кодом, например:
	 * {
	 * "requestid":"100",
	 * "result":"wrongparameters"
	 * }
	 * Возможные ошибки в ответе:
	 * "wrongparameters"  - один из параметров не задан или задан неверно
	 * "parametersnotset"  - обязательный параметр не установлен
	 * "wronghash"    - неверный hash
	 * "wrongpartnerid"  - несуществующий или неверный partner id
	 * "wronglocalid"    - несуществующий или неверный local id
	 * "dbconnerr"    - биллинг отверг запрос
	 *
	 * @param string $localId
	 * @param string $newContract
	 *
	 * @return bool|mixed
	 */
	public function newContract($localId, $newContract) {
		$this::$action = 'newcontract';
		$this::$requestParams = [
			'localid'     => $localId,
			'newcontract' => $newContract,
		];
		return $this->sendRequest();
	}

	public function sendRequest() {
		$this::$requestParams = [
				'requestid' => str_replace('.', '', microtime(true)),
				'partnerid' => $this->partnerId,
			] + $this::$requestParams;
		$this::$requestParams += ['hash' => $this->createHash()];
		$client = new Client(['baseUrl' => $this::$apiUrl]);
		$response = $client->get($this::$action, $this::$requestParams)->send();
		if ($response->isOk) {
			return Json::decode($response->getContent());
		}
		return false;
	}

	/**
	 * Получение списка пользователей и их статусов.
	 * GET/partners/user/subscriberlist?requestid={requestid}&partnerid={partnerid}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Cтрока, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {hash} Формируется md5 hash из строк requestid+partnerid+salt . Salt выдается менеджером нашей компании
	 * {
	 * "requestid": "11342",
	 * "result": "success",
	 * "subscribers": {
	 * "125": {
	 * "subscrid": 413,
	 * "subscrprice": 50,
	 * "subscrstatusid": 0
	 * },
	 * "600": {
	 * "subscrid": 413,
	 * "subscrprice": 50,
	 * "subscrstatusid": 0
	 * }
	 * }
	 * }
	 *
	 * @return bool|mixed
	 */
	public function subscriberList() {
		$this::$action = 'subscriberlist';
		$this::$requestParams = [];
		return $this->sendRequest();
	}

	/**
	 * Приостановление и восстановление услуги (подписки) Партнером
	 * При отсутствии на счету пользователя достаточных средств для оплаты дальнейшего доступа к услуге или при
	 * наступлении других событий (на усмотрение Партнера), которые приводят к невозможности дальнейшей оплаты
	 * пользователем услуг TRINITY-TV, Партнер осуществляет передачу в TRINITY-TV со своего сервера уведомления о
	 * приостановлении сервиса абоненту по ссылке типа GET
	 * /partners/user/subscription?requestid={requestid}&partnerid={partnerid}&localid={localid}&operationid={suspend}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Cтрока, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {localid} Cтрока , Идентификатор абонента в сети партнера. Числовое значение.
	 * {operationid} Строковое значение действия т с трифным планом абонента:
	 * Варианты operationid
	 * suspend - отключение подписки
	 * resume - продолжение отключенной подписки
	 * {hash} Формируется md5 hash из строк requestid+partnerid+localid+operationid+salt . Salt выдается менеджером нашей
	 * компании Ответ в формате json при успешном выполнении запроса:
	 * {
	 * "requestid": "12345",
	 * "result": "success"
	 * }
	 * Пример ссылки на приостановление услуги:
	 * http://partners.trinity-tv.net/partners/user/subscription?requestid=1&12345partnerid=1&localid=100&operationid=suspend&hash=f0eae71d787a0841bd535ec5a964f596
	 * После получения положительного ответа на это уведомление от TRINITY-TV - Партнер фиксирует у себя факт успешной
	 * блокировки услуги абоненту. При такой блокировке услуги пользователь не может воспользоваться сервисом TRINITY-TV, при
	 * этом услуга остается у него активированной (но заблокированной). После получения положительного ответа на это
	 * уведомление от TRINITY-TV - Партнер фиксирует у себя факт успешного восстановления услуги абоненту. Пользователь может
	 * продолжать пользоваться сервисом TRINITY-TV.
	 *
	 * @param string $localId
	 * @param string $operationId должен быть [[TrinityApi::SUSPEND]] или [[TrinityApi::RESUME]]
	 *
	 * @return bool|mixed
	 */
	public function subscription($localId, $operationId) {
		$this::$action = 'subscription';
		$this::$requestParams = [
			'localid'     => $localId,
			'operationid' => $operationId,
		];
		return $this->sendRequest();
	}

	/**
	 * Получение списка подписок пользователя.
	 * GET/partners/user/subscriptioninfo?requestid={requestid}&partnerid={partnerid}&localid={localid}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Cтрока, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {localid} Cтрока , Идентификатор абонента в сети партнера. Числовое значение.
	 * {hash} Формируется md5 hash из строк requestid+partnerid+localid+salt . Salt выдается менеджером нашей компании
	 * {
	 * "reqiestid": "12345",
	 * "result": "success",
	 * "subscriptions": {
	 * "subscrid": "20051",
	 * "subscrname": "DVcom TV Vip",
	 * "subscrprice": "25",
	 * "subscrstatus": "active",
	 * "contractott": "123456",
	 * "middlename": "Петров",
	 * "name":"Иван",
	 * "lastname": "Иванович",
	 * "address": "Киев",
	 * "contracttrinity": "654321",
	 * "balance": "0",
	 * "devicescount": "1",
	 * "contractdate": "2019-09-13",
	 * "last_session_date": "2019-09-13",
	 * "note": "Примечание"
	 * }
	 * }
	 * В случае ошибки выполнения будет возвращен объект с соответствующим ошибке кодом, например:
	 * {
	 * "requestid":"12345",
	 * "result":"wrongparameters"
	 * }
	 * Возможные ошибки в ответе:
	 * "wrongparameters"  - один из параметров не задан или задан неверно
	 * "parametersnotset"  - обязательный параметр не установлен
	 * "wronghash"    - неверный hash
	 * "wrongpartnerid"  - несуществующий или неверный partner id
	 * "wronglocalid"    - несуществующий или неверный local id
	 * "dbconnerr"    - биллинг отверг запрос
	 *
	 * @param string $localId
	 *
	 * @return bool|mixed
	 */
	public function subscriptionInfo($localId) {
		$this::$action = 'subscriptioninfo';
		$this::$requestParams = [
			'localid' => $localId,
		];
		return $this->sendRequest();
	}

	/**
	 * Изменение примечания у уже привязанных устройств
	 * Изменение примечания у привязанных устройств производится передачей запроса вида:
	 * GET
	 * /partners/user/updatenotebydevice?requestid={requestid}&partnerid={partnerid}&deviceid={deviceid}&note={note}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Строка, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {deviceid} Строка, идентификатор устройства, примечание которого планируется редактировать, получить можно выполнив
	 * запрос на получение авторизованных устройств из поля "device_id" соответствующего устройства
	 * {note} Строка, новое примечание.
	 * {hash} Формируется md5 hash из строк requestid+partnerid+deviceid+salt . Salt выдается менеджером нашей компании
	 * {
	 * "requestid": "12345",
	 * "result": "success"
	 * }
	 * В случае ошибки будет возвращен объект с соответствующим ошибке кодом, например:
	 * {
	 * "requestid":"100",
	 * "result":"wrongparameters"
	 * }
	 * Возможные ошибки в ответе:
	 * "wrongparameters"    - один из параметров не задан или задан неверно
	 * "parametersnotset"    - обязательный параметр не установлен
	 * "wronghash"      - неверный hash
	 * "wrongpartnerid"    - несуществующий или неверный partner id
	 * "unexpected_termination"  - в случае получения ошибки, связаться с менеджером
	 *
	 * @param        $deviceId
	 * @param string $note
	 *
	 * @return null|false|mixed
	 */
	public function updateNoteByDevice($deviceId, $note = '') {
		$this::$action = 'updatenotebydevice';
		$this::$requestParams = [
			'deviceid' => $deviceId,
			'note'     => trim($note),
		];
		return $this->sendRequest();
	}

	/**
	 * Изменение Данных пользователя.
	 * Изменение данных пользователя: ФИО и Адрес производится передачей запроса вида:
	 * GET
	 * /partners/user/updateuser?requestid={requestid}&partnerid={partnerid}&localid={localid}firstname={partnerid}&lastname={lastname}&middlename={middlename}&address={address}&hash={hash}
	 * {requestid} ID запроса.Уникальный числовой идентификатор запроса - любое уникальное число генерируемое на стороне
	 * партнера .
	 * {partnerid} Cтрока, обязательный идентификатор партнера.Числовое значение. Выдается менеджером нашей компании
	 * {localid} Cтрока , Идентификатор абонента в сети партнера. Числовое значение.
	 * {firstname}Имя абонента кодированное  в urlnencoded.
	 * {lastname}Фамилия абонента кодированная  в urlnencoded.
	 * {middlename}Отчество абонента кодированное  в urlnencoded.
	 * {address}Адрес абонента кодированное  в urlnencoded.
	 * {hash} Формируется md5 hash из строк requestid+partnerid+localid+firstname+lastname+middlename+address+salt . Salt
	 * выдается менеджером нашей компании ВАЖНО MD5 берется из строки с символами urlnencoded Для успешного выполнения
	 * операции требуется обязательное наличие всех параметров.
	 * {
	 * "requestid": "100",
	 * "result": "success"
	 * }
	 * http://partners.trinity-tv.net/partners/user/updateuser?requestid=100&partnerid=73&localid=4&firstname=%D0%A2%D0%B5%D1%81%D1%82&lastname=%D0%A2%D0%B5%D1%81%D1%82&middlename=%D0%A2%D0%B5%D1%81%D1%82&address=%D0%A2%D0%B5%D1%81%D1%82&hash=40069011fd25a1afa3122e0cee67bb6d
	 *
	 * @param string $localId
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $middleName
	 * @param string $address
	 *
	 * @return bool|mixed
	 */
	public function updateUser($localId, $firstName = '-', $lastName = '-', $middleName = '-', $address = '-') {
		if (empty($firstName) || empty($lastName) || empty($middleName) || empty($address)) {
			throw new Exception('Не указаны обязательные поля: firstname и lastname и middlename и address');
		}
		$this::$action = 'updateuser';
		$this::$requestParams = [
			'localid'    => $localId,
			'firstname'  => trim($firstName),
			'lastname'   => trim($lastName),
			'middlename' => trim($middleName),
			'address'    => trim($address),
		];
		return $this->sendRequest();
	}


}
