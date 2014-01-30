<!DOCTYPE html>
<html>
	<head>
		<title>Группа ВА-32 КПИ</title>
		<meta charset="utf-8">
		<link rel="stylesheet" href="template/style.css" type="text/css">
	</head>
	<body>
		<div id="panel">
			<div id="user">
				<?php if(isset($GLOBALS['user'])) : ?>
					<a href="#" class="user"><span class="pic"></span><?php echo $GLOBALS['user']['name'] ?><span class="sub"></span></a>
				<?php else : ?>
					<a id="vklogin" href="https://oauth.vk.com/authorize?client_id=4099924&scope=notify,offline&redirect_uri=http://va32kpi.ru/login.php&response_type=code&v=5.5" rel="nofollow">&nbsp;</a>
				<?php endif; ?>
			</div>
			<ul>
				<li><a href="/" class="icon schedules active"><span></span>Расписание</a></li
				><li><a href="/?module=library" class="icon library"><span></span>Библиотека</a></li
				><li><a href="/?module=map" class="icon map"><span></span>Карта</a></li>
			</ul>
		</div>