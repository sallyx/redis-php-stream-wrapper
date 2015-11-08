if (typeof $ == 'undefined') {
	if (document.getElementsByClassName('sallyx-streamWrappers-fileBrowser %scheme%').length) {
		document.getElementsByClassName('sallyx-streamWrappers-fileBrowser %scheme%')[0].innerHTML = 'This panel require jQuery.';
	}
}
else {
	$(function () {
		var presenterLink = '%redisPresenterLink%';
		var scheme = '%scheme%';
		var data = {
			scheme: scheme,
			dir: '/'
		};

		$.ajax(presenterLink, {data: data}).done(function (html) {
			$('.sallyx-streamWrappers-fileBrowser.' + scheme).html(html);
		});
	});
}
