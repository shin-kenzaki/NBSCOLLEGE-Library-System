<?php
function getStatusDisplay($status) {
    switch ($status) {
        case '1':
        case 1:
        case 'active':
            return ['badge-success', 'Active'];
        case '0':
        case 0:
        case 'inactive':
            return ['badge-warning', 'Inactive'];
        case 'banned':
            return ['badge-danger', 'Banned'];
        case 'disabled':
            return ['badge-secondary', 'Disabled'];
        default:
            return ['badge-danger', 'Invalid Status'];
    }
}
?>
