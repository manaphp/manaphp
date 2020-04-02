Vue.prototype.$axios = axios;
Vue.prototype.$moment = moment;
Vue.prototype.$qs = Qs;
Vue.prototype._ = _;
Vue.prototype.sessionStorage = window.sessionStorage;
Vue.prototype.localStorage = window.localStorage;
Vue.prototype.console = console;

CONTROLLER_URL = location.pathname.substr(BASE_URL.length);
if (CONTROLLER_URL.endsWith('/index')) {
    CONTROLLER_URL = CONTROLLER_URL.substr(0, CONTROLLER_URL.length - 6);
}

(function () {
    let urlKey = `last_url_query.${document.location.pathname}`;
    window.onbeforeunload = (e) => {
        sessionStorage.setItem(urlKey, document.location.search);
    };

    if (document.location.search !== '' || document.referrer === '') {
        return;
    }
    let last_url_query = sessionStorage.getItem(urlKey);
    window.history.replaceState(null, null, last_url_query === null ? '' : last_url_query);
}());


document.location.query = document.location.search !== '' ? Qs.parse(document.location.search.substr(1)) : {};

axios.defaults.baseURL = BASE_URL;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

axios.interceptors.request.use(function (config) {
    if (typeof vm.loading == 'boolean') {
        vm.loading = true;
    }

    config.url += config.url.indexOf('?') === -1 ? '?ajax' : '&ajax';
    return config;
});

axios.interceptors.response.use(function (res) {
        if (typeof vm.loading === 'boolean') {
            vm.loading = false;
        }

        if (!Vue.config.silent) {
            var now = new Date();
            console.warn('[AJAX][%s.%i][%s][%s] %o', now.toLocaleString('en-GB'), now.getMilliseconds(), res.headers['x-response-time'], res.config.url, res);
        }

        if (typeof res.data === 'string') {
            vm.$alert(res.data, '服务器错误', {customClass: 'error-response'});
        }

        if (res.data.code === 0) {
            if (res.data.message) {
                vm.$message({type: 'success', duration: 1000, message: res.data.message});
            } else if (res.config.method !== 'get') {
                vm.$message({type: 'success', duration: 1000, message: '操作成功'});
            }
        }

        for (let name in res.headers) {
            let value = res.headers[name];
            if (value.match(/^https?:\/\//)) {
                console.warn('*'.repeat(32) + ' ' + name + ': ' + value);
            }
        }

        return res;
    },
    function (error) {
        if (typeof vm.loading == 'boolean') {
            vm.loading = false;
        }

        console.log(error);
        if (error.response.status) {
            switch (error.response.status) {
                case 400:
                    alert(error.response.data.message);
                    break;
                case 401:
                    window.location.href = '/login';
                    break;
                default:
                    alert(error.response.message || '网络错误，请稍后重试: ' + error.response.status);
                    break;
            }
        } else {
            alert('网络错误，请稍后重试。');
        }
    });

Vue.prototype.ajax_get = function (url, data, success) {
    if (typeof data === 'function') {
        success = data;
        data = null;
    } else if (data) {
        url += (url.indexOf('?') === -1 ? '?' : '&') + Qs.stringify(data);
    }

    let cache_key = null;
    if (success && url.match(/\bcache=[12]\b/) && localStorage.getItem('axios.cache.enabled') !== '0') {
        cache_key = 'axios.cache.' + url;
        let cache_value = sessionStorage.getItem(cache_key);
        if (cache_value) {
            success.bind(this)(JSON.parse(cache_value));
            return;
        }
    }

    return this.$axios.get(url).then((res) => {
        if (res.data.code === 0) {
            if (success) {
                if (cache_key) {
                    sessionStorage.setItem(cache_key, JSON.stringify(res.data.data));
                }
                success.bind(this)(res.data.data);
            }
        } else if (res.data.message) {
            this.$alert(res.data.message);
        }
        return res;
    });
};

Vue.prototype.ajax_post = function (url, data, success) {
    if (typeof data === 'function') {
        success = data;
        data = {};
    }

    let config = {};
    if (data instanceof FormData) {
        config.headers = {'Content-Type': 'multipart/form-data'};
    }

    return this.$axios.post(url, data, config).then((res) => {
        if (res.data.code === 0 && success) {
            success.bind(this)(res.data.data);
        }

        if (res.data.message !== '') {
            this.$alert(res.data.message);
        }
        return res
    });
};

Vue.filter('date', function (value, format = 'YYYY-MM-DD HH:mm:ss') {
    return value ? moment(value * 1000).format(format) : '';
});

Vue.filter('json', function (value) {
    return JSON.stringify(typeof value === 'string' ? JSON.parse(value) : value, null, 2);
});

Vue.component('pager', {
    template: ' <el-pagination background :current-page="Number($root.response.page)"\n' +
        '                   :page-size="Number($root.request.size)"\n' +
        '                   :page-sizes="[10,20,25,50,100,500,1000]"\n' +
        '                   @current-change="$root.request.page=$event"\n' +
        '                   @size-change="$root.request.size=$event; $root.request.page=1"\n' +
        '                   :total="$root.response.count" layout="sizes,total, prev, pager, next, jumper"></el-pagination>\n',
    watch: {
        '$root.request': {
            handler() {
                for (let field in this.$root.request) {
                    if (field === 'page' || field === 'size') {
                        continue;
                    }
                    if (field in this.last && this.last[field] != this.$root.request[field]) {
                        this.$root.request.page = 1;
                        this.last = Object.assign({}, this.$root.request);
                        break;
                    }
                }
            },
            deep: true
        }
    },
    data() {
        return {
            last: Object.assign({}, this.$root.request)
        }
    }
});

Vue.component('date-picker', {
    props: ['value'],
    template: '<el-date-picker v-model="time" type="daterange" start-placeholder="开始日期"\n ' +
        '                            end-placeholder="结束日期" value-format="yyyy-MM-dd" size="small"\n ' +
        ' :picker-options="pickerOptions" @change="change"' +
        '></el-date-picker>',
    methods: {
        change(value) {
            this.$emit('input', value);
        }
    },
    watch: {
        value(val) {
            this.time = val;
        }
    },
    data() {
        return {
            time: this.value,
            pickerOptions: {
                shortcuts: [
                    {
                        text: '今天',
                        onClick(picker) {
                            let end = new Date();
                            let start = new Date();
                            start.setTime(start.getTime());
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '昨天',
                        onClick(picker) {
                            let end = new Date();
                            let start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24);
                            end.setDate(end.getDate() - 1);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '最近三天',
                        onClick(picker) {
                            let end = new Date();
                            let start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 3);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '最近一周',
                        onClick(picker) {
                            let end = new Date();
                            let start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '最近一个月',
                        onClick(picker) {
                            let end = new Date();
                            let start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '最近三个月',
                        onClick(picker) {
                            let end = new Date();
                            let start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '最近一年',
                        onClick(picker) {
                            let end = new Date();
                            let start = new Date();
                            start.setTime(start.getTime() - 3600 * 1000 * 24 * 365);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '本周',
                        onClick(picker) {
                            let end = new Date();
                            let start = new Date();
                            start.setDate(start.getDate() - start.getDay() + 1);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '本月',
                        onClick(picker) {
                            let end = new Date();
                            let start = new Date();
                            start.setDate(1);
                            picker.$emit('pick', [start, end]);
                        }
                    },
                    {
                        text: '本季',
                        onClick(picker) {
                            let end = new Date();
                            let start = new Date();
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

Vue.component('my-menu', {
    template: `
<el-menu :default-active="active" class="el-menu-vertical-demo" :unique-opened="true">
    <template v-for="group in groups">
        <el-submenu :index="group.group_name">
            <template slot="title">
                <i :class="group.icon"></i>
                <span>{{group.group_name}}</span>
            </template>
            <template v-for="item in group.items ">
                <el-menu-item :index="baseURL+item.url"><i :class="item.icon"></i>
                    <el-link :href="baseURL+item.url">{{item.item_name}}</el-link>
                </el-menu-item>
            </template>
        </el-submenu>
     </template>
</el-menu>`,
    created() {
        this.ajax_get('/menu/my?cache=2', (res) => {
            this.groups = res;

            for (let group of res) {
                for (let item of group.items) {
                    if (item.url === this.active) {
                        document.title = ' ' + group.group_name + ' - ' + item.item_name;
                        break;
                    }
                }
            }
        })
    },
    data() {
        return {
            baseURL: window.BASE_URL,
            active: location.pathname,
            groups: []
        }
    }
});

Vue.component('axios-cache-switcher', {
    template: `
    <div>
        <div v-if="enabled" @click="localStorage.setItem('axios.cache.enabled','0');enabled=false">缓存 (√)</div>
        <div v-else @click="localStorage.setItem('axios.cache.enabled','1');enabled=true">缓存 (×)</div>
    </div>`,
    data() {
        return {
            enabled: localStorage.getItem('axios.cache.enabled') !== '0'
        }
    }
});

Vue.component('system-time', {
    template: '<span class="left" :title="diff.toFixed(3)" :style="{backgroundColor: bgColor}" style="cursor:pointer"" v-show="diff!==null&&time!==null" @click="update">{{time}}</span>',
    data() {
        return {
            diff: null,
            time: null,
            key: 'system-time.diff'
        }
    },
    computed: {
        bgColor() {
            return Math.abs(this.diff) >= 2 ? 'red' : 'parent';
        }
    },
    created() {
        setInterval(() => {
            if (this.diff !== null) {
                this.time = (moment().subtract(this.diff, 'seconds').format('YYYY-MM-DD hh:mm:ss'))
            }
        }, 1000);

        let diff = window.sessionStorage.getItem(this.key);
        if (diff === null) {
            this.update();
        } else {
            this.diff = parseFloat(diff);
        }
    },
    methods: {
        update() {
            axios.get('/index/time?t=' + Date.now()).then((res) => {
                if (res.data.code === 0) {
                    this.diff = Math.round(Date.now() - res.data.data.timestamp * 1000) / 1000;
                    window.sessionStorage.setItem(this.key, this.diff);
                }
            });
        }
    }
});

Vue.component('show-create', {
    template: `<el-button @click="$root.createVisible=true;$emit('click');" type="primary" icon="el-icon-plus" size="small">新增{{$root.topic}}</el-button>`
});

Vue.component('show-edit', {
    props: ['row'],
    template: `<el-button @click="$root.show_edit(row);$emit('click')" size="mini" type="primary"><slot>编辑</slot></el-button>`
});

Vue.component('show-delete', {
    props: ['row'],
    template: '<el-button @click="$root.do_delete(row)" size="mini" type="danger"><slot>删除</slot></el-button>'
});

Vue.component('show-detail', {
    props: ['row', 'link'],
    template: '<el-button @click="$root.show_detail(row,link)" size="mini" type="info"><slot>详情</slot></el-button>'
});

Vue.component('show-enable', {
    props: ['row'],
    template: `<el-button v-if="row.enabled" @click.native.prevent="$root.do_disable(row)" size="mini"
                           type="danger">禁用
                </el-button>
                <el-button v-else @click.native.prevent="$root.do_enable(row)" size="mini"
                           type="warning">启用
                </el-button>`
});

Vue.component('show-active', {
    props: ['row'],
    template: ` <el-button v-if="row.status==1" @click="$root.do_lock(row)" size="mini" type="danger">
                    锁定
                </el-button>
                <el-button v-else="row.status!==1" @click="$root.do_active(row)" size="mini" type="warning">激活
                </el-button>`
});

Vue.component('create-dialog', {
    template: `<el-dialog :title="'新增-'+$root.topic" :visible.sync="$root.createVisible" class="create-dialog">
    <slot></slot>
    <span slot="footer">
         <el-button type="primary" @click="$root.do_create" size="small">创建</el-button>
        <el-button @click="$root.createVisible = false; $root.$refs.create.resetFields()" size="small">取消</el-button>
    </span>
</el-dialog>`
});

Vue.component('edit-dialog', {
    template: `<el-dialog :title="'编辑-'+$root.topic" :visible.sync="$root.editVisible" class="edit-dialog">
      <slot></slot>
    <span slot="footer">
        <el-button type="primary" @click="$root.do_edit" size="small">保存</el-button>
        <el-button @click="$root.editVisible=false" size="small">取消</el-button>
    </span>
</el-dialog>`
});

Vue.component('detail-dialog', {
    template: `<el-dialog title="详情" :visible.sync="$root.detailVisible" class="detail-dialog"><slot></slot></el-dialog>`
});

Vue.component('result-table', {
    props: ['data'],
    template: `
<div class="result-table">
    <el-table v-if="data" :data="data" border size="small">
        <slot></slot>
    </el-table>
    <template v-else-if="$root.request.page">
        <pager></pager>
        <el-table :data="$root.response.items" border size="small">
            <slot></slot>
        </el-table>
        <pager></pager>
    </template>
    <el-table v-else :data="$root.response" border size="small">
        <slot></slot>
    </el-table>
</div>`,
});

Vue.component('selector', {
    props: ['value', 'data'],
    template: `
<span>
    <el-select v-if="data && typeof data[0]==='object'" v-model="val" size="small" clearable style="width: 150px" @change="$emit('input', $event)">
        <el-option v-for="item in data" :key="item[key]" :label="item[label]" :value="String(item[key])"></el-option>
    </el-select>
    <el-select v-else v-model="val" size="small" clearable style="width: 150px" @change="$emit('input', $event)">
        <el-option v-for="item in data" :key="item" :label="item" :value="String(item)"></el-option>
    </el-select>
</span>`,
    data() {
        let key = '';
        let label = '';
        let val = String(this.value);

        if (this.data.length > 0 && typeof this.data[0] === 'object') {
            [key, label] = Object.keys(this.data[0]);
        }
        return {key, label, val};
    },
    watch: {
        data() {
            if (this.data.length > 0 && typeof this.data[0] === 'object') {
                [this.key, this.label] = Object.keys(this.data[0]);
            }
        },
        value(val) {
            this.val = String(val);
        }
    }
});

Vue.prototype.format_date = function (value) {
    return value ? this.$moment(value * 1000).format('YYYY-MM-DD HH:mm:ss') : '';
};

Vue.prototype.auto_reload = function () {
    if (this.request && this.response) {
        let qs = this.$qs.parse(document.location.query);
        for (let k in qs) {
            this.$set(this.request, k, qs[k]);
        }

        this.reload().then(() => this.$watch('request', _.debounce(() => this.reload(), 500), {deep: true}));
    }
};

App = Vue.extend({
    data() {
        return {
            topic: '',
            createVisible: false,
            editVisible: false,
            detailVisible: false,
            detail: {},
        }
    },
    methods: {
        reload() {
            if (!this.request || !this.response) {
                alert('bad reload');
            }

            let qs = this.$qs.stringify(this.request);
            window.history.replaceState(null, null, qs ? ('?' + qs) : '');
            document.location.query = document.location.search !== '' ? Qs.parse(document.location.search.substr(1)) : {};
            this.response = [];
            return this.$axios.get(document.location.href).then((res) => {
                if (res.data.code !== 0) {
                    this.$alert(res.data.message);
                } else {
                    this.response = res.data.data;
                }
            });
        },
        fDate(row, column, value) {
            return this.format_date(value);
        },

        fEnabled(row, column, value) {
            return ['禁用', '启用'][value];
        },
        do_create(create) {
            let success = true;
            if (typeof create === 'string') {
                this.$refs[create].validate(valid => success = valid);
            }
            success && this.ajax_post(CONTROLLER_URL + "/create", this.create, (res) => {
                this.createVisible = false;
                this.$refs.create.resetFields();
                this.reload();
            });
        },
        show_edit(row, overwrite = {}) {
            if (Object.keys(this.edit).length === 0) {
                this.edit = Object.assign({}, row, overwrite);
            } else {
                for (let key in this.edit) {
                    if (row.hasOwnProperty(key)) {
                        this.edit[key] = row[key];
                    }
                }
                for (let key in overwrite) {
                    this.edit[key] = overwrite[key]
                }
            }

            this.editVisible = true;
        },
        do_edit() {
            this.ajax_post(CONTROLLER_URL + "/edit", this.edit, () => {
                this.editVisible = false;
                this.reload();
            });
        },
        show_detail(row, action) {
            this.detailVisible = true;

            let data = {};
            let key = Object.keys(row)[0];
            data[key] = row[key];

            this.ajax_get(CONTROLLER_URL + '/' + (action ? action : "detail"), data, (res) => {
                this.detail = res;
            });
        },
        do_delete(row, name = '') {
            let data = {};
            let keys = Object.keys(row);
            let key = keys[0];
            data[key] = row[key];

            if (!name) {
                name = (keys[1] && keys[1].indexOf('_name')) ? row[keys[1]] : row[key];
            }

            if (window.event.ctrlKey) {
                this.ajax_post(CONTROLLER_URL + "/delete", data, () => this.reload());
            } else {
                this.$confirm('确认删除 `' + (name ? name : row[key]) + '` ?').then(() => {
                    this.ajax_post(CONTROLLER_URL + "/delete", data, () => this.reload());
                });
            }
        },
    },
});
