chrome.extension.sendMessage({}, function(response) {
	var readyStateCheckInterval = setInterval(function() {
	if (document.readyState === "complete") {
		clearInterval(readyStateCheckInterval);

		$(function() {
			$('#sidebar-widget-6 > .ui-panel').after(
				'<div class="ui-panel bg-wrap "><div class="collapsible collapsible-initialized" style="min-height: 32px; padding: 0px 5px 10px;"><div class="ui-collapsible__header " style="border-bottom: 1px solid rgba(100, 100, 100, 0.3);"><div><i class="fa fa-chevron-down" style="width: 0.8rem; opacity: 0.6; margin-right: 2px; margin-left: -5px;"></i><span class="ui-collapsible__title"> Logs </span> <span>  </span></div></div><div aria-hidden="false" class="rah-static rah-static--height-auto" style="height: auto; overflow: visible;"><div><div class="ui-collapsible__body"><div class="summary__main"><table class="compact highlight"><tbody><tr><td><a class="btn" href="javascript:;" onclick="APP.reporter.start(\'Trades\', \'/reports/trades\', \'trades\', \'1948165\')">Download Trades</a></td></tr><tr>    <td><a class="btn" href="javascript:;" onclick="APP.reporter.start(\'USD History\', \'/reports/ledger/USD\', \'ledger-USD\', \'1948165\')">Download Balances</a></td></tr></tbody></table></div></div></div></div></div></div>'
			);

			setTimeout(
			  function()
			  {
			    $('#sidebar-widget-3 .ui-tabs:last-of-type').delay('10000').trigger("click");
			  }, 5000);
		});

	}
	}, 10);
});