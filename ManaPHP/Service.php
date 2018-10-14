<?php
namespace ManaPHP;

class Service extends Component
{
    public function categorizeLog()
    {
        return basename(str_replace('\\', '.', get_called_class()), 'Service');
    }
}