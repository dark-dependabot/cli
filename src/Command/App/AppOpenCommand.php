<?php

namespace Acquia\Cli\Command\App;

use Acquia\Cli\Command\CommandBase;
use Acquia\Cli\Helpers\LocalMachineHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppOpenCommand extends CommandBase {

  protected static $defaultName = 'app:open';

  protected function configure(): void {
    $this->setDescription('Opens your browser to view a given Cloud application')
      ->acceptApplicationUuid()
      ->setHidden(!LocalMachineHelper::isBrowserAvailable())
      ->setAliases(['open', 'o']);
  }

  /**
   * @return int 0 if everything went fine, or an exit code
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $applicationUuid = $this->determineCloudApplication();
    $this->localMachineHelper->startBrowser('https://cloud.acquia.com/a/applications/' . $applicationUuid);

    return 0;
  }

}
