<?php

namespace Acquia\Cli\Tests\Commands\App;

use Acquia\Cli\Command\App\UnlinkCommand;
use Acquia\Cli\Exception\AcquiaCliException;
use Acquia\Cli\Tests\CommandTestBase;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Acquia\Cli\Command\App\UnlinkCommand $command
 */
class UnlinkCommandTest extends CommandTestBase {

  protected function createCommand(): Command {
    return $this->injectCommand(UnlinkCommand::class);
  }

  /**
   * Tests the 'unlink' command.
   */
  public function testUnlinkCommand(): void {
    $applicationsResponse = $this->getMockResponseFromSpec('/applications',
      'get', '200');
    $cloudApplication = $applicationsResponse->{'_embedded'}->items[0];
    $cloudApplicationUuid = $cloudApplication->uuid;
    $this->createMockAcliConfigFile($cloudApplicationUuid);
    $this->mockApplicationRequest();

    // Assert we set it correctly.
    $this->assertEquals($applicationsResponse->{'_embedded'}->items[0]->uuid, $this->datastoreAcli->get('cloud_app_uuid'));

    $this->executeCommand([], []);
    $output = $this->getDisplay();

    // Assert it's been unset.
    $this->assertNull($this->datastoreAcli->get('cloud_app_uuid'));
    $this->assertStringContainsString("Unlinked $this->projectDir from Cloud application " . $cloudApplication->name, $output);
  }

  public function testUnlinkCommandInvalidDir(): void {
    $this->expectException(AcquiaCliException::class);
    $this->expectExceptionMessage('There is no Cloud Platform application linked to ' . $this->projectDir);
    $this->executeCommand([], []);
  }

}
