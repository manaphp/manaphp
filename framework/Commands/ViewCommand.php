<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Persistence\EntityMetadataInterface;
use ReflectionProperty;
use function in_array;

class ViewCommand extends Command
{
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    /**
     * @param string $entityClass
     *
     * @return string
     * @noinspection PhpUnusedParameterInspection
     */
    public function renderRequestForm(string $entityClass): string
    {
        $content = <<<HTML
<request-form>
</request-form>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param string $entityClass
     *
     * @return string
     */
    public function renderDetailForm(string $entityClass): string
    {
        $content = PHP_EOL . <<<HTML
<detail-form>
HTML;
        foreach ($this->entityMetadata->getFields($entityClass) as $field) {
            if ($this->isTimestampField($entityClass, $field)) {
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
     * @param string $entityClass
     *
     * @return string
     */
    public function renderCreateForm(string $entityClass): string
    {
        if (!$fields = $this->entityMetadata->getFillable($entityClass)) {
            return '';
        }

        $content = PHP_EOL . <<<HTML
<create-form>
HTML;
        foreach ($fields as $field) {
            $content .= PHP_EOL . <<<HTML
    <create-text prop="$field"></create-text>
HTML;
        }

        $content .= PHP_EOL . <<<HTML
</create-form>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param string $entityClass
     *
     * @return string
     */
    public function renderEditForm(string $entityClass): string
    {
        if (!$fields = $this->entityMetadata->getFillable($entityClass)) {
            return '';
        }

        $content = PHP_EOL . <<<HTML
<edit-form>
HTML;
        $primaryKey = $this->entityMetadata->getPrimaryKey($entityClass);
        $content .= PHP_EOL . <<<HTML
    <edit-text prop="$primaryKey" disabled></edit-text>
HTML;

        foreach ($fields as $field) {
            $content .= PHP_EOL . <<<HTML
    <edit-text prop="$field"></edit-text>
HTML;
        }

        $content .= PHP_EOL . <<<HTML
</edit-form>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param string $entityClass
     * @param string $field
     *
     * @return bool
     */
    public function isTimestampField(string $entityClass, string $field): bool
    {
        $rProperty = new ReflectionProperty($entityClass, $field);
        if ($rProperty->getType()?->getName() !== 'int') {
            return false;
        }

        return in_array($field, ['updated_time', 'created_time', 'deleted_time'], true);
    }

    /**
     * @param string $entityClass
     *
     * @return string
     */
    public function renderResultTable(string $entityClass): string
    {
        $content = PHP_EOL . <<<HTML
<result-table>
    <result-index></result-index>
HTML;
        foreach ($this->entityMetadata->getFields($entityClass) as $field) {
            if ($this->isTimestampField($entityClass, $field)) {
                $content .= PHP_EOL . <<<HTML
    <result-timestamp prop="$field"></result-timestamp>
HTML;
            } elseif (str_ends_with($field, '_id')) {
                $content .= PHP_EOL . <<<HTML
    <result-id prop="$field"></result-id>
HTML;
            } elseif ($field === 'email') {
                $content .= PHP_EOL . <<<HTML
    <result-email></result-email>
HTML;
            } elseif ($field === 'id' || str_ends_with($field, '_ip')) {
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
    <result-op></result-op>
HTML;
        $content .= PHP_EOL . <<<HTML
</result-table>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param string $entityClass
     *
     * @return string
     * @noinspection PhpUnusedParameterInspection
     */
    public function renderCss(string $entityClass): string
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
     * @param string $entityClass
     *
     * @return string
     */
    public function renderScript(string $entityClass): string
    {
        $fields = $this->entityMetadata->getFillable($entityClass);

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
            $content .= PHP_EOL . <<<HTML
                },
                edit: {
HTML;
            $content .= PHP_EOL . '                    ' . $this->entityMetadata->getPrimaryKey($entityClass) . ': 0,';
            $content .= PHP_EOL . '                },';
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
     * @param string $entityClass
     *
     * @return string
     */
    public function render(string $entityClass): string
    {
        $content = $this->renderRequestForm($entityClass);
        $content .= $this->renderDetailForm($entityClass);
        $content .= $this->renderCreateForm($entityClass);
        $content .= $this->renderEditForm($entityClass);
        $content .= $this->renderResultTable($entityClass);
        $content .= $this->renderCss($entityClass);
        $content .= $this->renderScript($entityClass);

        return $content;
    }

    /**
     * auto generate views file
     *
     * @return void
     */
    public function defaultAction(): void
    {
        foreach (LocalFS::glob('@app/Entities/*.php') as $entity_file) {
            if (basename($entity_file) === 'Entity.php') {
                continue;
            }
            $plain = basename($entity_file, '.php');
            $view_file = "@runtime/view/Views/$plain.sword";
            $entityClass = "App\Entities\\$plain";
            LocalFS::filePut($view_file, $this->render($entityClass));
            $this->console->writeLn("view of `$entityClass` saved to `$view_file`");
        }

        foreach (LocalFS::glob('@app/Areas/*/Entities/*.php') as $entity_file) {
            if (basename($entity_file) === 'Entity.php') {
                continue;
            }
            preg_match('#Areas/(\w+)/Entities/(\w+).php$#', $entity_file, $match);
            list(, $area, $plain) = $match;

            $view_file = "@runtime/view/Areas/$area/Views/$plain.sword";
            $entityClass = "App\\Areas\\$area\\Entities\\$plain";
            LocalFS::filePut($view_file, $this->render($entityClass));
            $this->console->writeLn("view of `$entityClass` saved to `$view_file`");
        }
    }
}
