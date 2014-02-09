//-----------------------------------------------------------------------------
/* Виджет Lightbox */
//-----------------------------------------------------------------------------

var lightbox = { shown: false,
                 element: $('#lightbox'),
                 workspace: $('#lb_body'),
                 width: 582 };

/* Режим lightbox отключается при клике вне его области */
$(lightbox.element).find('.darkenZoneLB').click(function() {
	lightbox.hide();
});

/* При изменении размеров окна браузера */
$(window).resize(function() {
	if (lightbox.shown) lightbox.resize('slide');
})

/* Включение режима lightbox и отображение данных */
lightbox.show = function(data) {
	lightbox.shown = true;
	$(lightbox.workspace).append(data);
	$(lightbox.element).fadeIn(500);
	lightbox.resize('just');
};

/* Выключение режима lightbox и очистка виджета */
lightbox.hide = function() {
	$(lightbox.element).fadeOut(500, function() {
		lightbox.shown = false;
		$(lightbox.workspace).html('');
	});
};

/* Адаптация виджета к измению размеров экрана */
lightbox.resize = function(type) {

	/* В случае переполнения lightbox'а включаем scroll */
	$('#lb_body').removeClass('scroll').css({ 'height': 'auto' });
	if ((($(window).height()-$('#lb_wrap').outerHeight())/2.25) < 50) {
		$('#lb_body').addClass('scroll').css({ 'height': ($(window).height()-100)+'px' });
		$('#lb_body').animate({ 'scrollTop': $('#form #text').position().top+'px' }, 1000);
	}

	/* Расположение и размеры lightbox'а */
	var width = lightbox.width;
	var height = $('#lb_wrap').outerHeight();
	var top = ($(window).height()-height)/2.25;
	var bottom = top+height;
	var side = ($(window).width()-lightbox.width)/2;

	/* Расположение окна lightbox'а */
	var lb_wrap = { 'left': side+'px', 'top': top+'px' };
	if (type == 'just') { // Без анимации
		$('#lb_wrap').css(lb_wrap);
	}
	else if (type == 'slide') { // Скольжение
		$('#lb_wrap').animate(lb_wrap, 300);
	}

	/* Затемнённая зона вокруг окна */
	$('#lb_top').css({ 'left': '0', 'top': '0', 'width': '100%', 'height': top+'px' });
	$('#lb_bottom').css({ 'left': '0', 'top': bottom+'px', 'bottom': '0', 'width': '100%' });
	$('#lb_left').css({ 'left': '0', 'top': top+'px', 'width': side+'px', 'height': height+'px' });
	$('#lb_right').css({ 'right': '0', 'top': top+'px', 'width': (side-16)+'px', 'height': height+'px' });
};

//-----------------------------------------------------------------------------
/* Модуль библиотеки */
//-----------------------------------------------------------------------------

/* Добавление в библиотеку */
$('#library #add.button').click(function() {
	var form = $('<div id="libform"><ul id="attached"><li class="descr">Прикрепите файл(ы) для добавления в библиотеку.</li></ul><label>Описание:</label><div class="text"><textarea placeholder="Кратко опишите содержимое прикрепляемых файлов"></textarea></div><label>Метки:</label><div class="text"><input id="tags" type="text" placeholder="Через пробел или запятую"></div><ul id="attached"></ul><div id="controls"><button id="attach" class="button light">Прикрепить файл</button><button id="submit" class="button">Сохранить</button></div></div>');
	$(form).find('#attach').click(attachFile);
	/* Открыть виджет lightbox c нашими данными */
	lightbox.show(form);
});

//-----------------------------------------------------------------------------
/* Много общего */
//-----------------------------------------------------------------------------

/* Прикрепление файла */
function attachFile() {

	/* Создаём виртуальную форму для загрузки файла */
	var input = $('<input name="fileinput" type="file" style="height: 1px; opacity: 0; position: absolute; width: 1px;">');
	var form = $('<form enctype="multipart/form-data"><input name="token" type="hidden" value="'+user.token+'"></form>');
	$(form).append(input);

	/* Когда файл выбран - загружаем его на сервер */
	$(input).change(function() {

		/* Добавляем индикатор прогресса загружаемого файла */
		var filename = this.files[0].name.replace(/'/g, '\\');
		var element = $('<li>'+filename+'</li>');
		var progressbar = $('<div class="progress"><div></div></div>');
		$(element).prepend(progressbar);
		$('#attached').append(element);
		lightbox.resize('slide');

		if ($('#attached .descr').length) $('#attached .descr').remove();

		/* Получаем данные нашей виртуальной формы */
		var formData = new FormData($(form)[0]);

		/* Делаем асинхронный запрос загрузки */
		$.ajax({
			url: 'engine/upload.php',
			type: 'POST',
			data: formData,

			/* В случае успешной загрузки */
			success: function(raw_data) {
				var data = JSON.parse(raw_data);
				if (data.id) { // Файл принят и добавлен в БД
					$(progressbar).remove();
					var remove = $('<a href="#" class="remove" title="Удалить приложенный файл">Удалить</a>');
					$(remove).click(function() { $(element).slideUp(200, function() { $(this).remove(); lightbox.resize('slide'); }); });
					$(element).addClass('done').attr('data', data.id).append(remove);
				} else { // Ошибка
					if (data.err) alert(data.err);

					$(element).fadeOut(2000, function() { $(this).remove(); });
				}
			},

			/* Отслеживание прогресса загрузки */
			xhr: function() {
				var myXhr = $.ajaxSettings.xhr();
				if(myXhr.upload){ // Проверка поддержки
				    myXhr.upload.addEventListener('progress', function(e) { attachProgress(e, progressbar); }, false);
				}
				return myXhr;
			},

			/* Параметры, запрещающие вмешиваться jQuery в процесс загрузки */
			cache: false,
			contentType: false,
			processData: false
		});
	});

	$(input).click();
}

/* Отслеживание прогресса загрузки файла */
function attachProgress(e, progressbar) {
	if(e.lengthComputable){
		var total = 78;
		var value = (e.loaded/e.total)*total;
		$(progressbar).find('div').animate({ width: value+'px' }, 200);
	}
}