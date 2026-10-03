<?php
/** @var array $flashes */
foreach ($flashes as $f) {
    $type    = e($f['type'] ?? 'info');
    $message = e($f['message'] ?? '');
    $class   = match ($f['type'] ?? 'info') {
        'error'   => 'alert-danger',
        'success' => 'alert-success',
        'warning' => 'alert-warning',
        default   => 'alert-info',
    };
    echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">'
       . $message
       . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
       . '</div>';
}