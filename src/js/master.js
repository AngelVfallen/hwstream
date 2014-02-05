/*
	Homework Stream, v1.0
	http://github.com/asleepwalker/hwstream

	by Artyom "Sleepwalker" Fedosov, 2014
	http://me.asleepwalker.ru/
	mail@asleepwalker.ru
*/

//-----------------------------------------------------------------------------
/* 1. Объявление переменных и иниализация */
//-----------------------------------------------------------------------------

/* Переменные для пользователя */
var user = { logged: $('#logged').val() };
if (user.logged) {
	user.id = $('#user_id').val();
	user.token = $('#user_token').val();
	user.perms = $('#user_perms').val();
} else {
	user.perms = 'guest';
}

/* Переменные для анимации */
var display = { 'margin_left': 50,
                'block_width': 250,
                'block_margin': 25,
                'init_jump': 1.5,
                'capacity': 0,
                'edged': 0 };
display.blocks = [];

/* Функции для изменения состояния и поведения дисплея */
display.setBusyState = function(busy) {
	if (busy) {
		display.busy = true;
		$('#loading').stop().fadeIn(300);
	} else {
		display.busy = false;
		$('#loading').stop().fadeOut(300);
	}
};
$(window).resize(resizeDisplay);

/* Загрузить текущее расписание */
calculateDisplayCapacity();
loadBlocks('init');

/* Назначаем контролы для переходов вперёд-назад */
correctNavigation();

//-----------------------------------------------------------------------------
/* 1. Загрузка и обработка данных */
//-----------------------------------------------------------------------------

/* Загрузка новых блоков */
function loadBlocks(query) {
	display.setBusyState(true);
	$.post('/engine/ajax.php', { query: query, capacity: display.capacity }, buildBlocks);
}

/* Отображение загруженных блоков */
function buildBlocks(raw_data) {
	var data = JSON.parse(raw_data);

	/* Добавление новых блоков в дисплей */
	if (data.timepunk == 'init') {
		display.blocks = display.blocks.concat(data.blocks);

		/* С первой загрузкой также передаются даты начала и конца всей ленты */
		display.first = data.first;
		display.last = data.last;
	}
	else if (data.timepunk == 'next') {
		$(display.blocks[0].element).remove();
		display.blocks.splice(0, 1);
		display.blocks.forEach(function(block) { block.queue--; });
		display.blocks = display.blocks.concat(data.blocks);
	}
	else if (data.timepunk == 'prev') {
		if (!((display.blocks[display.blocks.length-1].date == display.last) &&
		   ((display.blocks[display.blocks.length-1].queue+1)) < display.capacity))
		{
			$(display.blocks[display.blocks.length-1].element).remove();
			display.blocks.splice(display.blocks.length-1, 1);
		}
		display.blocks.forEach(function(block) { block.queue++; });
		display.blocks = data.blocks.concat(display.blocks);
	}
	else if (data.timepunk == 'add') {
		var start = display.blocks.length;
		display.blocks = display.blocks.concat(data.blocks);
	}

	/* Формат анимации загруженных блоков */
	switch (data.timepunk) {
		case 'init':   initAnimate(); break; // Первая загрузка
		case 'next':   stepAnimate('next'); break; // Следующий набор блоков
		case 'prev':   stepAnimate('prev'); break; // Предыдущий набор блоков
		case 'future': jumpAnimate('front'); break; // Прыжок вперёд
		case 'past':   jumpAnimate('behind'); break; // Прыжок назад
		case 'add':    addAnimate(start); break; // Догрузка в случае появления свободного места
	}

	/* Корректируем состояние управляющих элементов */
	correctNavigation();

	/* Передаём управление дальше */
	display.setBusyState(false);
}

//-----------------------------------------------------------------------------
/* 2. Шаблоны для отображения данных */
//-----------------------------------------------------------------------------

/* Создание элемента для блока */
function makeBlockElement(block) {
	var element = $('<div class="day"><div class="dayCaption '+block.tags+'"><span class="date">'+block.short_date+'</span>&nbsp;<span class="dayName">'+block.day+'</span></div><div class="schedule"></div></div>');
	block.data.forEach(function(subject) { // Предметы
		$(element).find('.schedule').append('<div class="lesson l'+subject.queue+' '+subject.type+'"><p class="caption">'+subject.caption+'</p><p class="place"><a href="#">'+subject.place+'</a></p></div>');
		if (subject.comments.length > 0) { // Комментарии
			var important = false;
			$(element).find('.l'+subject.queue).append('<div class="comments">'+subject.comments.length+'</div>');

			/* Ищем важные комментарии */
			subject.comments.forEach(function(comment) {
				if (comment.important > 0) important = true;
			});

			/* Если лента комментариев содержит что-то важное - стоит сделать посветку */
			if (important) $(element).find('.l'+subject.queue).addClass('important');
		}
		else if (user.perms != 'guest') {
			$(element).find('.l'+subject.queue).append('<div class="comments write">Добавить</div>');
		}
	});
	return element;
}

/* Создание элемента для комментария */
function makeCommentElement(comment) { }

//-----------------------------------------------------------------------------
/* 3. Навигация по ленте времени */
//-----------------------------------------------------------------------------

/* Переход на один шаг */
function loadStep(direction) {
	if (!display.busy) {
		if (direction == 'next') { // Вперёд
			var last = display.blocks[display.blocks.length-1];
			loadBlocks('next,'+last.date+','+last.queue);
		}
		else if (direction == 'prev') { // Назад
			var first = display.blocks[0];
			loadBlocks('prev,'+first.date+','+first.queue);
		}
	}
}

/* Прыжок к заданной дате */
function loadJump() {}

//-----------------------------------------------------------------------------
/* 4. Просмотр и добавление комментариев */
//-----------------------------------------------------------------------------

/* Включение поддержки кнопки обсуждения */
function activateCommentsViewer(block) {
	block.data.forEach(function(lesson) {
		$(block.element).find('.lesson.l'+lesson.queue).click(function() {
			openCommentsViewer(block, lesson);
		});
	});
}

/* Просмотр комментариев */
function openCommentsViewer(block, lesson) {
	var data = $('<div id="comments">no comments >_></div>');
	if (lesson.comments.length > 0) {
		lesson.comments.forEach(function(comment) {
			//$(data).find('#comments').append(makeCommentElement(comment));
		});
	}
	//$(data).append(makeCommentForm());
	lightbox.show(data);
}

//-----------------------------------------------------------------------------
/* 5. Работа с виджетами */
//-----------------------------------------------------------------------------

/* Виджет lightbox */
var lightbox = { shown: false,
                 element: $('#lightbox'),
                 workspace: $('#lb_body'),
                 width: 582 };

/* Режим lightbox отключается при клике вне его области */
$(lightbox.element).find('.darkenZoneLB').click(function() {
	lightbox.hide();
});

/* Включение режима lightbox и отображение данных */
lightbox.show = function(data) {
	lightbox.shown = true;
	lightbox.resize();
	$(lightbox.workspace).append(data);
	$(lightbox.element).fadeIn(500);
};

/* Выключение режима lightbox и очистка виджета */
lightbox.hide = function() {
	$(lightbox.element).fadeOut(500, function() {
		lightbox.shown = false;
		$(lightbox.workspace).html('');
	});
};

/* Адаптация виджета к измению размеров экрана */
lightbox.resize = function() {

	/* Расположение и размеры lightbox'а */
	var width = lightbox.width;
	var height = $('#lb_wrap').height()+66;
	var top = ($(window).height()-height)/2.25;
	var bottom = top+height;
	var side = ($(window).width()-lightbox.width)/2;

	/* Расположение окна lightbox'а */
	$('#lb_wrap').css({ 'left': side+'px', 'top': top+'px' });

	/* Затемнённая зона вокруг окна */
	$('#lb_top').css({ 'left': '0', 'top': '0', 'width': '100%', 'height': top+'px' });
	$('#lb_bottom').css({ 'left': '0', 'top': bottom+'px', 'bottom': '0', 'width': '100%' });
	$('#lb_left').css({ 'left': '0', 'top': top+'px', 'width': side+'px', 'height': height+'px' });
	$('#lb_right').css({ 'right': '0', 'top': top+'px', 'width': (side-16)+'px', 'height': height+'px' });
};

//-----------------------------------------------------------------------------
/* 6. Анимация и отображение данных */
//-----------------------------------------------------------------------------

/* Анимация первой загрузки блоков */
function initAnimate() {

	/* Отображаем все блоки из дисплея */
	display.blocks.forEach(function(block) {
		var element = makeBlockElement(block);
    	$('#display').append(element);

    	/* Передаём ссылку на элемент в дисплей */
    	block.element = element;

    	/* Включаем комментирование в блоке */
    	activateCommentsViewer(block);

    	/* Анимация появления блока */
    	$(element).css({ 'opacity': 0, 'left': (display.margin_left+((display.block_width+display.block_margin)*(block.queue+0.2)*display.init_jump))+'px' }).
    		animate({ 'opacity': 1, 'left': (display.margin_left+((display.block_width+display.block_margin)*block.queue))+'px' }, 500);
	});
}

/* Анимация пролистывания блоков */
function stepAnimate(direction) {

	/* Добавление элемента блока на экран и связывание его с объектом */
	if (direction == 'next') { // Шаг вперёд
		var block = display.blocks[display.blocks.length-1];
		var element = makeBlockElement(block).
			css({ 'opacity': '1', 'left': (display.margin_left+((display.block_width+display.block_margin)*((block.queue*1)+1)))+'px' });
		$('#display').append(element);
		block.element = element;
	}
	else if (direction == 'prev') { // Шаг назад
		var block = display.blocks[0];
		var element = makeBlockElement(block).
			css({ 'opacity': '1', 'left': (display.margin_left+((display.block_width+display.block_margin)*((block.queue*1)-1)))+'px' });
		$('#display').prepend(element);
		block.element = element;
	}

	/* Общая анимация скольжения в заданную сторону */
	$(display.blocks).each(function() {
		$(this.element).stop().animate({'left': (display.margin_left+((display.block_width+display.block_margin)*(this.queue*1)))+'px'}, 500);
	});
}

/* Скольжение до правого края, чтобы показать последний блок */
function slideRightEdge() {

	/* Сдвигаем блоки влево так, чтобы показать аккурат последний блок */
	var left = parseInt($(display.blocks[display.blocks.length-1].element).css('left'));
	var delta = left - ($('#display').width()-(display.margin_left+display.block_width));
	$('#display .day').each(function() {
		var now = parseInt($(this).css('left'));
		$(this).stop().animate({ 'left': (now-delta)+'px' }, 500);
		display.edged = -delta; // Сохраняем расстояние для восстановления исходного состояния
	});
}

/* Скольжение до левого края, чтобы показать первый блок */
function slideLeftEdge() {

	/* Сдвигаем блоки вправо так, чтобы показать блок, который обычно за левым краем */
	var left = parseInt($(display.blocks[0].element).css('left'));
	var delta = display.margin_left-left;
	$('#display .day').each(function() {
		var now = parseInt($(this).css('left'));
		$(this).stop().animate({ 'left': (now+delta)+'px' }, 500);
		display.edged = delta;
	});
}

/* Возвращаемся к обычной системе пошагового перехода */
function unslideEdge() {

	/* Сдвигаем все блоки на то расстояние, на которое их отодвинул краевой слайдер */
	$('#display .day').each(function() {
		var now = parseInt($(this).css('left'));
		$(this).stop().animate({ 'left': (now-display.edged)+'px' }, 500);
	});

	/* Возвращение к обычному состоянию */
	display.edged = 0;
	correctNavigation();
}

/* Анимация прыжка к заданной дате */
function jumpAnimate(direction) { }

/* Анимация догрузки блоков в случае появления свободного пространства */
function addAnimate(start) {

	/* Перебираем все новоприбывшие блоки */
	for (var i = start; i < display.blocks.length; i++) {
		var block = display.blocks[i];
		var element = makeBlockElement(block);
    	$('#display').append(element);

    	/* Передаём ссылку на элемент в дисплей */
    	block.element = element;

    	/* Анимация добавления блока */
    	$(element).css({ 'opacity': 0, 'left': (display.margin_left+((display.block_width+display.block_margin)*(block.queue+0.2)*display.init_jump))+'px' }).
    		animate({ 'opacity': 1, 'left': (display.margin_left+((display.block_width+display.block_margin)*block.queue))+'px' }, 500);
	}
}

//-----------------------------------------------------------------------------
/* 7. Технические функции, адаптация к среде */
//-----------------------------------------------------------------------------

/* Реакция на изменение размеров экрана */
function resizeDisplay() {

	/* Запустить resize у виджетов */
	if (lightbox.shown) lightbox.resize();

	/* Проверяем, сколько теперь доступно места */
	calculateDisplayCapacity();

	/* Если у нас ёмкость дисплея не соответствует наполнению */
	if (display.blocks.length > display.capacity) { // Блоков стало слишком много
		for (var i = (display.capacity+1); i < display.blocks.length; i++) {
			$(display.blocks[i].element).remove(); // Удаляем все лишние элементы
		};

		/* Обрезаем часть блоков, оставшуюся без элементов */
		display.blocks.splice(display.capacity+1, (display.blocks.length-1)-display.capacity);
	}
	else if ((display.blocks.length-1) < display.capacity) { // Появилось свободное место на экране

		/* Если мы не дошли до конца ленты - запрашиваем подкрепление */
		if (display.blocks[display.blocks.length-1].date != display.last) {

			/* Если дисплей доступен, делаем запрос на следующие за последним блоки */
			var last = display.blocks[display.blocks.length-1];
			if (!display.busy) loadBlocks('add,'+last.date+','+last.queue+','+(display.blocks.length));
		}
	}
}

/* Определить максимальное количество блоков на дисплее */
function calculateDisplayCapacity() {
	var workspace = $(window).width()-display.margin_left;
	var size = display.block_width+display.block_margin;
	display.capacity = Math.ceil(workspace/size)+1;
}

/* Управление системой пошаговой навигации */
function correctNavigation() {

	/* Если в дисплее уже есть какие-нибудь блоки */
	if (display.blocks.length > 0) {

		/* Отображаем края, которые являются кнопками для пошаговой навигации */
		$('.displayEdges').stop().fadeIn(100);

		/* Если последний блок в дисплее - не последний в ленте, то по клику переход к следующему */
		if (display.blocks[display.blocks.length-1].date != display.last) {
			$('#edgeRight').unbind('click').click(function() {
				loadStep('next'); // Следующий блок
			});
		} else { // Если мы добрались до конца ленты

			/* Если последний блок загружен, но находится за краем экрана */
			if ((display.blocks[display.blocks.length-1].queue+2) >= display.capacity) {
				$('#edgeRight').unbind('click').click(function() {

					/* Сдвигаем все блоки так, чтобы открылся последний блок */
					slideRightEdge();
					$('#edgeLeft').unbind('click').click(unslideEdge);
					$('#edgeRight').stop().fadeOut(100); // Прячем кнопку перехода, раз грузить нечего
				});
			} else { // Если последний блок загружен и виден - кнопка загрузки следующего нам ни к чему
				$('#edgeRight').stop().fadeOut(100);
			}
		}

		/* Если первый блок в дисплее - не первый в ленте, то по клику переход к предыдущему */
		if (display.blocks[0].date != display.first) {
			$('#edgeLeft').click(null).click(function() {
				loadStep('prev'); // Предыдущий блок
			});
		} else { // Если мы добрались до начала ленты
			$('#edgeLeft').unbind('click').click(function() {

				/* Сдвигаем блоки так, чтобы открылся первый */
				slideLeftEdge();
				$('#edgeRight').unbind('click').click(unslideEdge);
				$('#edgeLeft').stop().fadeOut(100);
			});
		}
	}
}