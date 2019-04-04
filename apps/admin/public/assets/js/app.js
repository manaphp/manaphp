Vue.prototype.$axios = axios;
Vue.prototype.$moment = moment;
Vue.prototype.$qs = Qs;
Vue.prototype._ = _;
axios.defaults.baseURL = 'http://www.manaphp.com';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.interceptors.response.use(function (res) {
        if (typeof res.data === 'string') {
            alert('unexpected response');
        } else {
            return res;
        }
    },
    function (error) {
        switch (error.response.status) {
            case 401:
                window.location.href = '@action(user/login)';
        }
    });
Vue.mixin({
    filters: {
        date: function (value, format='YYYY-MM-DD HH:mm:ss') {
            return value ? moment(value * 1000).format(format) : '';
        },
        json: function (value) {
            return JSON.stringify(typeof value === 'string' ? JSON.parse(value) : value, null, 2);
        }
    },

    methods: {
        ajax_get: function (url, success) {
            this.$axios.get(url).then(function (res) {
                console.log(res);
                if (res.data.code === 0) {
                    success.bind(this)(res.data.data);
                } else {
                    this.$alert(res.data.message);
                }
            }.bind(this));
        },
        ajax_post: function (url, data, success) {
            this.$axios.post(url, data).then(function (res) {
                if (res.data.code === 0) {
                    success.bind(this)(res.data.data);
                } else {
                    this.$alert(res.data.message);
                }
            }.bind(this));
        },
        reload_table: function () {
            var qs = this.$qs.stringify(this.request);
            window.history.replaceState(null, null, qs ? ('?' + qs) : '');
            this.response = [];
            this.$axios.get(document.location.href).then(function (res) {
                if (res.data.code !== 0) {
                    this.$alert(res.data.message);
                } else {
                    this.response = res.data.data;
                }
            }.bind(this));
        },
        format_date: function (value) {
            return value ? this.$moment(value * 1000).format('YYYY-MM-DD HH:mm:ss') : '';
        },
        fDate: function (row, column, value) {
            return this.format_date(value);
        },

        fEnabled: function (row, column, value) {
            return ['禁用', '启用'][value];
        },
    },
    created: function () {
        var qs = this.$qs.parse(document.location.search.substr(1));
        if (this.request) {
            for (var k in qs) {
                this.request[k] = qs[k];
            }
        }
    }
});

Vue.component('pager', {
    props: ['data'],
    template: ' <el-pagination :current-page.sync="data.page"\n' +
        '                   :page-size="data.size"\n' +
        '                   @current-change="$emit(\'change\')"\n' +
        '                   :total="data.count" layout="total, prev, pager, next"></el-pagination>\n',
});