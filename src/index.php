<?php

	/* Подключаемся к базе данных */
	include('engine/database.php');
	database_connect();

	/* Проверяем пользователя */
	if(isset($_COOKIE['token'])) {
		$token = $_COOKIE['token'];
		$query = "SELECT * FROM  `users` WHERE `token` = '$token'";
		$data = database_query($query);

		/* Если пользователь с таким token не существует, переменная останется пустой */
		if($login = mysql_fetch_assoc($data)) {
			$GLOBALS['user'] = $login;
		}
	}

	/* Открываем запрошенную страницу */
	if(isset($_GET['module'])) {
		$module = $_GET['module'];
	}
	else $module = '';

	switch($module) {
		case 'library': include('template/library.php'); break;
		case 'map':     include('template/map.php');     break;
		case 'options': include('template/options.php'); break;
		default:        include('template/display.php');
	}

	/* Завершаем работу с базой данных */
	database_disconnect();

?>