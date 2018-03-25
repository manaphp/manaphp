<?php
namespace ManaPHP\Model;

use ManaPHP\Component;

class Relation extends Component implements RelationInterface
{
    const TYPE_BELONGS_TO = 1;
    const TYPE_HAS_MANY = 2;
    const TYPE_HAS_ONE = 3;
    const TYPE_HAS_MANY_TO_MANY = 4;
}