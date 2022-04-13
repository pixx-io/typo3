<?php
  $uid = uniqid();
?>
<span class="btn btn-default pixxio" data-uid="<?php echo $uid ?>" style="margin-left: 5px">
  <?php echo $this->iconFactory->getIcon('actions-pixxio-extension-modal-view', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render() ?>
  <?php echo $buttonText ?>
</span>
<script src="/typo3conf/ext/pixxio_extension/Resources/Public/Vendor/pixxio.jsdk.min.js" defer async></script>
<style>
  @import "/typo3conf/ext/pixxio_extension/Resources/Public/Vendor/pixxio.jsdk.css";
</style>
<div class="pixxio-jsdk" data-uid="<?php echo $uid ?>" style="z-index: 1000; position: relative;"
  data-dom="<?php echo htmlspecialchars($objectPrefix)  ?>"
  data-key="<?php echo $this->applikationKey ?: '' ?>"
  data-url="<?php echo $extensionConfiguration['url'] ?: '' ?>" 
  data-token="<?php echo $extensionConfiguration['token_refresh'] ?: '' ?>"
></div>
