/* Переменные для анимации */
var display = { 'margin_left': 50,
                'block_width': 250,
                'block_margin': 25,
                'init_jump': 1.5,
                'capacity': 0,
                'displayed': 0 };
display.blocks = [];
$(window).resize(resizeDisplay);

/* Загрузить текущее расписание */
calculateDisplayCapacity();
loadBlocks('init');

/* Загрузка новых блоков */
function loadBlocks(query) {
	$.post('/engine/ajax.php', { query: query, capacity: display.capacity }, buildBlocks);
}

/* Отображение загруженных блоков */
function buildBlocks(raw_data) {
	var data = JSON.parse(raw_data);
	console.log(data);

	/* Добавление новых блоков в дисплей */
	if (data.timepunk == 'init') {
		display.blocks = display.blocks.concat(data.blocks);
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
}

/* Генерация кода и создание блока */
function makeBlockElement(block) {
	var element = $('<div class="day"><div class="dayCaption '+block.tags+'"><span class="date">'+block.short_date+'</span>&nbsp;<span class="dayName">'+block.day+'</span></div><div class="schedule"></div></div>');
	block.data.forEach(function(subject) {
		$(element).find('.schedule').append('<div class="lesson l'+subject.queue+' '+subject.type+'"><p class="caption">'+subject.caption+'</p><p class="place"><a href="#">'+subject.place+'</a></p></div>');
	});
	return element;
}

/* Анимация первой загрузки блоков */
function initAnimate() {

	/* Отображаем все блоки из дисплея */
	display.blocks.forEach(function(block) {
		var element = makeBlockElement(block);
		$(element).css({ 'opacity': 1, 'left': (display.margin_left+((display.block_width+display.block_margin)*block.queue))+'px' });
    	$('#display').append(element);
	});

	$('#display .day.init').each(function() {
		//$(this).animate({ 'opacity': 1, 'left': (displayMarginLeft+((dayBlockWidth+dayBlockMargin)*i++))+'px' }, 500).removeClass('init');
	});

	/* Теперь можно включить навигацию */
}

/* Анимация пролистывания блоков */
function stepAnimate(direction) { }
function jumpAnimate(direction) { }

/* Догрузить блоки, если после изменения размера появилось пустующее место */
function resizeDisplay() {
	calculateDisplayCapacity();
}

/* Определить максимальное количество блоков на дисплее */
function calculateDisplayCapacity() {
	var workspace = $(window).width()-display.margin_left;
	var size = display.block_width+display.block_margin;
	display.capacity = Math.round(workspace/size);
}