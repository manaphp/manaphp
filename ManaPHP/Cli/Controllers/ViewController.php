<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;

class ViewController extends Controller
{
    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return string
     */
    public function renderRequestForm($model)
    {
        $content = <<<HTML
<request-form>
</request-form>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return string
     */
    public function renderDetailForm($model)
    {
        $content = '';

        $content .= PHP_EOL . <<<HTML
<detail-form>
HTML;
        foreach ($model->getFields() as $field) {
            if ($this->isTimestampField($model, $field)) {
                $content .= PHP_EOL . <<<HTML
    <detail-timestamp prop="$field"></detail-timestamp>
HTML;
            } else {
                $content .= PHP_EOL . <<<HTML
    <detail-text prop="$field"></detail-text>
HTML;
            }

        }

        $content .= PHP_EOL . <<<HTML
</detail-form>
HTML;

        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return string
     */
    public function renderCreateForm($model)
    {
        if (!$fields = $model->getSafeFields()) {
            return '';
        }

        $content = PHP_EOL . <<<HTML
<create-form>
HTML;
        foreach ($fields as $field) {
            $content .= PHP_EOL . <<<HTML
    <create-input prop="$field"></create-input>
HTML;
        }

        $content .= PHP_EOL . <<<HTML
</create-form>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\ModelInterface $model
     *
     * @return string
     */
    public function renderEditForm($model)
    {
        if (!$fields = $model->getSafeFields()) {
            return '';
        }

        $content = PHP_EOL . <<<HTML
<edit-form>
HTML;
        $primaryKey = $model->getPrimaryKey();
        $content .= PHP_EOL . <<<HTML
    <edit-input prop="$primaryKey" disabled></edit-input>
HTML;

        foreach ($fields as $field) {
            $content .= PHP_EOL . <<<HTML
    <edit-input prop="$field"></edit-input>
HTML;
        }

        $content .= PHP_EOL . <<<HTML
</edit-form>
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
    public function renderResultTable($model)
    {
        $content = PHP_EOL . <<<HTML
<result-table>
    <result-index></result-index>
HTML;
        foreach ($model->getFields() as $field) {
            if ($this->isTimestampField($model, $field)) {
                $content .= PHP_EOL . <<<HTML
    <result-timestamp prop="$field"></result-timestamp>
HTML;
            } elseif (strpos($field, '_id')) {
                $content .= PHP_EOL . <<<HTML
    <result-id prop="$field"></result-id>
HTML;
            } elseif ($field === 'email') {
                $content .= PHP_EOL . <<<HTML
    <result-email></result-email>
HTML;
            } elseif ($field === 'id' || Str::endsWith($field, '_ip')) {
                $content .= PHP_EOL . <<<HTML
    <result-ip prop="$field"></result-ip>
HTML;
            } elseif (in_array($field, ['admin_name', 'user_name', 'updator_name', 'creator_name'], true)) {
                $content .= PHP_EOL . <<<HTML
    <result-account prop="$field"></result-account>
HTML;
            } elseif ($field === 'enabled') {
                $content .= PHP_EOL . <<<HTML
    <result-enabled></result-enabled>
HTML;
            } else {
                $content .= PHP_EOL . <<<HTML
    <result-column prop="$field" width="100"></result-column>
HTML;
            }
        }

        $content .= PHP_EOL . <<<HTML
    <el-table-column fixed="right" label="操作" width="150">
        <template v-slot="{row}">
            <show-edit :row="row"></show-edit>
            <show-delete :row="row"></show-delete>          
        </template>
    </el-table-column>
HTML;
        $content .= PHP_EOL . <<<HTML
</result-table>
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
                topic: '',
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
            $content .= PHP_EOL . '                },';
        }

        $labels = $model->labels();
        if ($labels) {
            $content .= PHP_EOL . <<<HTML
                label: {
HTML;
            foreach ($labels as $k => $v) {
                $content .= PHP_EOL . <<<HTML
                    $k: '$v',
HTML;
            }
            $content .= PHP_EOL . <<<HTML
                }
HTML;
        } else {
            $content .= PHP_EOL . '                label: {}';
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

        $content .= $this->renderRequestForm($model);
        $content .= $this->renderDetailForm($model);
        $content .= $this->renderCreateForm($model);
        $content .= $this->renderEditForm($model);
        $content .= $this->renderResultTable($model);
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
