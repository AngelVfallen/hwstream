<?php

	function widget_library()
	{
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
			foreach (explode(',', $block['content']) as $file_id) {
				$query = "SELECT * FROM `files` WHERE `id` = '$file_id'";
				$data1 = database_query($query);
				if ($file = mysql_fetch_assoc($data1)) {
					$files[] = '<li><a href="uploads/'.$file['file'].'" target="_blank" title="Загрузить файл">'.$file['caption'].'</a></li>';
				}
			}

			/* Подготовка тегов */
			$tags = array();
			foreach (explode(',', $block['tags']) as $tag) {
				$tags[] = '<a class="button light" href="?module=library&filter='.$tag.'">'.$tag.'</a>';
			}

			$library[] = '<div class="item">
			<p class="caption">'.$block['caption'].'</p>
			<ul class="files">'.implode('', $files).'</ul>
			<p class="tags">Метки:'.implode('', $tags).'</p>
			
			</div>';
		}//<p class="added">'.$added.' от '.$author.'</p>

		/* Быстрый фильтр */
		$tags = array(0 => '<a class="button light" href="?module=library">Все</a>',
		              1 => '<a class="button light" href="?module=library&filter=книга">Книги</a>',
		              2 => '<a class="button light" href="?module=library&filter=методичка">Методички</a>',
		              3 => '<a class="button light" href="?module=library&filter=лабораторная">Лабораторные</a>',
		              4 => '<a class="button light" href="?module=library&filter=решение">Решения</a>');

		/* HTML-шаблон */
		$annotation = '<div id="annotation">
		<p>В библиотеке <b>'.$total_count.'</b> файлов. <a id="add" class="button" href="#" style="margin-left: 5px">Добавить файл</a></p>
		<p class="tags">Фильтр:'.implode(' ', $tags).'</p>
		</div>';
		$content = '<div id="content">'.implode('', $library).'</div>';
		return '<div id="library" class="inner">'.$annotation.$content.'</div>';
	}

	function widget_map()
	{
		return '<img class="inner" src="uploads/map.jpg">';
	}

?>