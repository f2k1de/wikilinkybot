<?php
class Telegram {

	const ACTION_TYPING = 'typing';
	const ACTION_UPLOAD_PHOTO = 'upload_photo';
	const ACTION_RECORD_VIDEO = 'record_video';
	const ACTION_UPLOAD_VIDEO = 'upload_video';
	const ACTION_RECORD_AUDIO = 'record_audio';
	const ACTION_UPLOAD_AUDIO = 'upload_audio';
	const ACTION_UPLOAD_DOC = 'upload_document';
	const ACTION_FIND_LOCATION = 'find_location';

	const BOT_URL = 'https://api.telegram.org/bot';

	const ADMIN_NOTHING = 0;
	const ADMIN_CHAT = 1;
	const ADMIN_GLOBAL = 2;
	const ADMIN_SUPER = 3;

	private $webhook = false;

	private $available_commands = [
		'getMe',
		'sendMessage',
		'forwardMessage',
		'sendPhoto',
		'sendAudio',
		'sendDocument',
		'sendSticker',
		'sendVideo',
		'sendLocation',
		'sendChatAction',
		'getUserProfilePhotos',
		'getUpdates',
		'setWebhook',
		'getFile'
	];

	private $adminList = [];

	private $apiKey = "";

	private $botName = "";

	private $messages = [];

	private $chatID;
	private $userID;
	private $firstName;
	private $lastName;
	private $userName;
	private $chatType;
	private $text;
	private $messageID;

	function __construct($apiKey, $botName) {
		$this->apiKey = $apiKey;
		$this->botName = $botName;

		$input = file_get_contents('php://input');
		if (!empty($input)) {
			$this->parseWebhook($input);

			//Load permissions
			if (!file_exists("data/permissions.json")) {
				file_put_contents("data/permissions.json", "[]");
			}
			$this->adminList = json_decode(file_get_contents("data/permissions.json"), true);
		}
	}

	public function webhookAnswer() {
		if (empty($this->messages) || empty($this->messages[0])) {
			return "{}";
		}
		return json_encode(array_merge($this->messages[0]["param"], ["method" => $this->messages[0]["method"]]));
	}

	public function answer() {
		if ($this->webhook) {
			unset($this->messages[0]);
		}
		if (count($this->messages) >= 1) {
			return $this->execute();
		}
	}

	public function sendMessage($text, $chatID = null, $useMarkdown = true, $disableWebpagePreview = false, $replyToMessageID = null) {
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n", 'error.log');
				return false;
			}
		}

		if (strtolower($useMarkdown) == "markdown" || $useMarkdown == true || $useMarkdown == 1) {
			$useMarkdown = "Markdown";
		} else {
			$useMarkdown = "";
		}

		$this->messages[] = [
			"method" => "sendMessage",
			"param" => [
				"chat_id" => $chatID,
				"text" => $text,
				"parse_mode" => $useMarkdown,
				"disable_web_page_preview" => $disableWebpagePreview,
				"reply_to_message_id" => $replyToMessageID
			]
		];
	}

	public function forwardMessage($fromChatID, $messageID, $chatID) {
		$this->messages[] = [
			"method" => "forwardMessage",
			"param" => [
				"chat_id" => $chatID,
				"from_chat_id" => $fromChatID,
				"message_id" => $messageID
			]
		];
	}

	public function sendPhoto($photo, $chatID = null, $caption = null, $replyToMessageID = null) {
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n", 'error.log');
				return false;
			}
		}

		if ($this->webhook) {
			$this->messages[] = null;
		}

		$this->sendChatAction(self::ACTION_UPLOAD_PHOTO);
		$this->messages[] = [
			"method" => "sendPhoto",
			"param" => [
				"chat_id" => $chatID,
				'photo' => $photo,
				'caption' => $caption,
				'reply_to_message_id' => $replyToMessageID
			]
		];
	}

	public function sendDocument($document, $chatID = null, $replyToMessageID = null) {
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n", 'error.log');
				return false;
			}
		}

		if ($this->webhook) {
			$this->messages[] = null;
		}

		$this->sendChatAction(self::ACTION_UPLOAD_DOC);
		$this->messages[] = [
			"method" => "sendDocument",
			"param" => [
				"chat_id" => $chatID,
				'document' => $document,
				'reply_to_message_id' => $replyToMessageID
			]
		];
	}

	public function sendAudio($audio, $chatID = null, $duration = null, $performer = null, $title = null, $replyToMessageID = null)	{
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n", 'error.log');
				return false;
			}
		}

		if ($this->webhook) {
			$this->messages[] = null;
		}

		$this->sendChatAction(self::ACTION_UPLOAD_AUDIO);
		$this->messages[] = [
			"method" => "sendAudio",
			"param" => [
				"chat_id" => $chatID,
				'audio' => $audio,
				'duration' => $duration,
				'performer' => $performer,
				'title' => $title,
				'reply_to_message_id' => $replyToMessageID
			]
		];
	}

	public function sendVoice($voice, $chatID = null, $duration = null, $replyToMessageID = null)	{
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n", 'error.log');
				return false;
			}
		}

		if ($this->webhook) {
			$this->messages[] = null;
		}

		$this->sendChatAction(self::ACTION_RECORD_AUDIO);
		$this->messages[] = [
			"method" => "sendVoice",
			"param" => [
				"chat_id" => $chatID,
				'voice' => $voice,
				'duration' => $duration,
				'reply_to_message_id' => $replyToMessageID
			]
		];
	}

	public function sendVideo($video, $chatID = null, $replyToMessageID = null) {
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n", 'error.log');
				return false;
			}
		}

		if ($this->webhook) {
			$this->messages[] = null;
		}

		$this->sendChatAction(self::ACTION_UPLOAD_VIDEO);
		$this->messages[] = [
			"method" => "sendVideo",
			"param" => [
				"chat_id" => $chatID,
				'video' => $video,
				'reply_to_message_id' => $replyToMessageID
			]
		];
	}

	public function sendLocation($latitude, $longitude, $chatID = null, $replyToMessageID = null)	{
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n", 'error.log');
				return false;
			}
		}

		if ($this->webhook) {
			$this->messages[] = null;
		}

		$this->sendChatAction(self::ACTION_FIND_LOCATION);
		$this->messages[] = [
			"method" => "sendLocation",
			"param" => [
				"chat_id" => $chatID,
				'latitude' => $latitude,
				'longitude' => $longitude,
				/*'reply_to_message_id' => $replyToMessageID*/
			]
		];
	}

	public function sendChatAction($type, $chatID = null) {
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n", 'error.log');
				return false;
			}
		}
		if ($this->webhook && empty($this->messages)) {
			return;
		}

		$this->messages[] = [
			"method" => "sendChatAction",
			"param" => [
				"chat_id" => $chatID,
				"action" => $type
			]
		];
	}

	public function getText() {
		if (!empty($this->text)) {
			return $this->text;
		}
		return false;
	}

	public function getChatID() {
		if (!empty($this->chatID)) {
			return intval($this->chatID);
		}
		return false;
	}

	public function getMessageID() {
		if (!empty($this->messageID)) {
			return intval($this->messageID);
		}
		return false;
	}

	public function getUserID() {
		if (!empty($this->userID)) {
			return intval($this->userID);
		}
		return false;
	}

	public function getUserName() {
		if (!empty($this->userName)) {
			return $this->userName;
		}
		return false;
	}

	public function getChatType() {
		if (!empty($this->chatType)) {
			return $this->chatType;
		}
		return false;
	}

	public function getCommand($delimiter = " ") {
		$fragment = explode($delimiter, $this->text);
		$base = str_replace('@'.$this->botName, "", $fragment[0]);
		unset($fragment[0]);
		return [ "command" => $base, "args" => array_values($fragment) ];
	}

	public function getPermission($userID = null, $chatID = null) {
		if ($userID == null) {
			if ($this->userID != null) {
				$userID = $this->userID;
			} else {
				error_log("[TELEGRAM] No userID given. Dropping message.\n", 'error.log');
				return false;
			}
		}
		if ($chatID == null) {
			if ($this->chatID != null) {
				$chatID = $this->chatID;
			} else {
				error_log("[TELEGRAM] No chatID given. Dropping message.\n", 'error.log');
				return false;
			}
		}

		if (!isset($this->adminList[$userID])) {
			return self::ADMIN_NOTHING;
		}
		if ($this->adminList[$userID] == self::ADMIN_SUPER) {
			return self::ADMIN_SUPER;
		}
		if ($this->adminList[$userID] == self::ADMIN_GLOBAL) {
			return self::ADMIN_GLOBAL;
		}
		if (!isset($this->adminList[$userID][$chatID])) {
			return self::ADMIN_NOTHING;
		} else {
			return $this->adminList[$userID][$chatID];
		}
	}

	private function execute() {
		foreach ($this->messages as $message) {

			if ($message["method"] == "sendPhoto") {
				$message["param"]["photo"] = new CURLFile(realpath($message["param"]["photo"]));
			} else if ($message["method"] == "sendDocument") {
				$message["param"]["document"] = new CURLFile(realpath($message["param"]["document"]));
			}

			$handle = curl_init();
			curl_setopt($handle, CURLOPT_URL, self::BOT_URL . $this->apiKey . '/' . $message["method"]);

			if ($message["method"] == "sendPhoto" || $message["method"] == "sendDocument") {
				curl_setopt($handle, CURLOPT_HTTPHEADER, [ "Content-Type:multipart/form-data" ]);
			}

			curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($handle, CURLOPT_POST, count($message["param"]));
			curl_setopt($handle, CURLOPT_POSTFIELDS, $message["param"]);
			$result = curl_exec($handle);

			if ($result === false) {
				$errno = curl_errno($handle);
				$error = curl_error($handle);
			}
			$http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
			curl_close($handle);

			if ($http_code >= 500) {
				//Prevent DDoS of telegram servers
				sleep(5);
			} else if ($http_code != 200) {
				error_log("[TELEGRAM] Request failed\n" . print_r($result, true) . "\n\n", 'error.log');
			}
		}
	}

	private function parseWebhook($input) {
		$message = json_decode($input, true);
		if (!empty($message)) {
			$this->webhook = true;

			$this->chatID = $message["message"]["chat"]["id"];
			$this->userID = $message["message"]["from"]["id"];
			if (isset($message["message"]["from"]["first_name"])) {
				$this->firstName = $message["message"]["from"]["first_name"];
			}
			if (isset($message["message"]["from"]["last_name"])) {
				$this->lastName = $message["message"]["from"]["last_name"];
			}
			if (isset($message["message"]["from"]["username"])) {
				$this->userName = $message["message"]["from"]["username"];
			}
//			$this->chatType = $message["message"]["chat"]["type"];
			if (isset($message["message"]["text"])) {
				$this->text = $message["message"]["text"];
			} else {
				$this->text = "";
			}
			$this->messageID = $message["message"]["message_id"];
		}
	}
}
