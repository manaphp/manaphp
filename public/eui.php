<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <!-- 引入样式 -->
  <link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-chalk/index.css">
</head>
<body>
<div id="app">
  <el-button @click="visible = true">按钮</el-button>
  <el-dialog :visible.sync="visible" title="Hello world">
    <p>欢迎使用 Element</p>
  </el-dialog>
</div>
</body>
<!-- 先引入 Vue -->
<script src="https://cdn.bootcss.com/vue/2.5.9/vue.min.js"></script>
<!-- 引入组件库 -->
<script src="https://cdn.bootcss.com/element-ui/2.0.5/index.js"></script>
<script>
    new Vue({
        el: '#app',
        data: function () {
            return {visible: false}
        }
    })
</script>
</html>
