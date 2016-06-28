<?php
namespace SymphonyCms\Installer\Steps;

use Psr\Log\LoggerInterface;

abstract class DefaultStep implements Step
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var boolean
     */
    protected $override = false;

    /**
     * CreateManifest constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setOverride($override = false)
    {
        $this->override = $override;
    }
}
