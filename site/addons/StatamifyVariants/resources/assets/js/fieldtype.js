template = `
<div class="flex">
<div class="form-group grid-field grid-mode-table width-75" v-show="hasData">
	 <table v-if="hasData" class="grid-table bordered-table">
		<thead>
		 <tr>
			<th style="">
				 <div class="flexy">
					<label class="block fill">
					Option Name
					</label>
				 </div>
			</th>
			<th style="">
				 <div class="flexy">
					<label class="block fill">
					Option Values
					</label>
				 </div>
			</th>
			<th class="row-controls"></th>
		 </tr>
		</thead>
		<tbody>
		 <tr v-for="(rowIndex, row) in data.settings">
			<td v-for="field in config.fields">
				 <div class="{{ field.type }}-fieldtype">
					<component :is="field.type + '-fieldtype'"
					 :name="name + '.' + rowIndex + '.' + field.name"
					 :data.sync="row[field.name]"
					 :config="field">
					</component>
				 </div>
			</td>
			<td class="row-controls">
				 <span class="icon icon-menu move drag-handle"></span>
				 <span class="icon icon-cross delete" v-on:click="deleteRow(rowIndex)"></span>
			</td>
		 </tr>
		</tbody>
	 </table>
	 
</div>

<div class="form-group width-25">
	<button type="button" class="btn btn-default add-row" @click="addRow" v-el:add-row-button>
	 	Option <i class="icon icon-plus icon-right"></i>
	 </button>
</div>
</div>

<div class="table-fieldtype" v-if="data.settings.length">
<div class="table-field">
	<table class="bordered-table">
		<thead>
			<tr>
				<th>
					<span>Variants</span>
				</th><th>
					<span>Price change</span>
				</th><th>
					<span>Compare at price change</span>
				</th><th>
					<span>Inventory</span>
				</th><th>
					<span>SKU</span>
				</th><th>
					<span>Hide</span>
				</th>
			</tr>
		</thead>
		<tbody class="ui-sortable">
			<tr v-for="(rowIndex, row) in data" v-if="rowIndex != 'settings'" data-id="{{ row.id }}">
				<td>
					<span v-for="attr in row.attrs.split('|')">{{ attr }}</span>
				</td><td>
					<input class="form-control" type="text" v-model="row.price">
				</td><td>
					<input class="form-control" type="text" v-model="row.compare_at_price">
				</td><td>
					<input class="form-control" type="text" v-model="row.inventory">
				</td><td>
					<input class="form-control" type="text" v-model="row.sku">
				</td><td>
				<div class="toggle-container" :class="{ on: row.hide }" @click="row.hide = !row.hide">
					<div class="toggle-slider">
						<div class="toggle-knob" tabindex="0"></div>
					</div>
				</div>
				</td>
			</tr>
		</tbody>
	</table>

</div>
</div>

`

Vue.component('statamify_variants-fieldtype', {

	mixins: [Fieldtype],

	template: template,

	data: function() {
		return {
			blank: {},
			sortableOptions: {},
			min_rows: 0,
			max_rows: false,
			autoBindChangeWatcher: false,
			changeWatcherWatchDeep: false
		};
	},

	computed: {
		hasData: function() {
			return this.data && this.data.settings.length;
		}
	},

	ready: function() {
		// Initialize with an empty array if there's no data.
		if (! this.data) {
			this.data = [];
		}

		// Prepare the blank row
		this.prepareBlankRow();
		this.watchTags();


		this.initSortable();
		this.bindChangeWatcher();
	},

	methods: {
		prepareBlankRow: function() {
			var blank = {};
			var fields = JSON.parse(JSON.stringify(this.config.fields));

			_.each(fields, function(field, key) {
				blank[field.name] = '';
			});

			this.blank = blank;
		},

		watchTags: function() {

			self = this

			setTimeout(function() {
				$(self.$el).next().find('.grid-table input.selectized').on('change', function() {
					self.generateVariants()
				})
			}, 0)

		},

		generateVariants: function() {

			val = JSON.parse(JSON.stringify(this.data))
			
			if (val.settings && val.settings.length) {
				settings = val.settings
				variations = settings[0].option_values

				for(i=1;i<settings.length;i++) {
					attrs = settings[i].option_values
					newVariations = []
					for(k=0;k<attrs.length;k++) {
						variations.forEach(function(v) {
							newVariations.push(v + '|' + attrs[k])
						})
					}
					variations = newVariations
				}

				items = variations.map(function(v) {
					return { id: String.fromCharCode(65 + Math.floor(Math.random() * 26)) + Date.now(), attrs: v, price: '', compare_at_price: '', inventory: '', sku: '', hide: false }
				})

				finalItems = items.map(function(i) {

					exists = false

					_.mapObject(val, function(v, key) {
						if (key != 'settings') {
							attrsI = i.attrs.split('|')
							attrsV = v.attrs.split('|')
							arrContainsArr = attrsV.every(function (value) {
								return (attrsI.indexOf(value) >= 0);
							})

							if (arrContainsArr && attrsI.length == attrsV.length) {
								v.attrs = i.attrs
								exists = v
							}
						}
					})

					if (exists) {
						return exists
					} else {
						return i
					}
				})

				finalItems.settings = val.settings
				data = $.extend({}, finalItems);
				Vue.set(this, 'data', data)
				this.watchTags()
			}

			else {

				data = { settings: [] }
				Vue.set(this, 'data', data)

			}

		},

		addRow: function() {

			var blank = _.clone(this.blank);

			this.data.settings.push(blank);

			this.$nextTick(function() {
				this.getSortable().sortable(this.getSortableOptions());
				this.watchTags();
				// Focus the first field in the last row.
				const child = this.$children.length - this.$children.length / this.data.settings.length;
				this.$children[child].focus();
			});
		},

		deleteRow: function(index) {
			var self = this;

			swal({
				type: 'warning',
				title: translate('cp.are_you_sure'),
				confirmButtonText: translate('cp.yes_im_sure'),
				cancelButtonText: translate('cp.cancel'),
				showCancelButton: true
			}, function() {
				self.data.settings.splice(index, 1);
				self.watchTags();
				self.generateVariants()
			});
		},

		initSortable: function() {
			this.getSortable().sortable(this.getSortableOptions());
		},

		getSortable: function() {
			return $(this.$el).next().find('.grid-table tbody');
		},

		getSortableOptions: function() {
			var self = this;
			var start = '';

			return {
				axis: "y",
				revert: 175,
				handle: '.drag-handle',
				placeholder: 'table-row-placeholder',
				forcePlaceholderSize: true,

				start: function(e, ui) {
					start = ui.item.index();
					ui.placeholder.height(ui.item.height());
				},

				update: function(e, ui) {
					var end  = ui.item.index(),
					swap = self.data.settings.splice(start, 1)[0];

					self.data.settings.splice(end, 0, swap);
					self.watchTags();
					self.generateVariants()
				}
			}
		},

		/**
		 * Bootstrap Column Width class
		 * Takes a percentage based integer and converts it to a bootstrap column number
		 * eg. 100 => 12, 50 => 6, etc.
		 */
		 colClass: function(width) {
		 	if (this.$root.isPreviewing) {
		 		return 'col-md-12';
		 	}

		 	width = width || 100;
		 	return 'col-md-' + Math.round(width / 8.333);
		 },

		 gridColWidth: function(width) {
		 	return (width === 100) ? '' :  width + '%';
		 },

		 getReplicatorPreviewText() {
		 	return _.map(this.$children, (fieldtype) => {
		 		if (fieldtype.config.replicator_preview === false) return;

		 		return (typeof fieldtype.getReplicatorPreviewText !== 'undefined')
		 		? fieldtype.getReplicatorPreviewText()
		 		: JSON.stringify(fieldtype.data);
		 	}).join(', ');
		 },

		 focus() {
		 	if (this.hasData) {
		 		this.$children[0].focus();
		 	} else {
		 		this.$els.addRowButton.focus();
		 	}
		 }
		}

});
