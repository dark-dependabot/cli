<?php

namespace Acquia\Cli\Command\Ide;

use Acquia\Cli\Exception\AcquiaCliException;
use Acquia\DrupalEnvironmentDetector\AcquiaDrupalEnvironmentDetector;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IdePhpVersionCommand extends IdeCommandBase {

  protected static $defaultName = 'ide:php-version';

  private string $idePhpFilePathPrefix;

  /*
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *
   * @return bool
   */
  protected function commandRequiresAuthentication(): bool {
    return FALSE;
  }

  protected function configure(): void {
    $this->setDescription('Change the PHP version in the current IDE')
      ->addArgument('version', InputArgument::REQUIRED, 'The PHP version')
      ->setHidden(!AcquiaDrupalEnvironmentDetector::isAhIdeEnv());
  }

  /**
   * @return int 0 if everything went fine, or an exit code
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->requireCloudIdeEnvironment();
    $version = $input->getArgument('version');
    $this->validatePhpVersion($version);
    $this->localMachineHelper->getFilesystem()->dumpFile($this->getIdePhpVersionFilePath(), $version);
    $this->restartService('php-fpm');

    return 0;
  }

  private function getIdePhpFilePathPrefix(): string {
    if (!isset($this->idePhpFilePathPrefix)) {
      $this->idePhpFilePathPrefix = '/usr/local/php';
    }
    return $this->idePhpFilePathPrefix;
  }

  public function setIdePhpFilePathPrefix(string $path): void {
    $this->idePhpFilePathPrefix = $path;
  }

  protected function validatePhpVersion(string $version): string {
    parent::validatePhpVersion($version);
    $phpFilepath = $this->getIdePhpFilePathPrefix() . $version;
    if (!$this->localMachineHelper->getFilesystem()->exists($phpFilepath)) {
      throw new AcquiaCliException('The specified PHP version does not exist on this machine.');
    }

    return $version;
  }

}
