$(function () {
	var loginLink = $('#loginLink'),
		logoutLink = $('#logoutLink'),
		userLoggedOut = $('#userLoggedOut'),
		userLoggedIn = $('#userLoggedIn');
		
	loginLink.colorbox();
	logoutLink.click(function (e) {
		var href = logoutLink.attr('href');
		$.ajax({
			url: href,
			dataType: 'json',
			success: function (data, textStatus, jqXHR) {
				userLoggedIn.slideUp(1000, function () {
					userLoggedOut.removeClass('hidden');
				});
			},
			error: function (jqXHR, textStatus, errorThrown) {
				window.location.href = href;
			}
		});
		e.preventDefault();
	});
});