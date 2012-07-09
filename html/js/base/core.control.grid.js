core.control.extend('grid', function(){
	var control = this;
	var _private = {
			form: new String(),
			sandbox: new core.sandbox(),
			source: new String(),
			template: new String(),
			html: new Object(),
			records: new Object(),
			offset: 0,
			limit: 20,
			search: new String(),
			page: 1,
			sortdirection: new String(),
			sortcolumn: new String(),
			reset: function(){
				this.offset = 0;
				this.page = 1;
			},
			refresh: function(){
				var that = this;
				this.ajaxPost('browse', function(){
					that.records = jQuery.parseJSON(arguments[0].responseText);
					that.renderGrid();
					that.initPaginator();
				});				
			},
			renderGrid: function(){
				this.renderContent();
				this.renderLegend();
				this.renderPaginator();
			},			
			renderContent: function(){
				var template = new String($('.gridContent' ,$(this.template)).html());
				var rows = "";
				if(this.records.body) {
					rows = control.render(template, this.records.body);
				}
				$('.gridContent', this.html).html(rows);
				this.initEditForm();
			},
			renderLegend: function(){
				var template = new String($('.gridFooter>span' ,$(this.template)).html());
				var footer = $('.gridFooter>span', this.html);
				var records = this.records.footer;
				var rowCount = this.records.footer['rowCount'];
				var rowLimit = (parseInt(records['rowOffset'])+parseInt(records['rowLimit']));
				records['rowLimit'] = rowLimit > rowCount ? rowCount : rowLimit; 
				var legend = control.render(template, [records]);
				footer.html(legend);
			},
			renderPaginator: function(){
				var template = $('.gridFooter a.previous', $(this.template));
				var pagination = $('.gridFooter a.previous', this.html);
				var rowCount = this.records.footer['rowCount'] ? this.records.footer['rowCount'] : false;
				var pageCount = rowCount ? Math.ceil(rowCount/this.limit) : 0;
				if(pageCount){
					var i = this.page - 2;
					i = i > 0 ? i : 1;
					var j = i + 4;
					j = j > pageCount ? pageCount : j;
					i = ((j-i) < 4) ? (((j-4) > 0) ? (j-4) : i) : i;
					var buttons = new Array();
					while(i <= j){ 
						buttons.push(' <li><a class="pagenavigator" name="'+i+'">' + i++ +'</a></li> ');
					}
					template.parent('li').after(buttons.join(''));
					pagination.parent('li').parent('ul').html(template.parent('li').parent('ul').html());
				}else{
					pagination.parent('li').parent('ul').css({display: 'none'});
				}
			},
			initPaginator: function(){
				var that = this;
				$('.gridFooter a.button', this.html).unbind('mousedown').mousedown(function(event){
					var name = $(this).attr('name');
					var rowCount = that.records.footer['rowCount'];
					var pageCount = Math.ceil(rowCount/that.limit);
					if(isNaN(name)){
						switch(name){
							case "first":
								that.page = 1;
								break;
							case "previous":
								that.page--;
								break;
							case "next":
								that.page++;
								break;
							case "last":
								that.page = pageCount;
								break;
						}
						that.page = that.page > pageCount ? pageCount : that.page;
						that.page = that.page < 1 ? 1 : that.page;
					}else{
						that.page = parseInt(name);
					}
					that.offset = (that.limit*(that.page-1));
					that.refresh();
				});
			},
			initSort: function(){
				var columns = $('.gridColumns>div', this.html);
				this.ordercolumn = this.records.ordercolumn;
				this.orderdirection = this.records.orderdirection;
				var orderclass = this.orderdirection.toLowerCase();
				$('.gridColumns>div>span[name="'+this.ordercolumn+'"]', this.html).addClass(orderclass);
				var that = this;
				columns.unbind('mousedown').mousedown(function(){
					var icon = $(this).children('span');
					that.orderdirection = icon.hasClass('asc') ? 'desc' : 'asc';
					that.ordercolumn = icon.attr('name');
					$('.gridColumns>div>span.asc', this.html).not(this).removeClass('asc');
					$('.gridColumns>div>span.desc', this.html).not(this).removeClass('desc');
					icon.addClass(that.orderdirection);
					that.reset();
					that.refresh();
				});
			},
			initSearch: function(){
				var form = $('.gridHeaderSearch>form', this.html);
				form.unbind('submit').submit(function(event){
					event.preventDefault();
					_private.search = $('input[name="keywords"]', form).val();
					_private.ajaxPost('search', function(){
						_private.records = jQuery.parseJSON(arguments[0].responseText);
						_private.renderGrid();
						_private.initPaginator();
					});
				});
			},
			initAddButton: function(){
				var button = $('.gridHeaderSearch input[name="addButton"]', this.html);
				var that = this;
				button.unbind('mousedown').mousedown(function(event){
					that.sandbox.fire({type: 'navigation.primary', data: that.form.getSource()});
				});
			},
			initEditForm: function(){
				var that = this;
				var rows = $('.gridContent .gridContentRecord', this.html);
				rows.unbind('mousedown').mousedown(function(event){
					$('.gridContent .gridContentRecord').not(this).find('form').slideUp();
					var openForm = $('form', this);
					if(openForm.length) {
						openForm.slideDown();
					}else{
						var primarykey = parseInt($(this).attr('title'));
						that.form.setPrimaryKey(primarykey);
						var form = that.form.getHTML();
						$('.column:last-child', this).after(form);
						form.slideDown();
					}
				});
			},
			findRecord: function(primarykey){
				if(!this.records.body) return false;
				for(i in this.records.body){
					var record = this.records.body[i];
					if(record.primarykey == primarykey){
						return record;
					}
				}
				return false;
			},
			ajaxPost: function(){
				$.ajax({
					type: 'POST',
					data: this.postParameters(arguments[0]),
					url: this.source,
					complete: arguments[1],
					dataType: 'json',
					async: false
				});			
			},
			postParameters: function(){
				var offset = (this.limit*(this.page-1));
				return {
					ordercolumn: this.ordercolumn,
					orderdirection: this.orderdirection,
					command: arguments[0],
					keywords: this.search,
					offset: offset,
					limit: this.limit
				};
			}
	};
	var _public = {			
			init: function(source){
				if(!source) return;
				_private.source = source;
				this.getTemplate();
				this.getRecords();
				_private.renderGrid();
			},
			setOffset: function(offset){
			_private.offset = offset;
			},
			setLimit: function(limit){
				_private.limit = limit;
			},
			getTemplate: function(){
				$.ajax({
					type: 'GET',
					url: _private.source,
					complete: function(){
						_private.template = arguments[0].responseText;
						_private.html = $(_private.template);
					},
					async: false
				});
			},
			setForm: function(source){
				_private.form = _private.sandbox.getControl('form', source);
				_private.form.setCommand('update');
			},			
			getRecords: function(){
				_private.ajaxPost('browse', function(){
					_private.records = jQuery.parseJSON(arguments[0].responseText);
				});
			},
			getHTML: function(){
				_private.initSearch();
				_private.initAddButton();
				_private.initPaginator();
				_private.initSort();
				return _private.html;
			}
		};
	for(i in _public){
		this[i] = _public[i];
	}
	this.init(arguments[0]);
});