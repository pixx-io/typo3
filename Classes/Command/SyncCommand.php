<?php

namespace Pixxio\PixxioExtension\Command;

use Pixxio\PixxioExtension\Controller\FilesController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
    $this->setHelp('Synchronizes files from Pixxio.' . "\n" . 'Use this command to sync media assets from your pixx.io mediaspace.' . "\n" . 'Options:' . "\n" . '  --fid=<id>  Sync a specific file by TYPO3 file UID' . "\n" . '  --pid=<id>  Sync a specific file by pixx.io ID');
    
    $this->addOption(
      'fid',
      null,
      InputOption::VALUE_REQUIRED,
      'TYPO3 file UID to sync'
    );
    
    $this->addOption(
      'pid',
      null,
      InputOption::VALUE_REQUIRED,
      'pixx.io file ID to sync'
    );
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
      $io = new SymfonyStyle($input, $output);

      $io->title($this->getDescription());

      $fid = $input->getOption('fid');
      $pid = $input->getOption('pid');
      
      // Check if both options are provided
      if ($fid && $pid) {
          $io->error('Please provide either --fid or --pid, not both');
          return Command::INVALID;
      }

      $io->writeln('🚀 Start syncing');
      try {
          $filesController = GeneralUtility::makeInstance(FilesController::class);
          
          // Sync single file if fid or pid is provided
          if ($fid || $pid) {
              $result = $filesController->syncSingleFileAction($io, $fid, $pid);
          } else {
              // Sync all files
              $result = $filesController->syncAction($io);
          }
          
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
