$(function () {

	$('A.retrievePasswordLink').click(function () {
		$.colorbox({ 
							href: $(this).attr('href'),
							width: $('#colorbox').outerWidth(),
							height: $('#colorbox').outerHeight()
						});
								
		return false;
	});

	var form = $('#loginForm').find('form'),
			ajaxError = false,
			errorElement = null;
			
		form.submit(function (e) {
			var $this = $(this),
				ajaxFailed = function (jqXHR, textStatus, errorThrown) {
					ajaxError = true;
					$this.trigger('submit');
				};
			if (!ajaxError) {
				$this.ajaxSubmit(function (data, textStatus, jqXHR) {
					if (data.success === true) {
						window.location.reload(false);
					} else if (data.redirect) {
						window.location.href = data.redirect;
					} else if (data.error === true) {
						var msg;
						if (!(msg = data.message)) {
							msg = "An error has occured. Please try again";
						}
						if (!errorElement) {
							errorElement = $this.find('.errorBlock');
							/*errorElement = $('<ul>').addClass('error');
							$this.prepend(errorElement); */
							if(errorElement.find('li:first').length == 0)
								errorElement.append($('<li>'));
						}
						errorElement.find('li:first').html(msg);
					} else {
						ajaxFailed();
					}
				}, {
					error: ajaxFailed
				});	
				e.stopImmediatePropagation();
				e.preventDefault();
				return false;
			}
		});
	
});