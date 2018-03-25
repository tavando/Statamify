template = `
<div class="col-md-6">
	<div class="form-group select-fieldtype">
		<label class="block">Country</label>
		<div class="select select-full" :data-content="countryName">
			<select tabindex="0" v-model="country">
				<option :value="key" v-for="(key, value) in countries">{{ value }}</option>
			</select>
		</div>
	</div>
</div>

<div class="col-md-6" v-if="states">
	<div class="form-group select-fieldtype">
		<label class="block">State</label>
		<div class="select select-full" :data-content="regionName">
			<select tabindex="0" v-model="region">
				<option :value="key" v-for="(key, value) in states">{{ value }}</option>
			</select>
		</div>
	</div>
</div>

<div class="col-md-6" v-else>
	<div class="form-group text-fieldtype">
		<label class="block">Region</label>
		<input tabindex="0" class="form-control type-text" type="text" v-model="region">
	</div>
</div>

`

Vue.component('statamify_countries-fieldtype', {

	mixins: [Fieldtype],

	template: template,

	data: function() {
		return {
			countries: [],
			regions: []
		}
	},

	computed: {

		country: {
			get: function() {
				return this.data.split(';')[0]
			},

			set: function(val) {
				data = this.data.split(';')
				data[0] = val
				this.data = data.join(';')
			}
		},

		region: {
			get: function() {
				return this.data.split(';')[1]
			},

			set: function(val) {
				data = this.data.split(';')
				data[1] = val
				this.data = data.join(';')
			}
		},

		countryName: function() {
			return this.countries[this.data.split(';')[0]]
		},

		regionName: function() {
			return this.states[this.data.split(';')[1]]
		},

		states: function() {
			country = this.data.split(';')[0]
			if (this.regions[country] != undefined) {
				return this.regions[country]
			} else {
				return false
			}

		},

	},

	ready: function() {

		if (!this.data) {
			this.data = ';'
		}

		this.$http.get(cp_url('/addons/statamify/countries'), function(res) {
			
			this.countries = res.countries
			this.regions = res.regions

		});

	}


});