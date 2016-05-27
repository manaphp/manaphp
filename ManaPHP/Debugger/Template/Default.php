<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ManaPHP Debug</title>
    <link rel="stylesheet" href="http://ajax.aspnetcdn.com/ajax/bootstrap/3.3.5/css/bootstrap.min.css">
    <!--[if lt IE 9]>
    <script src="http://ajax.aspnetcdn.com/ajax/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
        #tab_global tr td:nth-child(1) {
            width: 5%;
        }

        #tab_global tr td:nth-child(2) {
            width: 20%;
        }

        body {
            overflow-y: scroll;
        }

        table pre {
            border: none;
            padding: 0;
            margin: 0;
            background-color: transparent;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 80px;
        }

    </style>
</head>
<body>
<div class="container" id="app">
    <ul class="nav nav-tabs">
        <li><a href="#tab_basic" data-toggle="tab">Basic</a></li>
        <li><a href="#tab_dump" data-toggle="tab">Dump</a></li>
        <li><a href="#tab_view" data-toggle="tab">View</a></li>
        <li><a href="#tab_exception" data-toggle="tab">Exception</a></li>
        <li><a href="#tab_configure" data-toggle="tab">Configure</a></li>
        <li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Global <b class="caret"></b></a>
            <ul class="dropdown-menu">
                <li><a data-toggle="tab" @click="global_type='request'" href="#tab_global">REQUEST</a></li>
                <li><a data-toggle="tab" @click="global_type='get'" href="#tab_global">GET</a></li>
                <li><a data-toggle="tab" @click="global_type='post'" href="#tab_global">POST</a></li>
                <li><a data-toggle="tab" @click="global_type='session'" href="#tab_global">SESSION</a></li>
                <li><a data-toggle="tab" @click="global_type='cookie'" href="#tab_global">COOKIE</a></li>
                <li><a data-toggle="tab" @click="global_type='server'" href="#tab_global">SERVER</a></li>
            </ul>
        </li>
        <li><a href="#tab_log" data-toggle="tab">Log</a></li>
        <li><a href="#tab_sql" data-toggle="tab">SQL</a></li>
        <li><a href="#tab_included_files" data-toggle="tab">IncludedFiles</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane" id="tab_basic">
            <h4>Basic Information</h4>
            <table class="table table-striped table-bordered table-condensed">
                <thead>
                <tr>
                    <td style="width: 5%">#</td>
                    <td>name</td>
                    <td>value</td>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(name,value) in basic">
                    <td>{{$index}}</td>
                    <td>{{name}}</td>
                    <td>{{value}}</td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="tab-pane" id="tab_dump">
            <h4>Dump Data({{dump.length}})</h4>
            <table class="table table-striped table-bordered table-condensed">
                <thead>
                <tr>
                    <td width="5%">#</td>
                    <td width="15%">location</td>
                    <td>name</td>
                    <td>value</td>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in dump">
                    <td>{{$index}}</td>
                    <td title="{{item.file}}:{{item.line}}">{{item.base_name}}:{{item.line}}</td>
                    <td>{{item.name}}</td>
                    <td title="{{item.value|json 4}}">
                        <pre>{{item.value|json 4}}</pre>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="tab-pane" id="tab_view">
            <h4>Renderer Files({{view.length}})</h4>
            <table class="table table-striped table-bordered table-condensed">
                <thead>
                <tr>
                    <td style="width: 5%">#</td>
                    <td style="width: 20%">file</td>
                    <td>vars</td>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in view">
                    <td>{{$index}}</td>
                    <td title="{{item.file}}">{{item.base_name}}</td>
                    <td>
                        <pre>{{item.vars|json 4}}</pre>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="tab-pane" id="tab_exception">
            <h4>Exception</h4>
            <table class="table table-striped table-bordered table-condensed">
                <thead>
                <tr>
                    <td>name</td>
                    <td>value</td>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>message</td>
                    <td>{{exception.message}}</td>
                </tr>
                <tr>
                    <td>code</td>
                    <td>{{exception.code}}</td>
                </tr>
                <tr>
                    <td>location</td>
                    <td>{{exception.file}}:{{exception.line}}</td>
                </tr>
                </tbody>
            </table>
            <table class="table table-striped table-bordered table-condensed">
                <thead>
                <tr>
                    <td>#</td>
                    <td>location</td>
                    <td>revoke</td>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in exception.callers">
                    <td>{{$index}}</td>
                    <td>{{item.file}}:{{item.line}}</td>
                    <td>{{item.revoke}}</td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="tab-pane" id="tab_configure">
            <h4>Configure</h4>
            <table class="table table-striped table-bordered table-condensed">
                <thead>
                <tr>
                    <td style="width: 5%">#</td>
                    <td>name</td>
                    <td>value</td>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(name,value) in configure">
                    <td>{{$index}}</td>
                    <td>{{name}}</td>
                    <td title="{{value|json 4}}">
                        <pre>{{value|json 4}}</pre>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="tab-pane" id="tab_global">
            <h4>{{global_type|uppercase}} Data</h4>
            <table class="table table-striped table-bordered table-condensed">
                <thead>
                <tr>
                    <td style="width: 5%">#</td>
                    <td style="width: 15%">name</td>
                    <td>value</td>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(name,value) in global_computed">
                    <td>{{ $index }}</td>
                    <td>{{ name }}</td>
                    <td>{{ value }}</td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="tab-pane" id="tab_log">
            <h4>Log Messages
                <small><select v-model="log_level" title="">
                        <option v-for="(strLevel,intLevel) in log_levels" value="{{intLevel}}">{{strLevel}}</option>
                    </select></small>
            </h4>
            <table class="table table-striped table-bordered table-condensed">
                <thead>
                <tr>
                    <td>#</td>
                    <td>message</td>
                </tr>
                </thead>
                <tbody>
                <tr v-for="item in log_computed">
                    <td>{{$index}}</td>
                    <td>{{item.message}}</td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="tab-pane" id="tab_sql">

            <h4>SQL Statements({{sql.count}})
                <small><label><input type="checkbox" checked v-model="log_checked_executed">Executed</label></small>
            </h4>
            <div v-show="log_checked_executed">
                <table class="table table-striped table-bordered table-condensed">
                    <thead>
                    <tr>
                        <td style="width: 5%">#</td>
                        <td style="width: 8%">rows</td>
                        <td>executed sql</td>
                        <td>time</td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="item in sql.executed" title="{{item|json 4}}">
                        <td>{{$index}}</td>
                        <td>{{item.row_count}}</td>
                        <td>{{item.emulated}}</td>
                        <td>{{item.time}}</td>

                    </tr>
                    </tbody>
                </table>
            </div>
            <div v-show="!log_checked_executed">
                <table class="table table-striped table-bordered table-condensed">
                    <thead>
                    <tr>
                        <td style="width: 5%">#</td>
                        <td style="width: 8%">count</td>
                        <td>prepared sql</td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(sql,count) in sql.prepared">
                        <td>{{$index}}</td>
                        <td>{{count}}</td>
                        <td>{{sql}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane" id="tab_included_files">
            <h4>IncludedFiles({{included_files_computed.length}}) <label><input type="checkbox" v-model="included_files_application_only">
                    <small>Ignore Framework Files</small>
                    </input></label></h4>
            <table class="table table-striped table-bordered table-condensed">
                <thead>
                <tr>
                    <td style="width: 5%">#</td>
                    <td>file</td>
                </tr>
                </thead>
                <tbody>
                <tr v-for="file in included_files_computed">
                    <td>{{$index}}</td>
                    <td>{{file}}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.11.3.min.js"></script>
<script src="http://ajax.aspnetcdn.com/ajax/bootstrap/3.3.5/bootstrap.min.js"></script>
<script src='http://vuejs.org.cn/js/vue.js'></script>

<?php
$data['included_files'] = get_included_files();
$data['request'] = $_REQUEST;
$data['get'] = $_GET;
$data['post'] = $_POST;
$data['cookie'] = $_COOKIE;
$data['session'] = isset($_SESSION) ? $_SESSION : [];
$data['server'] = $_SERVER;
unset($data['server']['PATH']);
?>
<script>
    data =<?=json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)?>;
    included_files_application = [];

    data['included_files_application'] = included_files_application;
    data['included_files_application_only'] = true;
    data['log_checked_executed'] = true;
    data['global_type'] = 'request';

    $("a[href='#<?=count($data['exception'])===0?'tab_basic':'tab_exception'?>']").tab('show');

    new Vue({
        el: '#app',
        data: data,
        computed: {
            log_computed: function () {
                var computed = [];
                var log = data['log'];
                for (var i = 0; i < log.length; i++) {
                    if (log[i].level <= data['log_level']) {
                        computed.push(log[i]);
                    }
                }

                return computed;
            },
            included_files_computed: function () {
                if (data['included_files_application_only']) {
                    var computed = [];
                    for (var i = 0; i < data['included_files'].length; i++) {
                        var file = data['included_files'][i];
                        if (file.indexOf('/ManaPHP/') < 0 && file.indexOf('\\ManaPHP\\') < 0) {
                            computed.push(file);
                        }
                    }
                    return computed;
                } else {
                    return data['included_files'];
                }
            },
            global_computed: function () {
                return data[data['global_type']];
            }
        }
    })
</script>
</body>
</html>