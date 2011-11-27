$(function () {

	$('a.retrievePasswordLink').colorbox();

	$('#loginForm').find('form').colorboxForm({
		onSuccess: function () {
			var doc = $(document);
			if($.data(doc.get(0), 'events').cbox_loggedIn) {
				doc.trigger('cbox_loggedIn');
			} else {
				window.location.reload(false);
			}
		}
	});
});