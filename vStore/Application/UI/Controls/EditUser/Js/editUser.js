$(function () {
	var map = {
			// One day, when we desire more forms, it will only take to update this obj.
			updateProfileFormWrapper: 'Upravit profilové informace',
			changePasswordFormWrapper: 'Změnit heslo'
		},
		wrapperClass = 'formWrapper',
		links = [],
		showThisWrapper = function (wrapper) {
			$('.'+wrapperClass).not(wrapper).slideUp();
			wrapper.slideToggle();
		};
	for (var i in map) {
		var el = $('#'+i).hide(),
			link = $('<a>').attr('href', '#').html(map[i]).prependTo(el.parent());
		links.push(link);
		link.click((function () {
			var correspondingWrapper = el;
			return function (e) {
				showThisWrapper(correspondingWrapper);
				e.preventDefault();
			}
		})());
	}
	links.pop();
	links.forEach(function (item) {
		item.before($('<span>').html('|'));
	});
	
	var error = $('.'+wrapperClass+' .error');
	if (error) {
		showThisWrapper(error.closest('.'+wrapperClass));
	}
});