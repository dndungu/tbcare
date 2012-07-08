core.control.extend('form', function(){
	var _private = {
		html: new Object(),
		source: new String(),
		template: new String(),
		record: new Object(),
		command: new String(),
		grid: new String(),
		sandbox: new sandbox(),
		primarykey: 0,
		formObject: new Object(),
		getTemplate: function(){
			var that = this;
			$.ajax({
				type: 'GET',
				url: that.source,
				complete: function(){
					that.template = '<div class="column grid10of10">'+arguments[0].responseText+'</div>';
					that.html = $(that.template);
				},
				async: false
			});			
		},
		ajaxPost: function(){
			var control = this;
			var parameters = this.formObject.serialize()+'&command='+this.command+'&primarykey='+this.primarykey.toString();
			$.ajax({
				type: 'POST',
				data: parameters,
				url: control.source,
				complete: arguments[0],
				dataType: 'json'
			});			
		}		
	};
	var _public = {
			init: function(){
				if(!arguments.length) return;
				_private.source = arguments[0];
				_private.getTemplate();
				this.isCreator();
			},
			getHTML: function(){
				return _private.html;
			},
			setRecord: function(record){
				var template = new String(_private.template);
				var text = this.render(template, record);
				_private.html = $('form', text).removeClass('primaryContent').addClass('column').addClass('grid10of10').css({display: 'none'});
				$('select', _private.html).each(function(){
					var name = $(this).attr('name');
					var value = record[0] ? parseInt(record[0][name]) : false;
					if(value){
						$(this).val(value);
					}
				});
				this.isUpdator();
			},
			clearForm: function(){
				_private.html.find('input[type="text"], input[type="password"], textarea').each(function(){
					var pattern = /{{([^}]*)}}/g;
					var element = $(this);
					var value = element.val();
					element.val(value.replace(pattern, ''));
				});
			},
			setGrid: function(source){
				_private.grid = source;
			},
			setCommand: function(command){
				_private.command = command;
			},
			getSource: function(){
				return _private.source;
			},
			setPrimaryKey: function(ID){
				var that = this;
				_private.primarykey = ID;
				$.ajax({
					type: 'POST',
					data: {command: "select", primarykey: ID},
					url: _private.source,
					complete: function(){
						that.setRecord(jQuery.parseJSON(arguments[0].responseText));
					},
					dataType: 'json',
					async: false
				});
			},
			isCreator: function(){
				this.setCommand('insert');
				_private.formObject = _private.html.find('form');
				_private.formObject.unbind('submit').submit(function(event){
					event.preventDefault();
					_private.ajaxPost(function(){
						_private.sandbox.fire({type: 'navigation.primary', data: _private.grid});
					});
				});
				this.clearForm();
			},
			isUpdator: function(){
				this.setCommand("update");
				_private.formObject = _private.html;
				_private.formObject.unbind('submit').submit(function(event){
					event.preventDefault();
					_private.ajaxPost(function(){
						_private.html.slideUp();
						var data = {};
						_private.html.find('textarea, input').each(function(){
							var element = $(this);
							var name = element.attr('name');
							data[name] = element.val();
						});
						_private.html.find('select').each(function(){
							var element = $(this);
							var name = element.attr('name');
							data[name] = element.find('option:selected').text();
						});
						_private.html.siblings('div').each(function(event){
							var column = $(this);
							var name = column.attr('name');
							column.html(data[name]);
						});
					});
				});				
			}
	};
	for(i in _public){
		this[i] = _public[i];
	}
	this.init(arguments[0]);	
});