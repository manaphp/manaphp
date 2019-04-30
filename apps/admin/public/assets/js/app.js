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
        reload: function () {
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
    props: ['request', 'response'],
    template: ' <el-pagination :current-page="request.page"\n' +
        '                   :page-size="request.size"\n' +
        '                   :page-sizes="[10,20,25,50,100,500,1000]"\n' +
        '                   @current-change="request.page=$event"\n' +
        '                   @size-change="request.size=$event; request.page=1"\n' +
        '                   :total="response.count" layout="sizes,total, prev, pager, next"></el-pagination>\n',
});

Vue.component('date-picker', {
    props: ['value'],
    template: '<el-date-picker v-model="value" type="daterange" start-placeholder="开始日期"\n ' +
        '                            end-placeholder="结束日期" value-format="yyyy-MM-dd" size="small"\n ' +
        ' :picker-options="pickerOptions" @change="change"' +
        '></el-date-picker>',
    methods: {
        change: function (value) {
            this.$emit('input', value);
        }
    },
    data: function () {
        return {
            pickerOptions: {
                shortcuts: [
                    {
                        text: '今天',
                        onClick: function (picker) {
                            var end = new Date();
                            var start = new Date();
                            start.setTime(start.getTime());
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '昨天',
                        onClick: function (picker) {
                            var end = new Date();
                            var start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24);
                            end.setDate(end.getDate() - 1);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '最近三天',
                        onClick: function (picker) {
                            var end = new Date();
                            var start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 3);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '最近一周',
                        onClick: function (picker) {
                            var end = new Date();
                            var start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '最近一个月',
                        onClick: function (picker) {
                            var end = new Date();
                            var start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '最近三个月',
                        onClick: function (picker) {
                            var end = new Date();
                            var start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '最近一年',
                        onClick: function (picker) {
                            var end = new Date();
                            var start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 365);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '本周',
                        onClick: function (picker) {
                            var end = new Date();
                            var start = new Date();
                            start.setDate(start.getDate() - start.getDay() + 1);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '本月',
                        onClick: function (picker) {
                            var end = new Date();
                            var start = new Date();
                            start.setDate(1);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '本季',
                        onClick: function (picker) {
                            var end = new Date();
                            var start = new Date();
                            start.setMonth(parseInt(start.getMonth() / 3) * 3);
                            start.setDate(1);
                            picker.$emit('pick', [start, end]);
                        }
                    }
                ]
            }
        }
    }
});