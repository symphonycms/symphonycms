<?php
namespace SymphonyCms\Installer\Steps;

use Configuration;

interface Step
{
    /**
     * Allow the step to take actions to override an existing installation.
     *
     * @param boolean $override
     * @return $this
     */
    public function setOverride($override = false);

    /**
     * Given `$config` and additional data captured from the user, run this step.
     *
     * @param Configuration $config
     * @param array $data
     * @return mixed
     */
    public function handle(Configuration $config, array $data);
}
