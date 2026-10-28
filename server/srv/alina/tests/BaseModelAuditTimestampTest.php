<?php

declare(strict_types=1);

require_once __DIR__ . '/../mvc/Model/_BaseAlinaModel.php';

use alina\mvc\Model\_BaseAlinaModel;

if (! defined('ALINA_TIME')) {
    define('ALINA_TIME', time());
}

final class AuditTimestampModel extends _BaseAlinaModel
{
    public function currentAuditTimestamp(): int
    {
        return $this->auditTimestamp();
    }
}

$model = (new ReflectionClass(AuditTimestampModel::class))->newInstanceWithoutConstructor();
sleep(2);
$timestamp = $model->currentAuditTimestamp();

if ($timestamp <= ALINA_TIME) {
    fwrite(STDERR, "FAIL: audit timestamps must be evaluated when the model is saved\n");
    exit(1);
}

if (abs(time() - $timestamp) > 1) {
    fwrite(STDERR, "FAIL: audit timestamp must represent the current wall-clock time\n");
    exit(1);
}

echo "PASS: base model uses current time for audit fields\n";
