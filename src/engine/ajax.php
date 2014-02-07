<?php

	/* Подключаемся к базе данных */
	include('database.php');
	database_connect();

	/* Запрос блоков из базы данных */
	if (isset($_POST['query']) && isset($_POST['capacity'])) {

		/* Сразу готовим переменные для вывода */
		$data = array();
		$blocks = array();

		/* Получаем полный список предметов и их вариаций */
		$subjects = array();
		$query = "SELECT * FROM `subjects`";
		$data1 = database_query($query);
		while ($subject = mysql_fetch_assoc($data1)) {
			$subjects[$subject['id']] = $subject;
		}

		/* Определяем какой формат подачи блоков ожидается */
		$data_query = explode(',', $_POST['query']);
		if ($data_query[0] == 'init') {

			/* Первая загрузка */
			$data['timepunk'] = 'init';

			/* Передаём "края" ленты */
			$data['first'] = find_edgedates('first');
			$data['last'] = find_edgedates('last');

			/* Передаём один устаревший блок */
			$query = "SELECT * FROM `schedules` WHERE `date` < DATE(NOW()) ORDER BY `date` DESC LIMIT 0,1";
			$data1 = database_query($query);
			if ($block = mysql_fetch_assoc($data1)) {
				$blocks[] = make_block($block, -1);
			}

			/* Передаём актуальные блоки на всю ёмкость экрана */
			$i = 0;
			$limit = addslashes($_POST['capacity']);
			$query = "SELECT * FROM `schedules` WHERE `date` >= DATE(NOW()) ORDER BY `date` ASC LIMIT 0,$limit";
			$data1 = database_query($query);
			while ($block = mysql_fetch_assoc($data1)) {
				$blocks[] = make_block($block, $i++);
			}
		}
		else if ($data_query[0] == 'next') {

			/* Следующий блок */
			$data['timepunk'] = 'next';

			/* Передаём следующий блок */
			$query = "SELECT * FROM `schedules` WHERE `date` > '".addslashes($data_query[1])."' ORDER BY `date` ASC LIMIT 0,1";
			$data1 = database_query($query);
			if ($block = mysql_fetch_assoc($data1)) {
				$blocks[] = make_block($block, $data_query[2]);
			}
		}
		else if ($data_query[0] == 'prev') {

			/* Предыдущий блок */
			$data['timepunk'] = 'prev';

			/* Передаём предыдущий блок */
			$query = "SELECT * FROM `schedules` WHERE `date` < '".addslashes($data_query[1])."' ORDER BY `date` DESC LIMIT 0,1";
			$data1 = database_query($query);
			if ($block = mysql_fetch_assoc($data1)) {
				$blocks[] = make_block($block, $data_query[2]);
			}
		}
		else if ($data_query[0] == 'add') {

			/* Догрузка блоков в случае появления свободного места */
			$data['timepunk'] = 'add';

			/* Передаём дополнительные блоки, начиная со следующего после отображённого, на всё свободное место */
			$i = ($data_query[2]+1);
			$limit = addslashes(($_POST['capacity']-$data_query[3])+1);
			$query = "SELECT * FROM `schedules` WHERE `date` > '".addslashes($data_query[1])."' ORDER BY `date` ASC LIMIT 0,$limit";
			$data1 = database_query($query);
			while ($block = mysql_fetch_assoc($data1)) {
				$blocks[] = make_block($block, $i++);
			}
		}

		/* Упаковываем всё в JSON и отправляем клиенту */
		$data['blocks'] = $blocks;
		echo json_encode($data);
	}
	/* Добавление нового комментария */
	else if (isset($_POST['comment']) && isset($_POST['to']) && isset($_POST['token'])) {

		/* Прежде всего проверим пользователя */
		$query = "SELECT * FROM  `users` WHERE `token` = '".addslashes($_POST['token'])."'";
		$data1 = database_query($query);

		/* Если пользователь с таким token есть в базе данных */
		if ($user = mysql_fetch_assoc($data1)) {

			/* Разбираемся, к чему мы добавляем комментарий */
			$to = explode(',', $_POST['to'], 2);

			/* Получаем блок, к которому добавляем комментарий */
			$query = "SELECT * FROM  `schedules` WHERE `date` = '".addslashes($to[0])."'";
			$data1 = database_query($query);

			/* Если блок найден, создаём комментарий и добавляем его */
			if ($block = mysql_fetch_assoc($data1)) {

				/* Создаём новый комментарий */
				$important = 0;
				if ($_POST['important'] == 'true') $important = 1;
				$query = "INSERT INTO `comments` (`id`, `content`, `attachments`, `important`, `added`, `author`) VALUES (NULL, '".addslashes($_POST['comment'])."', '', '".$important."', NOW(), '".$user['id']."')";
				database_query($query);

				/* Получаем идентификатор нового комментария и модифицируем данные блока */
				$comment_id = mysql_insert_id();
				$modified_data = mod_block_data($block['data'], 'comment', array('id' => $comment_id, 'to' => $to[1]));

				/* Обновляем данные блока */
				$query = "UPDATE `schedules` SET `data` = '".$modified_data."' WHERE `id` = '".$block['id']."'";
				database_query($query);

				/* Отправляем обработанный комментарий пользователю */
				echo json_encode(decode_comments_data($comment_id));
			}
		}
	}
	/* Удаление комментария */
	else if (isset($_POST['delete_comment']) && isset($_POST['token'])) {

		/* Прежде всего проверим пользователя */
		$query = "SELECT * FROM  `users` WHERE `token` = '".addslashes($_POST['token'])."'";
		$data1 = database_query($query);

		/* Если пользователь с таким token есть в базе данных */
		if ($user = mysql_fetch_assoc($data1)) {

			/* Разбираемся, какой удаляем комментарий */
			$query = "SELECT * FROM  `comments` WHERE `id` = '".addslashes($_POST['delete_comment'])."'";
			$data1 = database_query($query);
			if ($comment = mysql_fetch_assoc($data1)) {

				/* Если комментарий в самом деле авторства этого пользователя - удаляем его */
				if ($comment['author'] == $user['id']) {
					$query = "UPDATE `comments` SET `important` = '-1' WHERE `id` = '".$comment['id']."'";
					database_query($query);

					echo 'success';
				}
			}
		}
	}

	/* Завершаем работу с базой данных */
	database_disconnect();

	/* Создание блока */
	function make_block($block, $queue) {

		/* Подготовка данных */
		$date = explode('-', $block['date']);
		switch (date('w', strtotime($block['date']))) {
			case '1': $day = 'Понедельник'; break;
			case '2': $day = 'Вторник'; break;
			case '3': $day = 'Среда'; break;
			case '4': $day = 'Четверг'; break;
			case '5': $day = 'Пятница'; break;
			case '6': $day = 'Суббота'; break;
			case '0': $day = 'Воскресенье'; break;
		}
		$tags = array();
		if ($block['date'] == date('Y-m-d')) $tags[] = 'today';

		/* Объединение всех данных в один массив */
		return array('queue' => $queue,
			         'tags' => implode(' ', $tags),
			         'day' => $day,
			         'date' => $block['date'],
			         'short_date' => $date[2].'.'.$date[1],
			         'data' => decode_block_data($block['data']));
	}

	/* Расшифровка данных блока */
	function decode_block_data($raw_data) {
		$data = array();

		$subjects = $GLOBALS['subjects'];
		$i = 1;
		foreach (explode(';', $raw_data) as $lesson) {
			$lesson_data = explode(':', $lesson, 2);

			/* Если комментариев нет, передаём пустую строку */
			if (isset($lesson_data[1])) $comments = $lesson_data[1];
			else $comments = '';

			if ($lesson_data[0] != '0') { // Игнорируем "окна" в расписании

				/* Расшифрованные данные блока на выход */
				$subject_info = $subjects[$lesson_data[0]];
				$data[] = array('queue' => $i++,
				                'caption' => $subject_info['caption'],
				                'type' => $subject_info['type'],
				                'place' => preg_replace(array(0=>'/\[/',1=>'/\]/'), array(0=>'<b>',1=>'</b>'), $subject_info['place']), // Квадратные скобки - жирный текст
				                'comments' => decode_comments_data($comments));
			} else {

				/* Окна передаём отдельным типом, а ещё к ним тоже могут быть комментарии */
				$data[] = array('queue' => $i++,
				                'type' => 'empty',
				                'comments' => decode_comments_data($comments));
			}
		}
		return $data;
	}

	/* Изменение данных блока */
	function mod_block_data($raw_data, $action, $content) {

		if ($action == 'comment') { // Добавить комментарий

			/* Разбираем данные на элементы */
			$lessons = explode(';', $raw_data); // Занятия этого блока
			$data = explode(':', $lessons[$content['to']-1]); // Комментарии к нужному занятию

			/* Добавляем новый комментарий */
			if (isset($data[1]) && $data[1] != '') { // Если есть другие комментарии
				$comments = explode(',', $data[1]);
				$comments[] = $content['id'];
				$data[1] = implode(',', $comments);
			} else $data[1] = $content['id']; // Если наш комментарий будет первым

			/* Собираем модифицированные данные в рабочий вид */
			$lessons[$content['to']-1] = implode(':', $data);
			return implode(';', $lessons);
		}
	}

	/* Расшифровка ленты комментариев к предмету */
	function decode_comments_data($comments_list) {
		$comments = array();
		$i = 1;

		/* Перебираем все комментарии поочерёдно */
		foreach (explode(',', $comments_list) as $comment_id) {

			/* Если список пустой, то будет один элемент - пустая строка, которую надобно проигнорировать */
			if ($comment_id) {

				/* Получаем комментарий из базы данных */
				$query = "SELECT * FROM `comments` WHERE `id` = '$comment_id'";
				$data1 = database_query($query);
				if (($comment = mysql_fetch_assoc($data1)) && ($comment['important'] >= 0)) { // Если important < 0, то коммент удалён

					switch (date('n', strtotime($comment['added']))) {
						case '1':  $mon = 'января'; break;
						case '2':  $mon = 'февраля'; break;
						case '3':  $mon = 'марта'; break;
						case '4':  $mon = 'апреля'; break;
						case '5':  $mon = 'мая'; break;
						case '6':  $mon = 'июня'; break;
						case '7':  $mon = 'июля'; break;
						case '8':  $mon = 'августа'; break;
						case '9':  $mon = 'сентября'; break;
						case '10': $mon = 'октября'; break;
						case '11': $mon = 'ноября'; break;
						case '12': $mon = 'декабря'; break;
					}

					/* Расшифрованные данные на выход */
					$comments[] = array('queue' => $i++,
					                    'author' => user_info($comment['author']),
					                    'added' => date('j '.$mon.' Y в H:i', strtotime($comment['added'])),
					                    'content' => $comment['content'],
					                    'attachments' => attachments_data($comment['attachments']),
					                    'important' => $comment['important'],
					                    'id' => $comment['id']);
				}
			}
		}
		return $comments;
	}

	/* Информация о пользователе */
	function user_info($user_id) {
		$data = array('name' => 'DELETED');
		$query = "SELECT * FROM `users` WHERE `id` = '$user_id'";
		$data1 = database_query($query);
		if ($user = mysql_fetch_assoc($data1)) {
			$data['vk_id'] = $user['user_id'];
			$data['name'] = $user['name'];
			$data['avatar'] = $user['avatar'];
 		}
		return $data;
	}

	/* Прикреплённые файлы */
	function attachments_data($list) {
		$data = array();
		return $data;
	}

	/* Вычисление "краёв" ленты */
	function find_edgedates($direction) {
		if ($direction == 'first') $order = 'ASC'; // От наименьшего
		else if ($direction == 'last') $order = 'DESC'; // От наибольшего

		$query = "SELECT `date` FROM `schedules` ORDER BY `date` $order LIMIT 0,1";
		$data1 = database_query($query);
		if ($block = mysql_fetch_assoc($data1)) {
			return $block['date'];
		} else return '-';
	}
?>