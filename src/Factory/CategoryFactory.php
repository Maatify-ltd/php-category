<?php

declare(strict_types=1);

namespace Maatify\Category\Factory;

use Closure;
use Maatify\Category\Api\CategoryApi;
use Maatify\Category\Common\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Content\Api\ContentApi;
use Maatify\Category\Content\Infrastructure\PdoCategoryContentCommandRepository;
use Maatify\Category\Content\Query\Infrastructure\PdoCategoryContentManagementReadQuery;
use Maatify\Category\Content\Query\Infrastructure\PdoCategoryContentQueryReader;
use Maatify\Category\Content\Query\Infrastructure\PdoCategoryContentReadQuery;
use Maatify\Category\Content\Service\ContentService;
use Maatify\Category\ContentField\Api\ContentFieldApi;
use Maatify\Category\ContentField\Infrastructure\PdoCategoryContentFieldCommandRepository;
use Maatify\Category\ContentField\Query\Infrastructure\PdoCategoryContentFieldManagementReadQuery;
use Maatify\Category\ContentField\Query\Infrastructure\PdoCategoryContentFieldQueryReader;
use Maatify\Category\ContentField\Query\Infrastructure\PdoCategoryContentFieldReadQuery;
use Maatify\Category\ContentField\Service\ContentFieldService;
use Maatify\Category\Facade\CategoryFacade;
use Maatify\Category\Facade\Contract\CategoryFacadeInterface;
use Maatify\Category\ImageAssignment\Api\ImageAssignmentApi;
use Maatify\Category\ImageAssignment\Infrastructure\PdoCategoryImageAssignmentCommandRepository;
use Maatify\Category\ImageAssignment\Query\Infrastructure\PdoCategoryImageAssignmentManagementReadQuery;
use Maatify\Category\ImageAssignment\Query\Infrastructure\PdoCategoryImageAssignmentQueryReader;
use Maatify\Category\ImageAssignment\Query\Infrastructure\PdoCategoryImageAssignmentReadQuery;
use Maatify\Category\ImageAssignment\Service\ImageAssignmentService;
use Maatify\Category\ImageRole\Api\ImageRoleApi;
use Maatify\Category\ImageRole\Infrastructure\PdoCategoryImageRoleCommandRepository;
use Maatify\Category\ImageRole\Query\Infrastructure\PdoCategoryImageRoleManagementReadQuery;
use Maatify\Category\ImageRole\Query\Infrastructure\PdoCategoryImageRoleQueryReader;
use Maatify\Category\ImageRole\Service\ImageRoleService;
use Maatify\Category\Infrastructure\PdoCategoryCommandRepository;
use Maatify\Category\Query\Infrastructure\PdoCategoryManagementReadQuery;
use Maatify\Category\Query\Infrastructure\PdoCategoryQueryReader;
use Maatify\Category\Query\Infrastructure\PdoCategoryReadQuery;
use Maatify\Category\Service\CategoryService;
use Maatify\PhpPersistence\Ordering\Repository\ScopedOrderingManager;
use Maatify\PhpPersistence\Transaction\Repository\PdoTransactionRunner;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PDO;

/** Builds all package-owned adapters and Domain APIs from Host primitives. */
final class CategoryFactory
{
    private function __construct() {}

    /**
     * Composes all Domain APIs with the same Host PDO and Clock.
     *
     * The Host supplies an installed schema and retains ownership of its connection
     * and any already-open transaction. Composition does not open a connection or
     * mutate storage.
     */
    public static function create(PDO $pdo, ClockInterface $clock): CategoryFacadeInterface
    {
        return self::compose($pdo, $clock, null);
    }

    /**
     * Configures a lazy non-locking scope lookup for Image Assignment defaults.
     *
     * The Host provides a dedicated MySQL PDO to the same primary and schema,
     * with autocommit and exception mode enabled. All writes and locking reads
     * use the original Host PDO.
     *
     * @param callable(): PDO $openScopeLookup Opens the lookup when the Host snapshot cannot find an assignment.
     */
    public static function forHost(PDO $pdo, ClockInterface $clock, callable $openScopeLookup): CategoryFacadeInterface
    {
        $lookup = null;
        $scopeLookup = static function () use ($pdo, $openScopeLookup, &$lookup): PDO {
            if ($lookup === null) {
                $candidate = $openScopeLookup();
                self::validateScopeLookup($pdo, $candidate);
                $lookup = $candidate;
            }

            return $lookup;
        };

        return self::compose($pdo, $clock, $scopeLookup);
    }

    /** @param (Closure(): PDO)|null $scopeLookup */
    private static function compose(PDO $pdo, ClockInterface $clock, ?Closure $scopeLookup): CategoryFacadeInterface
    {
        $transaction = new PdoTransactionRunner($pdo);
        $ordering = new ScopedOrderingManager();
        $queryReader = new PdoCategoryQueryReader($pdo, $clock);
        $visibleReader = new PdoCategoryReadQuery($pdo, $clock);
        $managementReader = new PdoCategoryManagementReadQuery($pdo, $clock);
        $contentQueryReader = new PdoCategoryContentQueryReader($pdo, $clock);
        $contentVisibleReader = new PdoCategoryContentReadQuery($pdo, $clock);
        $contentManagementReader = new PdoCategoryContentManagementReadQuery($pdo, $clock);
        $contentFieldQueryReader = new PdoCategoryContentFieldQueryReader($pdo, $clock);
        $contentFieldVisibleReader = new PdoCategoryContentFieldReadQuery($pdo, $clock);
        $contentFieldManagementReader = new PdoCategoryContentFieldManagementReadQuery($pdo, $clock);
        $imageRoleQueryReader = new PdoCategoryImageRoleQueryReader($pdo, $clock);
        $imageRoleManagementReader = new PdoCategoryImageRoleManagementReadQuery($pdo, $clock);
        $imageAssignmentQueryReader = new PdoCategoryImageAssignmentQueryReader($pdo, $clock);
        $imageAssignmentVisibleReader = new PdoCategoryImageAssignmentReadQuery($pdo, $clock);
        $imageAssignmentManagementReader = new PdoCategoryImageAssignmentManagementReadQuery($pdo, $clock);

        return new CategoryFacade(
            new CategoryApi(new CategoryService(
                new PdoCategoryCommandRepository($pdo, $ordering),
                $queryReader,
                $visibleReader,
                $managementReader,
                $transaction,
                $clock,
            )),
            new ContentApi(new ContentService(
                new PdoCategoryContentCommandRepository($pdo),
                $queryReader,
                $contentQueryReader,
                $contentVisibleReader,
                $contentManagementReader,
                $transaction,
                $clock,
            )),
            new ContentFieldApi(new ContentFieldService(
                new PdoCategoryContentFieldCommandRepository($pdo, $ordering),
                $queryReader,
                $contentFieldQueryReader,
                $contentFieldVisibleReader,
                $contentFieldManagementReader,
                $transaction,
                $clock,
            )),
            new ImageRoleApi(new ImageRoleService(
                new PdoCategoryImageRoleCommandRepository($pdo),
                $imageRoleQueryReader,
                $imageRoleManagementReader,
                $transaction,
                $clock,
            )),
            new ImageAssignmentApi(new ImageAssignmentService(
                new PdoCategoryImageAssignmentCommandRepository($pdo, $ordering, $scopeLookup),
                $queryReader,
                $imageRoleQueryReader,
                $imageAssignmentQueryReader,
                $imageAssignmentVisibleReader,
                $imageAssignmentManagementReader,
                $transaction,
                $clock,
            )),
        );
    }

    private static function validateScopeLookup(PDO $pdo, PDO $lookup): void
    {
        if ($lookup === $pdo || $lookup->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql'
            || $lookup->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION
            || $lookup->inTransaction()
            || !(bool) $lookup->getAttribute(PDO::ATTR_AUTOCOMMIT)
        ) {
            throw new CategoryInvalidArgumentException(
                'The scope lookup requires a separate MySQL connection in autocommit and exception mode.',
            );
        }

        $hostDatabase = self::databaseIdentity($pdo);
        if ($hostDatabase === null || self::databaseIdentity($lookup) !== $hostDatabase) {
            throw new CategoryInvalidArgumentException('The scope lookup must use the same MySQL server and selected schema as the Host.');
        }
    }

    private static function databaseIdentity(PDO $pdo): ?string
    {
        $statement = $pdo->query("SELECT CONCAT(@@server_uuid, ':', DATABASE())");
        $identity = $statement === false ? null : $statement->fetchColumn();

        return is_string($identity) && $identity !== '' ? $identity : null;
    }
}
