<?php
    namespace SymphonyCms\Installer\Steps;

    use Configuration;

    interface Step
    {
        /**
         * @param Configuration $config
         * @param array $data
         * @return mixed
         */
        public function handle(Configuration $config, array $data);
    }
