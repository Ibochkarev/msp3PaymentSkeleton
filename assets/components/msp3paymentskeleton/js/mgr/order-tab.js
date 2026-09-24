;(function () {
  function getCfg() {
    return window.msp3PaymentSkeletonConfig || {}
  }

  function getLex() {
    return getCfg().lexicon || {}
  }

  function request(action, extra) {
    const cfg = getCfg()
    if (!cfg.connectorUrl) {
      return Promise.reject(new Error('msp3 order-tab: connectorUrl is not configured'))
    }
    const body = new URLSearchParams(Object.assign({ action }, extra || {}))
    const headers = { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
    if (window.MODx && MODx.siteId) {
      headers['modAuth'] = MODx.siteId
    }
    return fetch(cfg.connectorUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers,
      body,
    }).then(function (res) {
      return res.json()
    })
  }

  const PaymentSkeletonOrderTab = {
    name: 'Msp3PaymentSkeletonOrderTab',
    props: {
      orderId: { type: [Number, String], default: 0 },
      order: { type: Object, default: null },
      config: { type: Object, default: null },
      isCreateMode: { type: Boolean, default: false },
    },
    data: function () {
      return {
        attempts: [],
        loading: false,
        error: '',
        amount: '',
        reason: '',
      }
    },
    watch: {
      orderId: {
        immediate: true,
        handler: function () {
          this.reload()
        },
      },
    },
    methods: {
      reload: function () {
        const id = Number(this.orderId || 0)
        if (!id) {
          this.attempts = []
          return
        }
        this.loading = true
        this.error = ''
        const self = this
        request('mgr/getlist', { order_id: id })
          .then(function (json) {
            self.attempts = (json.object && json.object.attempts) || []
            if (self.order && self.order.cost && !self.amount) {
              self.amount = String(self.order.cost)
            }
          })
          .catch(function (e) {
            self.error = e.message || String(e)
          })
          .finally(function () {
            self.loading = false
          })
      },
      call: function (action, extra, confirmText) {
        if (confirmText && !window.confirm(confirmText)) {
          return
        }
        const id = Number(this.orderId || 0)
        const self = this
        this.loading = true
        request(action, Object.assign({ order_id: id }, extra || {}))
          .then(function (json) {
            if (json.success === false) {
              self.error = json.message || 'Error'
              return
            }
            self.error = ''
            self.reload()
          })
          .catch(function (e) {
            self.error = e.message || String(e)
          })
          .finally(function () {
            self.loading = false
          })
      },
    },
    template:
      '<div class="msp3paymentskeleton-tab">' +
      '<h3>{{ lex.attempts }}</h3>' +
      '<p v-if="error" class="msp3paymentskeleton-tab__error">{{ error }}</p>' +
      '<p v-if="!attempts.length && !loading" class="msp3paymentskeleton-tab__empty">{{ lex.empty }}</p>' +
      '<table v-if="attempts.length">' +
      '<thead><tr><th>ID</th><th>status</th><th>amount</th><th>external_id</th></tr></thead>' +
      '<tbody>' +
      '<tr v-for="row in attempts" :key="row.id">' +
      '<td>{{ row.id }}</td><td>{{ row.status }}</td>' +
      '<td>{{ row.amount }} {{ row.currency }}</td><td>{{ row.external_id }}</td>' +
      '</tr></tbody></table>' +
      '<div class="msp3paymentskeleton-tab__form">' +
      '<label>{{ lex.amount }}<input v-model="amount" type="text"></label>' +
      '<label>{{ lex.reason }}<input v-model="reason" type="text"></label>' +
      '</div>' +
      '<div class="msp3paymentskeleton-tab__actions">' +
      '<button data-primary type="button" :disabled="loading" @click="call(\'mgr/refund\', { amount: amount, reason: reason }, lex.confirm_refund)">{{ lex.refund }}</button>' +
      '<button type="button" :disabled="loading" @click="call(\'mgr/cancel\', {}, lex.confirm_cancel)">{{ lex.cancel }}</button>' +
      '<button type="button" :disabled="loading" @click="call(\'mgr/sync\')">{{ lex.sync }}</button>' +
      '</div>' +
      '</div>',
    computed: {
      lex: function () {
        return getLex()
      },
    },
  }

  // Official MS3 pattern: stub with pendingTabs before order.min.js mounts.
  // Do not retry via DOMContentLoaded + setTimeout — that double-registers the key.
  if (!window.MS3OrderTabsRegistry) {
    window.MS3OrderTabsRegistry = {
      pendingTabs: [],
      register: function (c) {
        this.pendingTabs.push(c)
        return true
      },
    }
  }
  if (window.__msp3PaymentSkeletonTabRegistered) {
    return
  }
  window.__msp3PaymentSkeletonTabRegistered = true
  window.MS3OrderTabsRegistry.register({
    key: 'paymentskeleton',
    title: getLex().tab_title || 'Payment Skeleton',
    type: 'vue',
    component: PaymentSkeletonOrderTab,
    position: 20,
    hideOnCreate: true,
  })
})()
