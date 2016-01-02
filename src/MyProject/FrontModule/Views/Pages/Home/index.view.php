<!-- layout: default -->

Current Module: <?php echo $moduleName; ?><br />
Testing: <?php echo $welcomeText; ?><br />

<?php
    \Scabbia\Helpers\String::vardump($this->controller->routeInfo);
    \Scabbia\Helpers\String::vardump(\Scabbia\Framework\ApplicationBase::$current->config);
?>

.
