$(function () {
	var loginLink = $('.loginLink'),
		logoutLink = $('#logoutLink'),
		userLoggedOut = $('#userLoggedOut'),
		userLoggedIn = $('#userLoggedIn');
		
	loginLink.live('click', function (e) {
		$.colorbox({
			href: $(this).attr('href'),
			innerHeight: '255px'
		});
		e.preventDefault();
	});
	logoutLink.click(function (e) {
		var href = logoutLink.attr('href');
		$.ajax({
			url: href,
			dataType: 'json',
			success: function (data, textStatus, jqXHR) {
				userLoggedIn.slideUp(1000, function () {
					userLoggedOut.removeClass('hidden');
				});
				window.location.reload(false);
			},
			error: function (jqXHR, textStatus, errorThrown) {
				window.location.href = href;
			}
		});
		e.preventDefault();
	});
	
});