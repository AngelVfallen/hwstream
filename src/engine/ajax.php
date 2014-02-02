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
		$data_query = explode(',', $_POST['query'], 3);
		if ($data_query[0] == 'init') {
			$data['timepunk'] = 'init';

			$data['first'] = find_edgedates('first');
			$data['last'] = find_edgedates('last');

			$query = "SELECT * FROM `schedules` WHERE `date` < DATE(NOW()) ORDER BY `date` DESC LIMIT 0,1";
			$data1 = database_query($query);
			if ($block = mysql_fetch_assoc($data1)) {
				$blocks[] = make_block($block, -1);
			}

			$i = 0;
			$limit = $_POST['capacity'];
			$query = "SELECT * FROM `schedules` WHERE `date` >= DATE(NOW()) ORDER BY `date` ASC LIMIT 0,$limit";
			$data1 = database_query($query);
			while ($block = mysql_fetch_assoc($data1)) {
				$blocks[] = make_block($block, $i++);
			}
		}
		else if ($data_query[0] == 'next') {
			$data['timepunk'] = 'next';

			$query = "SELECT * FROM `schedules` WHERE `date` > '".$data_query[1]."' ORDER BY `date` ASC LIMIT 0,1";
			$data1 = database_query($query);
			if ($block = mysql_fetch_assoc($data1)) {
				$blocks[] = make_block($block, $data_query[2]);
			}
		}
		else if ($data_query[0] == 'prev') {
			$data['timepunk'] = 'prev';

			$query = "SELECT * FROM `schedules` WHERE `date` < '".$data_query[1]."' ORDER BY `date` DESC LIMIT 0,1";
			$data1 = database_query($query);
			if ($block = mysql_fetch_assoc($data1)) {
				$blocks[] = make_block($block, $data_query[2]);
			}
		}

		$data['blocks'] = $blocks;
		echo json_encode($data);
	}

	/* Завершаем работу с базой данных */
	database_disconnect();

	/* Расшифровка данных блока */
	function decode_block_data($raw_data) {
		$data = array();
		$subjects = $GLOBALS['subjects'];
		$i = 1;
		foreach (explode(',', $raw_data) as $id) {
			$data[] = array('queue' => $i++,
				            'caption' => $subjects[$id]['caption'],
				            'type' => $subjects[$id]['type'],
				            'place' => $subjects[$id]['place'],);
		}
		return $data;
	}

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

	function find_edgedates($direction) {
		if ($direction == 'first') $order = 'ASC';
		else if ($direction == 'last') $order = 'DESC';

		$query = "SELECT `date` FROM `schedules` ORDER BY `date` $order LIMIT 0,1";
		$data1 = database_query($query);
		if ($block = mysql_fetch_assoc($data1)) {
			return $block['date'];
		} else return '-';
	}
?>