core.register('studio', function(sandbox){
	return {
		init: function(){
			sandbox.module = this;
			sandbox.listen('navigation.primary', this.route);
		},
		kill: function(){
			
		},
		route: function(event){
			var href = event.data;
			var control = sandbox.module.initControl(href);
			if(control){
				sandbox.fire({type: 'navigation.staging', data: {"stage": "primary", "control": control}});
			}
		},
		initControl: function(href){
			var control = false;
			if(sandbox.module.isGrid(href)){
				control = sandbox.getControl('grid', href);
			}
			if(sandbox.module.isForm(href)){
				control = sandbox.getControl('form', href);
			}
			return control;					
		},
		isGrid: function(href){
			var grids = new Array();
			grids.push('/grid/budget');
			if(grids.indexOf(href) === -1){
				return false;
			}else{
				return true;
			}
		},
		isForm: function(href){
			var forms = new Array();
			forms.push('/form/budget');
			if(forms.indexOf(href) === -1){
				return false;
			}else{
				return true;
			}
		}
	};
});