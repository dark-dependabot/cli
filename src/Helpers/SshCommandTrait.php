<?php

namespace Acquia\Cli\Helpers;

use Acquia\Cli\Exception\AcquiaCliException;
use AcquiaCloudApi\Connector\Client;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zumba\Amplitude\Amplitude;

trait SshCommandTrait {

  private function deleteSshKeyFromCloud($output, $cloudKey = NULL): int {
    $acquiaCloudClient = $this->cloudApiClientService->getClient();
    if (!$cloudKey) {
      $cloudKey = $this->determineCloudKey($acquiaCloudClient);
    }

    $response = $acquiaCloudClient->makeRequest('delete', '/account/ssh-keys/' . $cloudKey->uuid);
    if ($response->getStatusCode() === 202) {
      $output->writeln("<info>Successfully deleted SSH key <options=bold>$cloudKey->label</> from the Cloud Platform.</info>");
      $localKeys = $this->findLocalSshKeys();
      foreach ($localKeys as $localFile) {
        if (trim($localFile->getContents()) === trim($cloudKey->public_key)) {
          $privateKeyPath = str_replace('.pub', '', $localFile->getRealPath());
          $publicKeyPath = $localFile->getRealPath();
          $answer = $this->io->confirm("Do you also want to delete the corresponding local key files {$localFile->getRealPath()} and $privateKeyPath ?", FALSE);
          if ($answer) {
            $this->localMachineHelper->getFilesystem()->remove([
              $localFile->getRealPath(),
              $privateKeyPath,
            ]);
            $this->io->success("Deleted $publicKeyPath and $privateKeyPath");
            return 0;
          }
        }
      }
      return 0;
    }

    throw new AcquiaCliException($response->getBody()->getContents());
  }

  private function determineCloudKey(Client $acquiaCloudClient): object|array|null {
    $cloudKeys = $acquiaCloudClient->request('get', '/account/ssh-keys');
    return $this->promptChooseFromObjectsOrArrays(
      $cloudKeys,
      'uuid',
      'label',
      'Choose an SSH key to delete from the Cloud Platform'
    );
  }

  /**
   * @return \Symfony\Component\Finder\SplFileInfo[]
   */
  protected function findLocalSshKeys(): array {
    $finder = $this->localMachineHelper->getFinder();
    $finder->files()->in($this->sshDir)->name('*.pub')->ignoreUnreadableDirs();
    return iterator_to_array($finder);
  }

  protected function promptWaitForSsh(SymfonyStyle $io): bool {
    $io->note("It may take an hour or more before the SSH key is installed on all of your application's servers. Create a Support ticket for further assistance.");
    $wait = $io->confirm("Would you like to wait until your key is installed on all of your application's servers?");
    Amplitude::getInstance()->queueEvent('User waited for SSH key upload', ['wait' => $wait]);
    return $wait;
  }

}
