<?php
add_hook('ClientAreaPrimarySidebar', 1, function($primarySidebar) {
    if (!isset($_SESSION['uid'])) {
        return;
    }

    if ($primarySidebar->getChild('Service Details Actions')) {
        $reverseDNSMenuItem = $primarySidebar->getChild('Service Details Actions')->addChild('reverse_dns', [
            'label' => '<i class="fa fa-server"></i>Reverse DNS',
            'uri' => 'index.php?m=reversedns&serviceid=' . (int) $_GET['id'],
            'order' => 15,
        ]);
    }
});
?>
