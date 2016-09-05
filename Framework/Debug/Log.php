<?php
namespace Framework\Debug;

class Log
{
	public static $file = 'tmp/logs/log.txt';

	public static function write($message)
	{
		/**
		 * FILE_USE_INCLUDE_PATH - Ищет filename в подключаемых директориях. Подробнее смотрите директиву include_path.
		 * FILE_APPEND - Если файл filename уже существует, данные будут дописаны в конец файла вместо того, чтобы его перезаписать.
		 * LOCK_EX - Получить эксклюзивную блокировку на файл на время записи
		 */
		return file_put_contents(ABS . self::$file, $message, FILE_APPEND);
	}
}