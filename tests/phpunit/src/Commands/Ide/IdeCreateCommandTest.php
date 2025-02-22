<?php

namespace Acquia\Cli\Tests\Commands\Ide;

use Acquia\Cli\Command\Ide\IdeCreateCommand;
use Acquia\Cli\Tests\CommandTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Command\Command;

/**
 * @property IdeCreateCommand $command
 */
class IdeCreateCommandTest extends CommandTestBase {

  /**
   * Tests the 'ide:create' command.
   */
  public function testCreate(): void {

    $this->mockApplicationsRequest();
    $this->mockApplicationRequest();
    $this->mockAccountRequest();

    // Request to create IDE.
    $response = $this->getMockResponseFromSpec('/applications/{applicationUuid}/ides', 'post', '202');
    $this->clientProphecy->request(
          'post',
          // @todo Consider replacing path parameter with Argument::containingString('/ides') or something.
          '/applications/a47ac10b-58cc-4372-a567-0e02b2c3d470/ides',
          ['json' => ['label' => 'Example IDE']]
      )->willReturn($response->{'IDE created'}->value);

    // Request for IDE data.
    $response = $this->getMockResponseFromSpec('/ides/{ideUuid}', 'get', '200');
    $this->clientProphecy->request('get', '/ides/1792767d-1ee3-4b5f-83a8-334dfdc2b8a3')->willReturn($response)->shouldBeCalled();

    /** @var \Prophecy\Prophecy\ObjectProphecy|\GuzzleHttp\Psr7\Response $guzzleResponse */
    $guzzleResponse = $this->prophet->prophesize(Response::class);
    $guzzleResponse->getStatusCode()->willReturn(200);
    $guzzleClient = $this->prophet->prophesize(Client::class);
    $guzzleClient->request('GET', '/health')->willReturn($guzzleResponse->reveal())->shouldBeCalled();
    $this->command->setClient($guzzleClient->reveal());

    $inputs = [
      // Would you like Acquia CLI to search for a Cloud application that matches your local git config?
      // Would you like to link the project at ... ?
      'n',
      0,
      // Select the application for which you'd like to create a new IDE
      0,
      // Enter a label for your Cloud IDE:
      'Example IDE',
    ];
    $this->executeCommand([], $inputs);

    // Assert.
    $this->prophet->checkPredictions();
    $output = $this->getDisplay();
    $this->assertStringContainsString('Select a Cloud Platform application:', $output);
    $this->assertStringContainsString('  [0] Sample application 1', $output);
    $this->assertStringContainsString('  [1] Sample application 2', $output);
    $this->assertStringContainsString("Enter the label for the IDE (option --label) [Jane Doe's IDE]:", $output);
    $this->assertStringContainsString('Your IDE is ready!', $output);
    $this->assertStringContainsString('Your IDE URL: https://215824ff-272a-4a8c-9027-df32ed1d68a9.ides.acquia.com', $output);
    $this->assertStringContainsString('Your Drupal Site URL: https://ide-215824ff-272a-4a8c-9027-df32ed1d68a9.prod.acquia-sites.com', $output);
  }

  /**
   * @return \Acquia\Cli\Command\Ide\IdeCreateCommand
   */
  protected function createCommand(): Command {
    return $this->injectCommand(IdeCreateCommand::class);
  }

}
