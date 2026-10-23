<?php

declare(strict_types=1);

namespace alina {
    function AlinaResponseSuccess(int $success): void
    {
    }

    function Alina(): object
    {
        return new class {
            public function mvcGo(string $controller, string $action, object $context): string
            {
                return 'rendered exception page';
            }
        };
    }
}

namespace alina\mvc\Model {
    final class _baseAlinaEloquentTransaction
    {
        public static function rollback(): void
        {
        }
    }
}

namespace alina\Utils {
    final class Request
    {
        public static function obj(): self
        {
            return new self();
        }

        public function isPostPutDelete(): bool
        {
            return false;
        }
    }
}

namespace {
    require_once __DIR__ . '/../AppExceptionCatcher.php';

    final class NormalFlowExceptionCatcher extends alina\AppExceptionCatcher
    {
        protected function processError()
        {
        }
    }

    $actual = NormalFlowExceptionCatcher::obj()->exception(new RuntimeException('ordinary failure'));

    if ($actual !== 'rendered exception page') {
        fwrite(STDERR, "FAIL: ordinary exception handling must preserve the exception page return value\n");
        exit(1);
    }

    fwrite(STDOUT, "PASS: ordinary exception handling behavior\n");
}
