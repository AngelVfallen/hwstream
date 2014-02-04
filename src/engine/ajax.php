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
			$limit = $_POST['capacity'];
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
			$query = "SELECT * FROM `schedules` WHERE `date` > '".$data_query[1]."' ORDER BY `date` ASC LIMIT 0,1";
			$data1 = database_query($query);
			if ($block = mysql_fetch_assoc($data1)) {
				$blocks[] = make_block($block, $data_query[2]);
			}
		}
		else if ($data_query[0] == 'prev') {

			/* Предыдущий блок */
			$data['timepunk'] = 'prev';

			/* Передаём предыдущий блок */
			$query = "SELECT * FROM `schedules` WHERE `date` < '".$data_query[1]."' ORDER BY `date` DESC LIMIT 0,1";
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
			$limit = ($_POST['capacity']-$data_query[3])+1;
			$query = "SELECT * FROM `schedules` WHERE `date` > '".$data_query[1]."' ORDER BY `date` ASC LIMIT 0,$limit";
			$data1 = database_query($query);
			while ($block = mysql_fetch_assoc($data1)) {
				$blocks[] = make_block($block, $i++);
			}
		}

		/* Упаковываем всё в JSON и отправляем клиенту */
		$data['blocks'] = $blocks;
		echo json_encode($data);
	}

	/* Завершаем работу с базой данных */
	database_disconnect();

	/* Создание блока */
	function make_block($block, $queue) {

		/* Подготовка данных */
		$date = explode('-', $block['date']);
		switch (date("w", strtotime($block['date']))) {
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
		foreach (explode(';', $raw_data) as $subject) {
			$subject_data = explode(':', $subject, 2);
			$subject_info = $subjects[$subject_data[0]];
			if (isset($subject_data[1])) $comments = $subject_data[1];
			else $comments = '';

			$data[] = array('queue' => $i++,
			                'caption' => $subject_info['caption'],
			                'type' => $subject_info['type'],
			                'place' => $subject_info['place'],
			                'comments' => decode_comments_data($comments));
		}
		return $data;
	}

	/* Расшифровка ленты комментариев к предмету */
	function decode_comments_data($comments_list) {
		$comments = array();
		$i = 1;
		foreach (explode(',', $comments_list) as $comment_id) {
			if ($comment_id) {
				$query = "SELECT * FROM `comments` WHERE `id` = '$comment_id'";
				$data1 = database_query($query);
				if ($comment = mysql_fetch_assoc($data1)) {
					$comments[] = array('queue' => $i++,
					                    'author' => user_info($comment['author']),
					                    'content' => $comment['content'],
					                    'attachments' => attachments_data($comment['attachments']),
					                    'important' => $comment['important']);
				}
			}
		}
		return $comments;
	}

	/* Информация о пользователе */
	function user_info($user_id) {
		return $user_id;
	}

	/* Прикреплённые файлы */
	function attachments_data($list) {
		return $list;
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