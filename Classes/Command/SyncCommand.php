<?php

namespace Pixxio\PixxioExtension\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class SyncCommand extends Command
{
  /**
   * Configure the command by defining the name, options and arguments
   */
  protected function configure()
  {
    $this->setHelp('Synchronizes files from Pixxio.' . "\n" . 'Use this command to sync media assets from your pixx.io mediaspace.');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
      $io = new SymfonyStyle($input, $output);

      $io->title($this->getDescription());

      $io->writeln('🚀 Start syncing');
      try {
          $filesController = GeneralUtility::makeInstance(\Pixxio\PixxioExtension\Controller\FilesController::class);
          $result = $filesController->syncAction($io);
          if ($result) {
              $io->success('🪐 synchronization successful');
              return Command::SUCCESS;
          }
          $io->error('💥 synchronization failed');
          return Command::FAILURE;
      } catch (\RuntimeException $error) {
          $io->error('😱 got a runtime exception: ' . $error->getMessage());
          return Command::FAILURE;
      }
  }
}
