$(function () {
	$('#retrievePasswordForm').find('form').colorboxForm({
		onSpecial: function (data, config) {
			var src;
			if (src = data.captchaSrc) {
				this.find('img').attr('src', src);
				$('#frmretrievePasswordForm-_uid_captcha').val(data.captchaUid);
				return true;
			}
			return false;
		}
	});
});