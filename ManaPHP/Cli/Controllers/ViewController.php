<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Helper\LocalFS;

class ViewController extends Controller
{
    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return string
     */
    public function renderSearchBox($model)
    {
        $content = <<<HTML
<div class="search-box">
    <el-button @click="createVisible=true" type="primary" icon="el-icon-plus" size="small">创建</el-button>
</div>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return string
     */
    public function renderDetailDialog($model)
    {
        $content = '';

        $content .= PHP_EOL . <<<HTML
<el-dialog title="详情" :visible.sync="detailVisible">
    <el-form :model="detail" label-width="150px" size="mini">
HTML;

        $labels = $model->labels();
        foreach ($model->getFields() as $field) {
            $label = $labels[$field] ?? $field;
            if ($this->isTimestampField($model, $field)) {
                $content .= PHP_EOL . <<<HTML
        <el-form-item label="$label:">@{{ detail.$field|date }}</el-form-item>
HTML;
            } else {
                $content .= PHP_EOL . <<<HTML
        <el-form-item label="$label:">@{{ detail.$field }}</el-form-item>
HTML;
            }

        }

        $content .= PHP_EOL . <<<HTML
    </el-form>
</el-dialog>
HTML;

        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return string
     */
    public function renderCreateDialog($model)
    {
        if (!$fields = $model->getSafeFields()) {
            return '';
        }

        $content = PHP_EOL . <<<HTML
<el-dialog title="新增" :visible.sync="createVisible">
    <el-form :model.trim="create" ref="create" size="small">
HTML;
        $labels = $model->labels();
        foreach ($fields as $field) {
            $label = $labels[$field] ?? $field;

            $content .= PHP_EOL . <<<HTML
        <el-form-item label="$label:" prop="$field">
            <el-input v-model.trim="create.$field" auto-complete="off"></el-input>
        </el-form-item>
HTML;
        }

        $content .= PHP_EOL . <<<HTML
    </el-form>
    <span slot="footer">
        <el-button type="primary" @click="do_create" size="small">创建</el-button>
        <el-button @click="createVisible = false; \$refs.create.resetFields()" size="small">取消</el-button>
    </span>
</el-dialog>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return string
     */
    public function renderEditDialog($model)
    {
        if (!$fields = $model->getSafeFields()) {
            return '';
        }

        $content = PHP_EOL . <<<HTML
<el-dialog title="编辑" :visible.sync="editVisible">
    <el-form :model="edit" ref="edit" size="small">
HTML;

        $labels = $model->labels();

        $primaryKey = $model->getPrimaryKey();
        $label = $labels[$primaryKey] ?? $primaryKey;
        $content .= PHP_EOL . <<<HTML
        <el-form-item label="$label">
            <el-input v-model="edit.$primaryKey" disabled></el-input>
        </el-form-item>
HTML;

        foreach ($fields as $field) {
            $label = $labels[$field] ?? $field;

            $content .= PHP_EOL . <<<HTML
        <el-form-item label="$label:">
            <el-input v-model.trim="edit.$field" auto-complete="off"></el-input>
        </el-form-item>
HTML;
        }

        $content .= PHP_EOL . <<<HTML
    </el-form>
    <div slot="footer">
        <el-button type="primary" @click="do_edit" size="small">保存</el-button>
        <el-button @click="editVisible=false" size="small">取消</el-button>
    </div>
</el-dialog>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\ModelInterface $model
     * @param string                  $field
     *
     * @return bool
     */
    public function isTimestampField($model, $field)
    {
        if (!in_array($field, $model->getIntFields(), true)) {
            return false;
        }

        return in_array($field, ['updated_time', 'created_time', 'deleted_time'], true);
    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return string
     */
    public function renderResultBox($model)
    {
        $content = PHP_EOL . <<<HTML
<div class="result-box">
    <pager></pager>
    <el-table :data="response.items" border size="small">
        <el-table-column type="index" label="#" width="50"></el-table-column>
HTML;
        $labels = $model->labels();
        foreach ($model->getFields() as $field) {
            $label = $labels[$field] ?? $field;

            if ($this->isTimestampField($model, $field)) {
                $content .= PHP_EOL . <<<HTML
        <el-table-column prop="$field" label="$label" :formatter="fDate" width="150"></el-table-column>
HTML;
            } else {
                $content .= PHP_EOL . <<<HTML
        <el-table-column prop="$field" label="$label" width="100"></el-table-column>
HTML;
            }
        }

        $content .= PHP_EOL . <<<HTML
        <el-table-column fixed="right" label="操作" width="150">
            <template v-slot="{row}">
                <el-button @click="show_edit(row)" size="mini" type="primary">编辑</el-button>
                <el-button @click="do_delete(row)" size="mini" type="danger">删除</el-button>
            </template>
        </el-table-column>
HTML;
        $content .= PHP_EOL . <<<HTML
    </el-table>
    <pager></pager>
</div>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return string
     */
    public function renderCss($model)
    {
        $content = PHP_EOL . <<<HTML
@section('css')
    <style>
    
    </style>
@append
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return string
     */
    public function renderScript($model)
    {
        $fields = $model->getSafeFields();

        $content = PHP_EOL . <<<HTML
@section('script')
    <script>
        vm = new App({
            data: {
                request: {
                    page: 1,
                    size: 10
                },
                response: {},
HTML;

        if ($fields) {
            $content .= PHP_EOL . <<<HTML
                create: {
HTML;
            $rules = $model->rules();
            $iniFields = $model->getIntFields();
            foreach ($fields as $field) {
                $rule = $rules[$field] ?? [];
                if (is_array($rule) && isset($rule['default'])) {
                    $value = json_stringify($rule['default']);
                } elseif (in_array($field, $iniFields, true)) {
                    $value = 0;
                } else {
                    $value = "''";
                }
                $content .= PHP_EOL . "                    $field: $value, ";
            }

            $content .= PHP_EOL . <<<HTML
                },
                edit: {
HTML;
            $content .= PHP_EOL . '                    ' . $model->getPrimaryKey() . ': 0,';

            foreach ($fields as $field) {
                $rule = $rules[$field] ?? [];
                if (is_array($rule) && isset($rule['default'])) {
                    $value = json_stringify($rule['default']);
                } elseif (in_array($field, $iniFields, true)) {
                    $value = 0;
                } else {
                    $value = "''";
                }
                $content .= PHP_EOL . "                    $field: $value,";
            }
            $content .= PHP_EOL . '                }';
        }

        $content .= PHP_EOL . <<<HTML
            }
        });
    </script>
@append
HTML;
        return $content;
    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return string
     */
    public function render($model)
    {
        $content = '';

        $content .= $this->renderSearchBox($model);
        $content .= $this->renderDetailDialog($model);
        $content .= $this->renderCreateDialog($model);
        $content .= $this->renderEditDialog($model);
        $content .= $this->renderResultBox($model);
        $content .= $this->renderCss($model);
        $content .= $this->renderScript($model);

        return $content;
    }

    /**
     * auto generate views file
     */
    public function defaultCommand()
    {
        foreach (LocalFS::glob('@app/Models/*.php') as $model_file) {
            $plain = basename($model_file, '.php');
            $view_file = path("@tmp/view/Views/{$plain}.sword");
            $model = "App\Models\\$plain";
            $instance = new $model();
            LocalFS::filePut($view_file, $this->render($instance));
        }

        foreach (LocalFS::glob('@app/Areas/*/Models/*.php') as $model_file) {
            preg_match('#Areas/(\w+)/Models/(\w+).php$#', $model_file, $match);
            list(, $area, $plain) = $match;

            $view_file = path("@tmp/view/Areas/$area/Views/{$plain}.sword");
            $model = "App\\Areas\\$area\\Models\\$plain";
            $instance = new $model();
            LocalFS::filePut($view_file, $this->render($instance));
        }
    }
}
