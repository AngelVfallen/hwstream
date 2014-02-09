<?php

	/* Подключаемся к базе данных */
	include('database.php');
	database_connect();

	/* Данные для ответа */
	$data = array();

	/* Прежде всего проверим пользователя */
	$query = "SELECT * FROM  `users` WHERE `token` = '".addslashes($_POST['token'])."'";
	$data1 = database_query($query);

	/* Если пользователь с таким token есть в базе данных */
	if ($user = mysql_fetch_assoc($data1)) {

		/* Генерируем имя для полученного файла */
		$salt = substr(md5(time()), 0, 6); // "Соль" для уникальности имени файла
		$new_name = $salt.'_'.$_FILES['fileinput']['name'];


		/* Если файл успешно обработан, добавляем его в базу данных */
		if (move_uploaded_file($_FILES['fileinput']['tmp_name'], '../uploads/'.$new_name)) {

			/* Собираем информацию о загруженном файле */
			$name = $_FILES['fileinput']['name'];
			$size = $_FILES['fileinput']['size'];
			$author = $user['id'];

			/* Добавляем файл в базу данных */
			$query = "INSERT INTO `files` (`id`, `caption`, `file`, `size`, `author`) VALUES (NULL, '$name', '$new_name', '$size', '$author')";
			database_query($query);

			/* Если файл успешно загружен и добавлен в базу данных */
			$data['id'] = mysql_insert_id();
			$data['name'] = $name;

		} else { // Ошибка при обработке файла
			$data['err'] = 'В процессе загрузки файла произошла ошибка.';
		}
	} else { // Пользователь не найден
		$data['err'] = 'Ошибка доступа.';
	}

	/* Отправляем результат загрузки пользователю */
	echo json_encode($data);

	/* Отключаемся от базы данных */
	database_disconnect();

?>