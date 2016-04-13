<?php

namespace Application {

    use ManaPHP\Auth\AuthorizationInterface;
    use ManaPHP\Component;

    class Authorization extends Component implements AuthorizationInterface
    {
        public function authorize($dispatcher)
        {
            return true;
        }
    }
}

