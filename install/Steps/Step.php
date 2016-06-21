<?php

    interface Step
    {
        /**
         * @param Configuration $config
         * @return mixed
         */
        public function handle(Configuration $config);
    }
