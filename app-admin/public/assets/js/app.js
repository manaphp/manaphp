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
    template: ` 
 <el-pagination background :current-page="Number($root.response.page)"
       :page-size="Number($root.request.size)"
       :page-sizes="[10,20,25,50,100,500,1000]"
       @current-change="$root.request.page=$event"
       @size-change="$root.request.size=$event; $root.request.page=1"
       :total="$root.response.count" layout="sizes,total, prev, pager, next, jumper">
</el-pagination>`,
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
    template: `
<el-date-picker v-model="time" type="daterange" 
    start-placeholder="开始日期" end-placeholder="结束日期" 
    value-format="yyyy-MM-dd" 
    size="small"
    style="width: 215px"
    :picker-options="pickerOptions" @change="change">
</el-date-picker>`,
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
                <el-menu-item :index="baseURL+item.url" @click="click(item)"><i :class="item.icon" ></i>
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
    },
    methods: {
        click(item) {
            let tabs = sessionStorage.getItem('.my-menu-tabs');
            tabs = tabs ? JSON.parse(tabs) : {};

            tabs[item.url] = {name: item.item_name, url: item.url};
            sessionStorage.setItem('.my-menu-tabs', JSON.stringify(tabs));
        }
    }
});

Vue.component('my-menu-tabs', {
    template: `
<el-tabs  @tab-remove="remove" @tab-click="click" class="my-menu-tabs" v-model="tab">
    <el-tab-pane label="首页" name="/"></el-tab-pane>
    <el-tab-pane v-for="tab in tabs" :label="tab.name" closable :name="tab.url"></el-tab-pane>     
</el-tabs>`,
    data() {
        let tabs = sessionStorage.getItem('.my-menu-tabs');
        tabs = tabs ? JSON.parse(tabs) : {};

        return {
            tabs: tabs,
            tab: location.pathname.substr(window.BASE_URL.length)
        }
    },
    methods: {
        remove(name) {
            let tabs = JSON.parse(sessionStorage.getItem('.my-menu-tabs'));
            delete tabs[name];
            this.$delete(this.tabs, name);
            sessionStorage.setItem('.my-menu-tabs', JSON.stringify(tabs));
        },

        click(tab) {
            let url = '';
            if (tab.name !== '/') {
                let tabs = JSON.parse(sessionStorage.getItem('.my-menu-tabs'));
                url = tabs[tab.name].url;
            }
            console.log(url)
            window.location.href = window.BASE_URL + url;
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
            axios.get('/admin/time?t=' + Date.now()).then((res) => {
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
    template: `<el-button @click="$root.show_edit(row);$emit('click')" size="mini" type="primary" icon="el-icon-edit" title="编辑"></el-button>`
});

Vue.component('show-delete', {
    props: ['row'],
    template: '<el-button @click="$root.do_delete(row)" size="mini" type="danger" icon="el-icon-delete" title="删除"></el-button>'
});

Vue.component('show-detail', {
    props: ['row', 'link'],
    template: '<el-button @click="$root.show_detail(row,link)" size="mini" type="info" icon="el-icon-more" title="详情"></el-button>'
});

Vue.component('show-enable', {
    props: ['row'],
    template: `
<el-button v-if="row.enabled" @click.native.prevent="$root.do_disable(row)" size="mini" type="danger" icon="el-icon-lock" title="禁用"></el-button>
<el-button v-else @click.native.prevent="$root.do_enable(row)" size="mini" type="warning" icon="el-icon-unlock" title="启用"></el-button>`
});

Vue.component('create-dialog', {
    template: `
<el-dialog :title="'新增-'+$root.topic" :visible.sync="$root.createVisible" class="create-dialog">
    <slot></slot>
    <span slot="footer">
        <el-button type="primary" @click="$root.do_create" size="small">创建</el-button>
        <el-button @click="$root.createVisible = false; $root.$refs.create.resetFields()" size="small">取消</el-button>
    </span>
</el-dialog>`
});

Vue.component('edit-dialog', {
    template: `
<el-dialog :title="'编辑-'+$root.topic" :visible.sync="$root.editVisible" class="edit-dialog">
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
    props: ['value', 'data', 'disabled'],
    template: `
<span>
    <el-select v-model="val" size="small" clearable style="width: 150px" @change="$emit('input', $event)" :disabled="disabled">
        <el-option v-for="item in extract_ill(data)" :key="item.id" :label="item.label" :value="String(item.id)"></el-option>
    </el-select>
</span>`,
    data() {
        return {val: String(this.value)};
    },
    watch: {
        value(val) {
            this.val = String(val);
        }
    }
});

Vue.component('request-form', {
    template: `
<div class="request-form">
    <el-button v-if="$root.create" @click="$root.createVisible=true;$emit('open-create');" type="primary" icon="el-icon-plus" size="small">新增{{$root.topic}}</el-button>
    <slot></slot>   
</div>`,
});

Vue.component('request-text', {
    props: ['prop', 'width', 'placeholder'],
    template: `
<el-input v-model.trim="$root.request[prop]"
    size="small" clearable 
    :style="{width: (width||150)+'px'}"
    :placeholder="placeholder||$root.label[prop]||prop"
    v-bind="$attrs">
</el-input>`
});

Vue.component('request-date', {
    props: ['prop'],
    template: `<date-picker v-model.trim="$root.request[prop]"></date-picker>`
});

Vue.component('request-select', {
    props: ['prop', 'data'],
    template: `<selector v-model.trim="$root.request[prop]" :data="data"></selector>`
});

Vue.component('request-button', {
    template: `<el-button size="small" @click="$emit('click')" v-bind="$attrs""><slot></slot></el-button>`
});

Vue.component('create-form', {
    template: `
<el-dialog :title="'新增-'+$root.topic" :visible.sync="$root.createVisible" class="create-dialog" @opened="$root.$refs.create=$refs.create">
    <el-form :model="$root.create" ref="create" size="small">
        <slot></slot>
    </el-form>
    <span slot="footer">
         <el-button type="primary" @click="$root.do_create" size="small">创建</el-button>
        <el-button @click="$root.createVisible = false; $root.$refs.create.resetFields()" size="small">取消</el-button>
    </span>
</el-dialog>`,
});

Vue.component('create-text', {
    props: ['label', 'prop', 'disabled'],
    template: `
<el-form-item :label="(label||$root.label[prop]||prop)+':'" :prop="prop">
    <el-input v-model="$root.create[prop]" auto-complete="off" :disabled="disabled" @change="$emit('input', $event)"></el-input>
</el-form-item>`
});

Vue.component('create-textarea', {
    props: ['label', 'prop', 'rows', 'disabled'],
    template: `
<el-form-item :label="(label||$root.label[prop]||prop)+':'" :prop="prop">
    <el-input v-model="$root.create[prop]" auto-complete="off" type="textarea" :rows="rows" :disabled="disabled" @change="$emit('input', $event)"></el-input>
</el-form-item>`
});

Vue.component('create-checkbox', {
    props: ['label', 'prop', 'disabled'],
    template: `
<el-form-item :label="(label||$root.label[prop]||prop)+':'" :prop="prop">
    <el-checkbox v-model="$root.create[prop]" :disabled="disabled"><slot></slot></el-checkbox>
</el-form-item>`
});

Vue.component('create-radio', {
    props: ['label', 'prop', 'data', 'disabled'],
    template: `
<el-form-item :label="(label||$root.label[prop]||prop)+':'" :prop="prop">
    <el-radio-group v-model="$root.create[prop]" :disabled="disabled">
        <el-radio v-for="(status, id) in data" :label="id" :key="id">{{status}}</el-radio>
    </el-radio-group>
</el-form-item>`
});

Vue.component('create-select', {
    props: ['label', 'prop', 'data', 'disabled'],
    template: `
<el-form-item :label="(label||$root.label[prop]||prop)+':'">
    <selector v-model="$root.create[prop]" :data="data" :disabled="disabled"></selector>
</el-form-item>`
});

Vue.component('create-switch', {
    props: ['label', 'prop', 'disabled'],
    template: `
<el-form-item :label="(label||$root.label[prop]||prop)+':'">
    <el-switch v-model="$root.create[prop]" :disabled="disabled"></el-switch>
</el-form-item>`
});

Vue.component('edit-form', {
    template: `
<el-dialog :title="'编辑-'+$root.topic" :visible.sync="$root.editVisible" class="edit-dialog" @opened="$root.$refs.edit=$refs.edit">
     <el-form :model="$root.edit" ref="edit" size="small">
        <slot></slot>
    </el-form>
    <span slot="footer">
        <el-button type="primary" @click="$root.do_edit" size="small">保存</el-button>
        <el-button @click="$root.editVisible=false" size="small">取消</el-button>
    </span>
</el-dialog>`,
});
Vue.component('edit-text', {
    props: ['label', 'prop', 'disabled'],
    template: `
<el-form-item :label="(label||$root.label[prop]||prop)+':'" :prop="prop">
    <el-input v-model="$root.edit[prop]" auto-complete="off" :disabled="disabled" @change="$emit('input', $event)"></el-input>
</el-form-item>`
});

Vue.component('edit-textarea', {
    props: ['label', 'prop', 'disabled', 'rows'],
    template: `
<el-form-item :label="(label||$root.label[prop]||prop)+':'" :prop="prop">
    <el-input v-model="$root.edit[prop]" auto-complete="off" type="textarea" :disabled="disabled" @change="$emit('input', $event)" :rows="rows"></el-input>
</el-form-item>`
});

Vue.component('edit-select', {
    props: ['label', 'prop', 'data', 'disabled'],
    template: `
<el-form-item :label="(label||$root.label[prop]||prop)+':'">
    <selector v-model="$root.edit[prop]" :disabled="disabled" :data="data"></selector>
</el-form-item>`
});

Vue.component('edit-radio', {
    props: ['label', 'prop', 'data', 'disabled'],
    template: `
<el-form-item :label="(label||$root.label[prop]||prop)+':'" :prop="prop">
    <el-radio-group v-model="$root.edit[prop]" :disabled="disabled">
        <el-radio v-for="(status, id) in data" :label="id" :key="id">{{status}}</el-radio>
    </el-radio-group>
</el-form-item>`
});

Vue.component('detail-form', {
    template: `
<el-dialog title="详情" :visible.sync="$root.detailVisible" class="detail-dialog" @opened="$root.$refs.detail=$refs.detail">
    <el-form :model="$root.detail" ref="detail" label-width="150px" size="mini">
        <slot></slot>
    </el-form>
</el-dialog>`,
    beforeCreate() {
        this.$root.hasDetail = true;
    }
});

Vue.component('detail-text', {
    props: ['label', 'prop', 'disabled'],
    template: `<el-form-item :label="(label||$root.label[prop]||prop)+':'">{{ $root.detail[prop]}}</el-form-item>`
});

Vue.component('detail-timestamp', {
    props: ['label', 'prop', 'disabled'],
    template: `<el-form-item :label="(label||$root.label[prop]||prop)+':'">{{ $root.detail[prop]|date}}</el-form-item>`
});

Vue.component('detail-json', {
    props: ['label', 'prop', 'disabled'],
    template: `<el-form-item :label="(label||$root.label[prop]||prop)+':'"><pre>{{ $root.detail[prop]|json}}</pre></el-form-item>`
});

Vue.component('result-index', {
    template: `<el-table-column type="index" label="#" width="50"></el-table-column>`
});

Vue.component('result-id', {
    props: ['label', 'prop'],
    template: `<el-table-column :prop="prop" :label="label||$root.label[prop]||prop" width="100"></el-table-column>`
});

Vue.component('result-account', {
    props: ['label', 'prop'],
    template: `<el-table-column :prop="prop" :label="label||$root.label[prop]||prop" width="120"></el-table-column>`
});

Vue.component('result-email', {
    template: `<el-table-column prop="email" label="邮箱" with="200" show-overflow-tooltip></el-table-column>`
});

Vue.component('result-ip', {
    props: ['label', 'prop'],
    template: `
<el-table-column :prop="prop" :label="label||$root.label[prop]||prop" width="120" v-slot="{row}">
    <span v-if="row[prop]==='127.0.0.1'||row[prop]==='::1'||row[prop].startsWith('192.168.')">{{row[prop]}}</span>
    <a v-else target="_blank" class="el-link" :href="'https://www.baidu.com/s?wd='+row[prop]" type="primary">{{row[prop]}}</a>
</el-table-column>`
});

Vue.component('result-enabled', {
    template: `<el-table-column prop="enabled" :formatter="$root.fEnabled" label="状态" width="100"></el-table-column>`
});

Vue.component('result-timestamp', {
    props: ['label', 'prop'],
    template: `<el-table-column :prop="prop" :label="label||$root.label[prop]||prop" :formatter="$root.fDate" width="150"></el-table-column>`
});

Vue.component('result-column', {
    props: ['label', 'prop'],
    template: `
<el-table-column :prop="prop" :label="label||$root.label[prop]||prop" v-bind="$attrs" v-slot="{row}">
    <slot :row="row">{{$attrs['formatter']?$attrs['formatter'](row, prop, getProp(row,prop)):getProp(row,prop)}}</slot>
</el-table-column>`,
    methods: {
        getProp(row, prop) {
            if (prop === undefined) {
                return '';
            }
            let v = row;
            for (let part of prop.split('.')) {
                if (!v.hasOwnProperty(part)) {
                    return '';
                }
                v = v[part];
            }

            return v;
        }
    }
});

Vue.component('result-tag', {
    props: ['label', 'prop'],
    template: `
<el-table-column :label="label||$root.label[prop]||prop" v-bind="$attrs" v-slot="{row}">
    <el-tag size="small" v-for="item in extract_ill(row[prop])" :key="item.id">{{item.label}}</el-tag>
</el-table-column>`,
});

Vue.component('result-link', {
    props: ['label', 'prop', 'href'],
    template: `
<el-table-column :label="label||$root.label[prop]||prop" width="100" v-slot="{row}">
    <a :href="href+(href.includes('?')?'&':'?')+prop+'='+row[prop]"><slot>{{row[prop]}}</slot></a>
</el-table-column>`
});

Vue.component('result-op', {
    props: ['show-detail', 'detail-link', 'show-edit', 'show-enable', 'show-delete', 'width'],
    template: `
<el-table-column fixed="right" label="操作" :width="calcWidth()" v-slot="{row}">
    <show-detail v-if="$root.hasDetail&&showDetail!==false" :row="row" :link="detailLink"></show-detail>
    <show-edit v-if="$root.edit&&showEdit!==false" :row="row"></show-edit>
    <slot :row="row"></slot>
    <show-enable v-if="showEnable===''||showEnable===true" :row="row"></show-enable>
    <show-delete v-if="showDelete===''||showDelete===true" :row="row"></show-delete>
</el-table-column>`,
    methods: {
        calcWidth() {
            if (this.width > 0) {
                return this.width;
            }

            let count = 0;
            count += !!this.$root.hasDetail && this.showDetail !== false;
            count += !!this.$root.edit && this.showEdit !== false;
            count += this.showEnable === '' || this.showEnable === true;
            count += this.showDelete === '' || this.showDelete === true;

            return count * 80;
        }
    }
});

Vue.component('result-button', {
    template: `<el-button size="mini" v-bind="$attrs" @click="$emit('click')"><slot></slot></el-button>`
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

Vue.prototype.extract_ill = function (data) {
    if (!Array.isArray(data)) {
        return [];
    } else if (!data || data.length === 0) {
        return [];
    } else if (typeof data[0] === 'object') {
        return data.map(v => {
            let [id, label] = Object.values(v);
            return {id, label};
        });
    } else {
        return data.map(v => ({id: v, label: v}));
    }
};

App = Vue.extend({
    data() {
        return {
            topic: '',
            label: {
                id: 'ID',
                admin_id: '用户ID',
                admin_name: '用户名',
                created_time: '创建时间',
                updated_time: '更新时间',
                creator_name: '创建者',
                updator_name: '更新者',
                data: '数据',
                client_ip: '客户端IP',
                display_order: '排序',
                display_name: '显示名称',
                password: '密码',
                white_ip: 'IP白名单',
                status: '状态',
                email: '邮箱',
                tag: 'Tag',
                icon: '图标',
                path: '路径',
            },
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

            if (Array.isArray(this.response)) {
                this.response = [];
            }

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

            let key = Object.keys(row)[0];
            this.ajax_get(CONTROLLER_URL + '/' + (action ? action : "detail"), {[key]: row[key]}, (res) => {
                this.detail = res;
            });
        },
        do_delete(row, name = '') {
            let keys = Object.keys(row);
            let key = keys[0];

            if (!name) {
                name = (keys[1] && keys[1].indexOf('_name')) ? row[keys[1]] : row[key];
            }

            if (window.event.ctrlKey) {
                this.ajax_post(CONTROLLER_URL + "/delete", {[key]: row[key]}, () => this.reload());
            } else {
                this.$confirm('确认删除 `' + (name ? name : row[key]) + '` ?').then(() => {
                    this.ajax_post(CONTROLLER_URL + "/delete", {[key]: row[key]}, () => this.reload());
                });
            }
        },
        do_enable(row) {
            let key = Object.keys(row)[0];
            this.ajax_post(CONTROLLER_URL + "/enable", {[key]: row[key]}, () => row.enabled = 1);
        },
        do_disable(row) {
            let key = Object.keys(row)[0];
            this.ajax_post(CONTROLLER_URL + "/disable", {[key]: row[key]}, () => row.enabled = 0);
        }
    },
});
