<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Common\Exception\CategoryInvalidArgumentException;
use Maatify\Category\ContentField\CategoryContentFieldFormatEnum;
use Maatify\Category\ContentField\Mutation\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\ContentField\Ordering\Command\UpdateCategoryContentFieldDisplayOrderCommand;
use Maatify\Category\Facade\Contract\CategoryFacadeInterface;
use Maatify\Category\Factory\CategoryFactory;
use Maatify\Category\Hierarchy\Command\MoveCategoryCommand;
use Maatify\Category\ImageAssignment\Assignment\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\CategoryImageAssignmentScopeDTO;
use Maatify\Category\ImageAssignment\Default\Command\ClearCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageAssignment\Default\Command\SetCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageAssignment\Ordering\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\Lifecycle\Command\CreateCategoryCommand;
use Maatify\Category\Ordering\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Tests\Integration\Support\CategoryMySqlIntegrationTestCase;
use Maatify\Category\Tests\Integration\Support\FixedCategoryClock;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;

final class CategoryCurrentScopeIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    public function testCategoryReorderUsesTheCurrentParentWithinAStaleHostSnapshot(): void
    {
        $writer = $this->facade($this->connection())->categories();
        $firstParent = $writer->create(new CreateCategoryCommand('first-parent'));
        $secondParent = $writer->create(new CreateCategoryCommand('second-parent'));
        $writer->create(new CreateCategoryCommand('first-sibling', $firstParent));
        $writer->create(new CreateCategoryCommand('second-sibling', $secondParent));
        $target = $writer->create(new CreateCategoryCommand('target', $firstParent));
        $host = $this->beginHostSnapshot();
        $categories = $this->facade($host)->categories();

        try {
            $writer->move(new MoveCategoryCommand($target, $secondParent));
            self::assertSame($firstParent, $categories->getByIdForManagement($target)->parentId);

            $categories->updateDisplayOrder(new UpdateCategoryDisplayOrderCommand($target, 1));
            self::assertTrue($host->inTransaction());
            self::assertSame(1, $categories->getByIdForManagement($target)->displayOrder);
        } finally {
            $host->rollBack();
        }
        self::assertSame($secondParent, $writer->getByIdForManagement($target)->parentId);
        self::assertSame(2, $writer->getByIdForManagement($target)->displayOrder);
    }

    public function testContentFieldReorderFindsAFieldInsertedAfterTheHostSnapshot(): void
    {
        $writer = $this->facade($this->connection());
        $categoryId = $writer->categories()->create(new CreateCategoryCommand('field-owner'));
        $fields = $writer->contentFields();
        $fields->create($this->fieldCommand($categoryId, 'first'));
        $host = $this->beginHostSnapshot();
        $hostFields = $this->facade($host)->contentFields();

        try {
            $target = $fields->create($this->fieldCommand($categoryId, 'target'));
            $hostFields->updateDisplayOrder(new UpdateCategoryContentFieldDisplayOrderCommand($target, 1));
            self::assertTrue($host->inTransaction());
            self::assertSame(1, $hostFields->getByIdForManagement($target)->displayOrder);
        } finally {
            $host->rollBack();
        }
        self::assertSame(2, $fields->getByIdForManagement($target)->displayOrder);
    }

    public function testImageAssignmentReorderFindsAnAssignmentInsertedAfterTheHostSnapshot(): void
    {
        $writer = $this->facade($this->connection());
        $categoryId = $writer->categories()->create(new CreateCategoryCommand('image-owner'));
        $images = $writer->images();
        $images->assign($this->imageCommand($categoryId, 1));
        $host = $this->beginHostSnapshot();
        $hostImages = $this->facade($host)->images();

        try {
            $target = $images->assign($this->imageCommand($categoryId, 2));
            $hostImages->reorder(new UpdateCategoryImageAssignmentDisplayOrderCommand($target, 1));
            self::assertTrue($host->inTransaction());
            self::assertSame(1, $hostImages->getByIdForManagement($target)->displayOrder);
        } finally {
            $host->rollBack();
        }
        self::assertSame(2, $images->getByIdForManagement($target)->displayOrder);
    }

    #[DataProvider('defaultChanges')]
    public function testDefaultChangeFindsAnAssignmentInsertedAfterTheHostSnapshot(bool $initialDefault): void
    {
        $writer = $this->facade($this->connection());
        $categoryId = $writer->categories()->create(new CreateCategoryCommand('default-owner'));
        $images = $writer->images();
        $host = $this->beginHostSnapshot();
        $lookupAttempts = 0;
        $hostImages = CategoryFactory::forHost(
            $host,
            new FixedCategoryClock(),
            function () use (&$lookupAttempts): PDO {
                $lookupAttempts++;

                return $this->newConnection();
            },
        )->images();
        self::assertSame(0, $lookupAttempts);

        try {
            $target = $images->assign($this->imageCommand($categoryId, 1));
            if ($initialDefault) {
                $images->setDefault(new SetCategoryImageAssignmentDefaultCommand($target));
                $hostImages->clearDefault(new ClearCategoryImageAssignmentDefaultCommand($target));
            } else {
                $hostImages->setDefault(new SetCategoryImageAssignmentDefaultCommand($target));
            }
            self::assertTrue($host->inTransaction());
            self::assertSame(!$initialDefault, $hostImages->getByIdForManagement($target)->isDefault);
            self::assertSame(1, $lookupAttempts);
        } finally {
            $host->rollBack();
        }
        self::assertSame($initialDefault, $images->getByIdForManagement($target)->isDefault);
    }

    public function testHostOwnImageAssignmentDoesNotOpenScopeLookup(): void
    {
        $writer = $this->facade($this->connection());
        $categoryId = $writer->categories()->create(new CreateCategoryCommand('host-image-owner'));
        $host = $this->beginHostSnapshot();
        $hostImages = CategoryFactory::forHost(
            $host,
            new FixedCategoryClock(),
            static fn(): PDO => throw new \RuntimeException('The Host can see its own write.'),
        )->images();

        try {
            $target = $hostImages->assign($this->imageCommand($categoryId, 1));
            $hostImages->setDefault(new SetCategoryImageAssignmentDefaultCommand($target));
            self::assertTrue($hostImages->getByIdForManagement($target)->isDefault);
            $hostImages->clearDefault(new ClearCategoryImageAssignmentDefaultCommand($target));
            self::assertFalse($hostImages->getByIdForManagement($target)->isDefault);
            self::assertTrue($host->inTransaction());
        } finally {
            $host->rollBack();
        }
    }

    public function testDefaultLookupRetriesAnInvalidConnectionAndKeepsScopeBeforeTarget(): void
    {
        $writer = $this->facade($this->connection());
        $categoryId = $writer->categories()->create(new CreateCategoryCommand('contested-image-owner'));
        $images = $writer->images();
        $first = $images->assign($this->imageCommand($categoryId, 1));
        $host = $this->beginHostSnapshot();
        $host->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $target = $images->assign($this->imageCommand($categoryId, 2));
        $wrongSchema = $this->newConnection();
        $wrongSchema->exec('USE information_schema');
        $lookupAttempts = 0;
        $hostImages = CategoryFactory::forHost(
            $host,
            new FixedCategoryClock(),
            function () use ($host, $wrongSchema, &$lookupAttempts): PDO {
                $lookupAttempts++;

                return match ($lookupAttempts) {
                    1 => $host,
                    2 => $wrongSchema,
                    default => $this->newConnection(),
                };
            },
        )->images();

        try {
            $command = new SetCategoryImageAssignmentDefaultCommand($target);
            foreach (['separate MySQL connection', 'same MySQL server and selected schema'] as $reason) {
                try {
                    $hostImages->setDefault($command);
                    self::fail('An invalid lookup connection was accepted.');
                } catch (CategoryInvalidArgumentException $exception) {
                    self::assertStringContainsString($reason, $exception->getMessage());
                    self::assertTrue($host->inTransaction());
                }
            }

            $locker = $this->newConnection();
            $locker->beginTransaction();
            try {
                $this->lockAssignment($locker, $first);
                try {
                    $hostImages->setDefault($command);
                    self::fail('The default mutation must wait on the earlier scope row.');
                } catch (PDOException $exception) {
                    self::assertSame(1205, $exception->errorInfo[1] ?? null);
                    self::assertTrue($host->inTransaction());
                }

                $locker->exec('SET SESSION innodb_lock_wait_timeout = 1');
                $this->lockAssignment($locker, $target);
            } finally {
                $locker->rollBack();
            }

            $hostImages->setDefault($command);
            self::assertTrue($hostImages->getByIdForManagement($target)->isDefault);
            self::assertSame(3, $lookupAttempts);
        } finally {
            $host->rollBack();
        }
        self::assertFalse($images->getByIdForManagement($target)->isDefault);
    }

    private function lockAssignment(PDO $pdo, int $id): void
    {
        $statement = $pdo->prepare('SELECT id FROM maa_category_category_image_assignments WHERE id = ? FOR UPDATE');
        $statement->execute([$id]);
        self::assertSame($id, $statement->fetchColumn());
    }

    /** @return array<string, array{bool}> */
    public static function defaultChanges(): array
    {
        return ['set default' => [false], 'clear default' => [true]];
    }

    private function facade(PDO $pdo): CategoryFacadeInterface
    {
        return CategoryFactory::create($pdo, new FixedCategoryClock());
    }

    private function beginHostSnapshot(): PDO
    {
        $host = $this->newConnection();
        $host->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $host->beginTransaction();
        $host->query('SELECT COUNT(*) FROM maa_category_categories');

        return $host;
    }

    private function fieldCommand(int $categoryId, string $key): CreateCategoryContentFieldCommand
    {
        return new CreateCategoryContentFieldCommand(
            categoryId: $categoryId,
            fieldKey: $key,
            languageCode: null,
            platform: null,
            format: CategoryContentFieldFormatEnum::TEXT,
            value: 'value',
        );
    }

    private function imageCommand(int $categoryId, int $mediaAssetId): CreateCategoryImageAssignmentCommand
    {
        return new CreateCategoryImageAssignmentCommand($categoryId, $mediaAssetId, new CategoryImageAssignmentScopeDTO());
    }
}
