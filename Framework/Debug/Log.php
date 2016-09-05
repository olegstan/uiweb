<?php
namespace Framework\Debug;

class Log
{
	public static $file = 'tmp/logs/log.txt';

	public static function write($message)
	{
		/**
		 * FILE_USE_INCLUDE_PATH - ���� filename � ������������ �����������. ��������� �������� ��������� include_path.
		 * FILE_APPEND - ���� ���� filename ��� ����������, ������ ����� �������� � ����� ����� ������ ����, ����� ��� ������������.
		 * LOCK_EX - �������� ������������ ���������� �� ���� �� ����� ������
		 */
		return file_put_contents(ABS . self::$file, $message, FILE_APPEND);
	}
}