// Statamify Countries Fieldtype //

Vue.component('statamify-countries-fieldtype', {

  mixins: [Fieldtype],

  template: `
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

    `,

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

// Statamify Variants Fieldtype //

Vue.component('statamify-variants-fieldtype', {

  mixins: [Fieldtype],

  template: `
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
              <span>Price</span>
            </th><th>
              <span>Compare at price</span>
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

    `,

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

// Statamify Order Summary Fieldtype //

Vue.component('statamify-summary-fieldtype', {

  mixins: [Fieldtype],

  template: `
    <table class="order-summary">
      <tbody>
        <tr data-id="{{ item.id }}" v-for="item in data.items">
          <td class="item-image"><div style="background-image: url({{ item.image }})"></div></td>
          <td class="item-line">
            <a href="{{ item.edit_url }}">{{ item.name }}</a>
            <p v-if="item.variant">Variant: {{ item.variant }}</p>
            <p>SKU: {{ item.sku || '-' }}</p>
          </td>
          <td class="item-totals">{{ item.price | money }}</td>
          <td class="item-totals">×</td>
          <td class="item-totals">{{ item.quantity }}</td>
          <td class="item-totals">{{ item.quantity * item.price | money }}</td>
        </tr>
        <tr class="totals">
          <td colspan="5" class="text-right">Subtotal</td>
          <td class="item-totals">{{ data.total.sub | money }}</td>
        </tr>
        <tr class="noborder totals">
          <td colspan="5" class="text-right">Shipping</td>
          <td class="item-totals">{{ data.total.shipping | money }}</td>
        </tr>
        <tr class="noborder totals">
          <td colspan="5" class="text-right">Discount</td>
          <td class="item-totals">{{ data.total.discount | money }}</td>
        </tr>
        <tr class="noborder totals">
          <td colspan="5" class="text-right"><strong>Total</strong></td>
          <td class="item-totals"><strong>{{ data.total.grand | money }}</strong></td>
        </tr>
      </tbody>
    </table>
    `,

  filters: {
    money: function(price) {
      return this.data.config.moneyFormat.replace('[symbol]', this.data.config.currencySymbol).replace('[price]', parseFloat(price).toFixed(2))
    }
  }

});