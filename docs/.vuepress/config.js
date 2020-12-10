module.exports={
    title: "ManaPHP 文档中心",
    head: [
        ['script', {src: '//js.users.51.la/20135751.js'}]
    ],
    dest: "dist",
    themeConfig: {
        docsRepo: "manaphp/docs",
        editLinks: true,
        docsDir: 'docs',
        lastUpdated: 'Last Updated',
        nav: [
            {text: "Home", link: "/"},
            {text: "GitHub", link: "https://github.com/manaphp"}
        ],
        sidebar: [
            '/introduction',
            {
                title: "基础",
                collapsable: true,
                children:[
                    '/basic/get_manaphp',
                    '/basic/environment',
                    '/basic/directory_structure',
                    '/basic/entrance_file',
                    '/basic/controller_general',
                    '/basic/develop_standard',
                    '/basic/develop_environment'
                ]
            },
            {
                title: "配置",
                collapsable: true,
                children:[
                    '/configure/dotenv',
                    '/configure/get',
                ]
            },
            {
                title: "架构",
                collapsable: true,
                children:[
                    '/db',
                    '/query',
                    '/model',
                    'redis'
                ]
            },
            {
                title: "路由",
                collapsable: true,
                children:[
                    '/db',
                    '/query',
                    '/model',
                    'redis'
                ]
            },
            {
                title: "控制器",
                collapsable: true,
                children:[
                    '/db',
                    '/query',
                    '/model',
                    'redis'
                ]
            },
            {
                title: "模型",
                collapsable: true,
                children:[
                    '/db',
                    '/query',
                    '/model',
                    'redis'
                ]
            },
            {
                title: "视图",
                collapsable: true,
                children:[
                    '/db',
                    '/query',
                    '/model',
                    'redis'
                ]
            },
            {
                title: "模板",
                collapsable: true,
                children:[
                    '/db',
                    '/query',
                    '/model',
                    'redis'
                ]
            },
            {
                title: "调试",
                collapsable: true,
                children:[
                    '/debug/debuggerPlugin'
                ]
            },
            {
                title: "缓存",
                collapsable: true,
                children:[
                    '/cache',
                ]
            },
            {
                title: "安全",
                collapsable: true,
                children:[
                    '/db',
                    '/query',
                    '/model',
                    'redis'
                ]
            },
            {
                title: "扩展",
                collapsable: true,
                children:[
                    '/db',
                    '/query',
                    '/model',
                    'redis'
                ]
            },
            {
                title: "部署",
                collapsable: true,
                children:[
                    '/db',
                    '/query',
                    '/model',
                    'redis'
                ]
            },
            {
                title: "专题",
                collapsable: true,
                children:[
                    '/session',
                    '/cookies',
                    '/translator',
                ]
            },
            {
                title: "附录",
                collapsable: true,
                children:[
                    '/join',
                ]
            },
        ]
    }
}