<?php

	if(isset($_GET['code'])) {

		/* Запрос token */
		$ch = curl_init('https://oauth.vk.com/access_token?client_id=4099924&client_secret=8wkIisOzZTlpYwGPkaRf&code='.$_GET['code'].'&redirect_uri=http://va32kpi.ru/login.php');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$answer = curl_exec($ch);
		curl_close($ch);
		$result = json_decode($answer);

		/* Если token получен, проверяем пользователя */
		if(isset($result->access_token)) {

			/* Состоит ли пользователь в группе доступа */
			$ch = curl_init("https://api.vk.com/method/groups.isMember?gid=57799683&access_token=$result->access_token");
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$answer = curl_exec($ch);
			curl_close($ch);
			$in_group = json_decode($answer);

			/* Если пользователь состоит в группе */
			if($in_group->response == "1") {

				/* Подключаемся к базе данных, проверяем, не логинился ли он раньше */
				include('engine/database.php');
				database_connect();
				$query = "SELECT * FROM  `users` WHERE `user_id` = '$result->user_id'";
				$data = database_query($query);

				/* Если пользователь логинился, то нужно всего лишь обновить его token */
				if($user = mysql_fetch_assoc($data)) {
					$query = "UPDATE `users` SET `token` = '$result->access_token' WHERE `user_id` = '$result->user_id'";
					database_query($query);
				}
				/* Если же нет, то узнать его имя и добавить в базу данных */
				else {
					$ch = curl_init("https://api.vk.com/method/users.get?uids=$result->user_id&lang=ru");
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$answer = curl_exec($ch);
					curl_close($ch);
					$user_name_array = json_decode($answer);
					$user_name = $user_name_array->response[0];

					/* После логина пользователь сразу получаем права (permissions) полноценного пользователя (user) */
					$query = "INSERT INTO `users` (`id`, `user_id`, `token`, `name`, `perms`) VALUES (NULL, '$result->user_id', '$result->access_token', '$user_name->first_name $user_name->last_name', 'user')";
					database_query($query);
				}

				/* Теперь запишем новый token в cookies и отправим обратно на главную страницу */
				setcookie('token', $result->access_token);
				database_disconnect();
				header('Location: /');
			}
			/* Если пользователь не состоит в группе доступа, сообщим об этом на главной странице */
			else {
				header('Location: /?error=not_in_group');
			}
		}
		/* На случай сбоев, изменения API и отказов авторизации сообщим на главной, что доступ не получен */
		else {
			header('Location: /?error=not_allowed');
		}
	}

?>
