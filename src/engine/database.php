<?php

	function database_connect()
	{
		//Данные для подключения к базе данных
		$hostname = 'database_host';
		$database = 'database_name';
		$username = 'database_user';
		$password = 'database_password';
		$encoding = 'utf8';

		//Подключение, ссылка в GLOBALS
		$GLOBALS['db'] = mysql_connect($hostname, $username, $password);
		mysql_set_charset($encoding, $GLOBALS['db']);
		mysql_select_db($database, $GLOBALS['db']);
	}

	function database_disconnect()
	{
		//Отключиться от базы данных
		mysql_close($GLOBALS['db']);
	}

	function database_query($query)
	{
		//Выполнить SQL-запрос
		return mysql_query($query, $GLOBALS['db']);
	}

?>
