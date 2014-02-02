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
	else if(data.timepunk == 'next') {
		$(display.blocks[0].element).remove();
		display.blocks.splice(0, 1);
		display.blocks.forEach(function(block) { block.queue--; });
		display.blocks = display.blocks.concat(data.blocks);
	}
	else if(data.timepunk == 'prev') {
		if(!((display.blocks[display.blocks.length-1].date == display.last) &&
		   ((display.blocks[display.blocks.length-1].queue+1)) < display.capacity))
		{
			$(display.blocks[display.blocks.length-1].element).remove();
			display.blocks.splice(display.blocks.length-1, 1);
		}
		display.blocks.forEach(function(block) { block.queue++; });
		display.blocks = data.blocks.concat(display.blocks);
	}

	/* Формат анимации загруженных блоков */
	switch (data.timepunk) {
		case 'init':   initAnimate(); break; // Первая загрузка
		case 'next':   stepAnimate('next'); break; // Следующий набор блоков
		case 'prev':   stepAnimate('prev'); break; // Предыдущий набор блоков
		case 'future': jumpAnimate('front'); break; // Прыжок вперёд
		case 'past':   jumpAnimate('behind'); break; // Прыжок назад
		case 'add':    break; // Догрузка в случае появления свободного места
	}

	/* Корректируем состояние управляющих элементов */
	correctNavigation();

	/* Передаём управление дальше */
	display.setBusyState(false);
}

/* Генерация кода и создание блока */
function makeBlockElement(block) {
	var element = $('<div class="day"><div class="dayCaption '+block.tags+'"><span class="date">'+block.short_date+'</span>&nbsp;<span class="dayName">'+block.day+'</span></div><div class="schedule"></div></div>');
	block.data.forEach(function(subject) {
		$(element).find('.schedule').append('<div class="lesson l'+subject.queue+' '+subject.type+'"><p class="caption">'+subject.caption+'</p><p class="place"><a href="#">'+subject.place+'</a></p></div>');
	});
	return element;
}

/* Переход на один шаг */
function loadStep(direction) {
	if (!display.busy) {
		if (direction == 'next') {
			var last = display.blocks[display.blocks.length-1];
			loadBlocks('next,'+last.date+','+last.queue);
		}
		else if (direction == 'prev') {
			var first = display.blocks[0];
			loadBlocks('prev,'+first.date+','+first.queue);
		}
	}
}

/* Анимация первой загрузки блоков */
function initAnimate() {

	/* Отображаем все блоки из дисплея */
	display.blocks.forEach(function(block) {
		var element = makeBlockElement(block);
    	$('#display').append(element);

    	/* Передаём ссылку на элемент в дисплей */
    	block.element = element;

    	/* Анимация появления блока */
    	$(element).css({ 'opacity': 0, 'left': (display.margin_left+((display.block_width+display.block_margin)*(block.queue+0.2)*display.init_jump))+'px' }).
    		animate({ 'opacity': 1, 'left': (display.margin_left+((display.block_width+display.block_margin)*block.queue))+'px' }, 500);
	});
}

/* Анимация пролистывания блоков */
function stepAnimate(direction) {
	if (direction == 'next') {
		var block = display.blocks[display.blocks.length-1];
		var element = makeBlockElement(block).
			css({ 'opacity': '1', 'left': (display.margin_left+((display.block_width+display.block_margin)*((block.queue*1)+1)))+'px' });
		$('#display').append(element);
		block.element = element;
	}
	else if (direction == 'prev') {
		var block = display.blocks[0];
		var element = makeBlockElement(block).
			css({ 'opacity': '1', 'left': (display.margin_left+((display.block_width+display.block_margin)*((block.queue*1)-1)))+'px' });
		$('#display').prepend(element);
		block.element = element;
	}

	$(display.blocks).each(function() {
		$(this.element).stop().animate({'left': (display.margin_left+((display.block_width+display.block_margin)*(this.queue*1)))+'px'}, 500);
	});
}
function jumpAnimate(direction) { }

/* Догрузить блоки, если после изменения размера появилось пустующее место */
function resizeDisplay() {
	calculateDisplayCapacity();
}

/* Определить максимальное количество блоков на дисплее */
function calculateDisplayCapacity() {
	var workspace = $(window).width()-display.margin_left;
	var size = display.block_width+display.block_margin;
	display.capacity = Math.ceil(workspace/size)+1;
}

function correctNavigation() {
	if (display.blocks.length > 0) {

		$('.displayEdges').stop().fadeIn(100);

		if (display.blocks[display.blocks.length-1].date != display.last) {
			$('#edgeRight').click(null).click(function() {
				loadStep('next');
			});
		} else {
			if((display.blocks[display.blocks.length-1].queue+2) >= display.capacity) {
				$('#edgeRight').unbind('click').click(function() {
					slideRightEdge();
					$('#edgeLeft').unbind('click').click(unslideEdge);
					$('#edgeRight').stop().fadeOut(100);
				});
			} else {
				$('#edgeRight').stop().fadeOut(100);
			}
		}

		if (display.blocks[0].date != display.first) {
			$('#edgeLeft').click(null).click(function() {
				loadStep('prev');
			});
		} else {
			$('#edgeLeft').unbind('click').click(function() {
				slideLeftEdge();
				$('#edgeRight').unbind('click').click(unslideEdge);
				$('#edgeLeft').stop().fadeOut(100);
			});
		}
	}
}

function slideRightEdge() {
	var left = parseInt($(display.blocks[display.blocks.length-1].element).css('left'));
	var delta = left - ($('#display').width()-(display.margin_left+display.block_width));
	$('#display .day').each(function() {
		var now = parseInt($(this).css('left'));
		$(this).stop().animate({ 'left': (now-delta)+'px' }, 500);
		display.edged = -delta;
	});
}

function slideLeftEdge() {
	var left = parseInt($(display.blocks[0].element).css('left'));
	var delta = display.margin_left-left;
	$('#display .day').each(function() {
		var now = parseInt($(this).css('left'));
		$(this).stop().animate({ 'left': (now+delta)+'px' }, 500);
		display.edged = delta;
	});
}

function unslideEdge() {
	$('#display .day').each(function() {
		var now = parseInt($(this).css('left'));
		$(this).stop().animate({ 'left': (now-display.edged)+'px' }, 500);
	});
	display.edged = 0;
	correctNavigation();
}