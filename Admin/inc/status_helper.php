<?php
function getStatusDisplay($status) {
    switch ($status) {
        case 0:
            return ['badge-secondary', 'Inactive'];
        case 1:
            return ['badge-success', 'Active'];
        case 2:
            return ['badge-danger', 'Banned'];
        case 3:
            return ['badge-warning', 'Disabled'];
        default:
            return ['badge-secondary', 'Unknown'];
    }
}
?>