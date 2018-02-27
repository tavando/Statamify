Vue.component('fieldblock-fieldtype', {

	mixins: [Fieldtype],

	ready: function() {

		if (this.config.end) {
			this.$el.parentElement.parentElement.classList.add('end')
		}

	}

});
