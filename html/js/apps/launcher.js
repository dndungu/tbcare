core.register('launcher', function(sandbox){
	return {
		init: function(){			
			sandbox.listen('launcher.submit', this.submit);
			sandbox.listen('launcher.submit', this.showBusy);
		},
		kill: function(){
			
		},
		submit: function(event){
			var action = '/' + event.data.action;
			var data = String(event.data.data);
			var ajax = sandbox.getService('ajax');
			ajax.post(action, data, function(){
				if(arguments[0].readyState != 4 || arguments[0].status != 200) return;
				var response = ajax.parseJSON(arguments[0].responseText);
				if(response.launcher.Launch[0].error){
					sandbox.log(response.launcher.Launch[0].error, 3);
				}else{
					var href = 'http://'+response.launcher.Launch[0].launch;
					window.location.replace(href);
				}
			});
		},
		showBusy: function(){
			var selector = '#launchButton';
			var decorator = sandbox.getService('decorator');
			var css = {
				'background-color': '#CCC',
				'border': '1px solid #BBB',
				'background-image': 'url(/skins/hisani/images/launcher-loader.gif)',
				'background-position': 'center',
				'background-attachment': 'scroll',
				'background-repeat': 'no-repeat',
				height: '36px'
			};
			decorator.css({selector: selector, css: css});
			decorator.val({selector: selector, value: ''});
		}
	};
});
