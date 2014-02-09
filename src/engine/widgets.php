<?php

	function widget_library() {
		
		/* Общее количество файлов */
		$query = "SELECT COUNT(`id`) FROM `library`";
		$data1 = database_query($query);
		$temp = mysql_fetch_array($data1);
		$total_count = $temp[0];

		/* Постройка блоков */
		$library = array();
		$query = "SELECT * FROM `library`";
		if (isset($_GET['filter'])) { $query .= " WHERE `tags` = '".addslashes($_GET['filter'])."'"; }
		$query .= ' ORDER BY `date` DESC';
		$data1 = database_query($query);
		while ($block = mysql_fetch_assoc($data1)) {

			/* Подготовка файлов блока */
			$files = array();
			foreach (explode(',', $block['attachments']) as $file_id) {
				$query = "SELECT * FROM `files` WHERE `id` = '$file_id'";
				$data1 = database_query($query);
				if ($file = mysql_fetch_assoc($data1)) {
					$files[] = '<li><a href="uploads/'.$file['file'].'" target="_blank" title="Загрузить файл">'.$file['caption'].'</a></li>';
				}
			}

			/* Подготовка тегов */
			$tags = array();
			foreach (explode(',', $block['tags']) as $tag) {
				$tags[] = '<a href="?module=library&filter='.$tag.'">'.$tag.'</a>';
			}

			/* Дата добавления */
			switch (date('n', strtotime($block['date']))) {
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
			$added = date('j '.$mon.' Y в H:i', strtotime($block['date']));

			/* Автор блока */
			$controls = '';
			$author = 'DELETED';
			$query = "SELECT * FROM `users` WHERE `id` = '".$block['author']."'";
			$data1 = database_query($query);
			if ($user = mysql_fetch_assoc($data1)) {
				$author = '<b>'.$user['name'].'</b>';
				if ($user['id'] == $block['author']) $controls = '<p class="controls"><a id="remove" href="#" title="Удалить из библиотеки">Удалить</a></p>';
			}

			$library[] = '<div class="item">
			<p class="caption">'.$block['caption'].'<span class="added">'.$added.' от '.$author.'</span></p>
			<div class="text">'.$block['content'].'</div>
			<ul class="files">'.implode('', $files).'</ul>
			<p class="tags">Метки: '.implode(', ', $tags).'</p>
			'.$controls.'
			</div>';
		}

		/* Быстрый фильтр */
		$tags = array(0 => '<a class="button light" href="?module=library">Все</a>',
		              1 => '<a class="button light" href="?module=library&filter=книга">Книги</a>',
		              2 => '<a class="button light" href="?module=library&filter=методичка">Методички</a>',
		              3 => '<a class="button light" href="?module=library&filter=лабораторная">Лабораторные</a>',
		              4 => '<a class="button light" href="?module=library&filter=решение">Решения</a>');

		/* HTML-шаблон */
		$upload = '';
		if (isset($GLOBALS['user'])) { $upload = ' <a id="add" class="button" href="#" style="margin-left: 5px">Добавить файл</a>'; }
		$annotation = '<div id="annotation">
		<p>В библиотеке <b>'.$total_count.'</b> файлов.'.$upload.'</p>
		<p class="tags">Фильтр:'.implode(' ', $tags).'</p>
		</div>';
		$content = '<div id="content">'.implode('', $library).'</div>';
		return '<div id="library" class="inner">'.$annotation.$content.'</div>';
	}

	function widget_map() {
		return '<img class="inner" src="uploads/map.jpg">';
	}

	function widget_lightbox() {
		return '<div id="lightbox" style="display: none">
			<div id="lb_wrap">
				<div id="lb_body"></div>
			</div>
			<div id="lb_top" class="darkenZoneLB"></div>
			<div id="lb_bottom" class="darkenZoneLB"></div>
			<div id="lb_left" class="darkenZoneLB"></div>
			<div id="lb_right" class="darkenZoneLB"></div>
		</div>';
	}

?>