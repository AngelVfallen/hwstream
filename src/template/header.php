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
				<input id="logged" type="hidden" value="<?php echo isset($GLOBALS['user']) ?>">
				<?php if (isset($GLOBALS['user'])) : ?>
					<input id="user_id" type="hidden" value="<?php echo $GLOBALS['user']['id'] ?>">
					<input id="user_token" type="hidden" value="<?php echo $GLOBALS['user']['token'] ?>">
					<input id="user_perms" type="hidden" value="<?php echo $GLOBALS['user']['perms'] ?>">
					<a href="#" class="user"><span class="pic"></span><?php echo $GLOBALS['user']['name'] ?><span class="sub"></span></a>
				<?php else : ?>
					<a id="vklogin" href="https://oauth.vk.com/authorize?client_id=4099924&scope=notify,offline&redirect_uri=http://va32kpi.ru/login.php&response_type=code&v=5.5" rel="nofollow">&nbsp;</a>
				<?php endif; ?>
			</div>
			<ul>
				<li><a href="/" class="icon schedules<?php if (!isset($_GET['module'])) : ?> active<?php endif; ?>"><span></span>Расписание</a></li
				><li><a href="/?module=library" class="icon library<?php if (isset($_GET['module']) && ($_GET['module'] == 'library')) : ?> active<?php endif; ?>"><span></span>Библиотека</a></li
				><li><a href="/?module=map" class="icon map<?php if(isset($_GET['module']) && ($_GET['module'] == 'map')) : ?> active<?php endif; ?>"><span></span>Карта</a></li>
			</ul>
		</div>