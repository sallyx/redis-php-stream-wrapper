if (typeof $ == 'undefined')
{
	if (document.getElementsByClassName('sallyx-streamWrappers-fileBrowser %scheme%').length)
	{
		document.getElementsByClassName('sallyx-streamWrappers-fileBrowser %scheme%')[0].innerHTML = 'This panel require jQuery.';
	}
}
else {
	$(function () {
		var presenterLink = '%redisPresenterLink%';
		var scheme = '%scheme%';
		var $content = $('.sallyx-streamWrappers-fileBrowser.' + scheme);
		var lastLink, lastData;

		function addHtml(html) {
			$content.html(html);
		}

		function doAjax(link, data) {
			lastLink = link;
			lastData = data;
			$.ajax(link, {data: data}).done(addHtml);
		}

		$content.on('click', 'tr.dir a', function (event) {
			event.preventDefault();
			var link = $(this).attr('href');
			doAjax(link, {});
			return false;
		});

		$content.on('click', '.sallyx-streamWrappers-fileBrowser-buttons span.r', function(event) {
			doAjax(lastLink, lastData);
		});

		$content.on('click', '.sallyx-streamWrappers-fileBrowser-buttons span.d a', function(event) {
			event.preventDefault();
			var link = $(this).attr('href');
			doAjax(link, {});
			return false;
		});
		$content.on('click', '.sallyx-streamWrappers-fileBrowser-buttons span.l a', function(event) {
			event.preventDefault();
			alert('Not implemented yet');
			return false;
			var link = $(this).attr('href');
			doAjax(link, {});
			return false;
		});

		doAjax(presenterLink, { scheme: scheme, dir: '/' });
	});
}
