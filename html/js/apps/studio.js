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
			var controlType = sandbox.module.controlType(href);
			if(!controlType) return;
			var control = sandbox.module.initControl(href, controlType);
			sandbox.fire({type: 'navigation.staging', data: {"stage": "primary", "control": control}});
		},
		controlType: function(href){
			var controlType = false;
			if(sandbox.module.isGrid(href)){
				controlType = 'grid';
			}
			if(sandbox.module.isForm(href)){
				controlType = 'form';
			}
			return controlType;				
		},
		initControl: function(href, controlType){
			var control = sandbox.createControl(controlType, href);
			if(controlType == 'grid'){
				control.setForm(href.replace('/grid/', '/form/'));
			}
			if(controlType == 'form'){
				control.setGrid(href.replace('/form/', '/grid/'));
			}				
			return control;					
		},
		isGrid: function(href){
			if(href.indexOf('/studio/grid/') === -1){
				return false;
			}else{
				return true;
			}
		},
		isForm: function(href){
			if(href.indexOf('/studio/form/') === -1){
				return false;
			}else{
				return true;
			}
		}
	};
});