<?php
namespace Pixxio\PixxioExtension\Domain\Model;
 
class FileReference extends \TYPO3\CMS\Core\Resource\FileReference {
 
  /**
   * pixxioMediaspace
   *
   * @var string
   */
  protected $pixxioMediaspace;

  /**
   * pixxioFileId
   *
   * @var integer
   */
  protected $pixxioFileId;

  /**
   * pixxioDownloadformat
   *
   * @var string
   */
  protected $pixxioDownloadformat;
 
  /**
   * Returns the pixxioMediaspace
   *
   * @return string $pixxioMediaspace
   */
  public function getPixxioMediaspace() {
      return $this->pixxioMediaspace;
  }
 
  /**
   * Sets the pixxioMediaspace
   *
   * @param string $pixxioMediaspace
   * @return void
   */
  public function setPixxioMediaspace($pixxioMediaspace) {
      $this->pixxioMediaspace = $pixxioMediaspace;
  }

  /**
   * Returns the pixxioFileId
   *
   * @return integer $pixxioFileId
   */
  public function getPixxioFileId() {
    return $this->pixxioFileId;
  }

  /**
   * Sets the pixxioFileId
   *
   * @param integer $pixxioFileId
   * @return void
   */
  public function setPixxioFileId($pixxioFileId) {
      $this->pixxioFileId = $pixxioFileId;
  }

  /**
   * Returns the pixxioDownloadformat
   *
   * @return string $pixxioDownloadformat
   */
  public function getPixxioDownloadformat() {
    return $this->pixxioDownloadformat;
  }

  /**
   * Sets the pixxioFileId
   *
   * @param integer $pixxioFileId
   * @return void
   */
  public function setPixxioDownloadformat($pixxioDownloadformat) {
      $this->pixxioDownloadformat = $pixxioDownloadformat;
  }
}