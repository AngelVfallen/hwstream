/*
	Homework Stream, v1.0
	http://github.com/asleepwalker/hwstream

	by Artyom "Sleepwalker" Fedosov, 2014
	http://me.asleepwalker.ru/
	mail@asleepwalker.ru
*/

/* Переменные для пользователя */
var user = { logged: $('#logged').val() };
if (user.logged) {
	user.vk_id = $('#user_id').val();
	user.token = $('#user_token').val();
	user.perms = $('#user_perms').val();
} else {
	user.perms = 'guest';
}