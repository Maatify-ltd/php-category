<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\ContentField\CategoryContentFieldFormatEnum;
use Maatify\Category\ContentField\CategoryContentFieldScopeDTO;
use Maatify\Category\ContentField\Mutation\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\SoftDeleteCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Exception\CategoryContentFieldAlreadyExistsException;
use Maatify\Category\ContentField\Ordering\Command\UpdateCategoryContentFieldDisplayOrderCommand;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldListCriteriaDTO;
use Maatify\Category\Factory\CategoryFactory;
use Maatify\Category\ImageAssignment\Assignment\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Assignment\Exception\CategoryImageAssignmentAlreadyExistsException;
use Maatify\Category\ImageAssignment\CategoryImageAssignmentScopeDTO;
use Maatify\Category\ImageAssignment\Default\Command\ClearCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageAssignment\Default\Command\SetCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageAssignment\Lifecycle\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Ordering\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentListCriteriaDTO;
use Maatify\Category\Lifecycle\Command\CreateCategoryCommand;
use Maatify\Category\Tests\Integration\Support\CategoryMySqlIntegrationTestCase;
use Maatify\Category\Tests\Integration\Support\FixedCategoryClock;
use Maatify\PhpPersistence\Pagination\Criteria\PageRequest;
use PHPUnit\Framework\Attributes\DataProvider;

final class CategoryScopeEqualityIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    #[DataProvider('equivalentScopes')]
    public function testImageScopeEqualityControlsVisibilityOrderingAndDefault(
        string $languageA,
        string $platformA,
        string $languageB,
        string $platformB,
    ): void {
        $facade = CategoryFactory::create($this->connection(), new FixedCategoryClock());
        $id = $facade->categories()->create(new CreateCategoryCommand('image-scope-equality'));
        $images = $facade->images();
        $scopeA = new CategoryImageAssignmentScopeDTO($languageA, $platformA);
        $scopeB = new CategoryImageAssignmentScopeDTO($languageB, $platformB);
        $first = $images->assign(new CreateCategoryImageAssignmentCommand($id, 100, $scopeA));
        $second = $images->assign(new CreateCategoryImageAssignmentCommand($id, 200, $scopeB));

        $images->setDefault(new SetCategoryImageAssignmentDefaultCommand($first));
        $images->setDefault(new SetCategoryImageAssignmentDefaultCommand($second));
        $visible = iterator_to_array($images->listVisibleForCategory($id, $scopeA), false);
        self::assertSame([$first, $second], array_column($visible, 'id'));
        self::assertSame([false, true], array_column($visible, 'isDefault'));
        self::assertSame([1, 2], array_column($visible, 'displayOrder'));
        self::assertSame($languageB, $images->getByIdForManagement($second)->languageCode);
        self::assertSame($platformB, $images->getByIdForManagement($second)->platform);

        $images->reorder(new UpdateCategoryImageAssignmentDisplayOrderCommand($second, 1));
        $images->clearDefault(new ClearCategoryImageAssignmentDefaultCommand($second));
        $criteria = new CategoryImageAssignmentListCriteriaDTO(categoryId: $id, scope: $scopeB);
        $managed = iterator_to_array($images->listForManagement($criteria), false);
        self::assertSame([$second, $first], array_column($managed, 'id'));
        self::assertSame([false, false], array_column($managed, 'isDefault'));
        self::assertSame([1, 2], array_column($managed, 'displayOrder'));
        $page = $images->paginateForManagement($criteria, new PageRequest());
        self::assertSame(2, $page->total);
        self::assertSame([$second, $first], array_column($page->data, 'id'));
    }

    #[DataProvider('equivalentScopes')]
    public function testFieldScopeEqualityControlsVisibilityAndOrdering(
        string $languageA,
        string $platformA,
        string $languageB,
        string $platformB,
    ): void {
        $facade = CategoryFactory::create($this->connection(), new FixedCategoryClock());
        $id = $facade->categories()->create(new CreateCategoryCommand('field-scope-equality'));
        $fields = $facade->contentFields();
        $format = CategoryContentFieldFormatEnum::TEXT;
        $first = $fields->create(new CreateCategoryContentFieldCommand($id, 'first', $languageA, $platformA, $format, 'First'));
        $second = $fields->create(new CreateCategoryContentFieldCommand($id, 'second', $languageB, $platformB, $format, 'Second'));

        $scopeA = new CategoryContentFieldScopeDTO($languageA, $platformA);
        $visible = iterator_to_array($fields->listVisibleForCategory($id, $scopeA), false);
        self::assertSame([$first, $second], array_column($visible, 'id'));
        self::assertSame([1, 2], array_column($visible, 'displayOrder'));

        $fields->updateDisplayOrder(new UpdateCategoryContentFieldDisplayOrderCommand($second, 1));
        $scopeB = new CategoryContentFieldScopeDTO($languageB, $platformB);
        $criteria = new CategoryContentFieldListCriteriaDTO(categoryId: $id, scope: $scopeB);
        $managed = iterator_to_array($fields->listForManagement($criteria), false);
        self::assertSame([$second, $first], array_column($managed, 'id'));
        self::assertSame([1, 2], array_column($managed, 'displayOrder'));
        $page = $fields->paginateForManagement($criteria, new PageRequest());
        self::assertSame(2, $page->total);
        self::assertSame([$second, $first], array_column($page->data, 'id'));
    }

    public function testNullAndSeparatorLikeValuesStayInSeparateScopes(): void
    {
        $facade = CategoryFactory::create($this->connection(), new FixedCategoryClock());
        $id = $facade->categories()->create(new CreateCategoryCommand('distinct-scope-equality'));
        $format = CategoryContentFieldFormatEnum::TEXT;
        foreach ([[null, null], ["\u{200B}", null], ['A|B', 'C'], ['A', 'B|C']] as [$language, $platform]) {
            $imageScope = new CategoryImageAssignmentScopeDTO($language, $platform);
            $imageId = $facade->images()->assign(new CreateCategoryImageAssignmentCommand($id, 100, $imageScope));
            $facade->images()->setDefault(new SetCategoryImageAssignmentDefaultCommand($imageId));
            $fieldId = $facade->contentFields()->create(
                new CreateCategoryContentFieldCommand($id, 'label', $language, $platform, $format, 'Label'),
            );
            $images = iterator_to_array($facade->images()->listVisibleForCategory($id, $imageScope), false);
            $fieldScope = new CategoryContentFieldScopeDTO($language, $platform);
            $fields = iterator_to_array($facade->contentFields()->listVisibleForCategory($id, $fieldScope), false);
            self::assertSame([$imageId], array_column($images, 'id'));
            self::assertSame([true], array_column($images, 'isDefault'));
            self::assertSame([1], array_column($images, 'displayOrder'));
            self::assertSame([$fieldId], array_column($fields, 'id'));
            self::assertSame([1], array_column($fields, 'displayOrder'));
        }
    }

    public function testCollationEquivalentIdentityRemainsReservedAfterSoftDeletion(): void
    {
        $facade = CategoryFactory::create($this->connection(), new FixedCategoryClock());
        $id = $facade->categories()->create(new CreateCategoryCommand('reserved-scope-equality'));
        $images = $facade->images();
        $fields = $facade->contentFields();
        $format = CategoryContentFieldFormatEnum::TEXT;
        $image = $images->assign(new CreateCategoryImageAssignmentCommand(
            $id,
            100,
            new CategoryImageAssignmentScopeDTO('en', 'Web'),
        ));
        $field = $fields->create(new CreateCategoryContentFieldCommand($id, 'label', 'en', 'Web', $format, 'Label'));
        $images->remove(new SoftDeleteCategoryImageAssignmentCommand($image));
        $fields->softDelete(new SoftDeleteCategoryContentFieldCommand($field));

        try {
            $images->assign(new CreateCategoryImageAssignmentCommand(
                $id,
                100,
                new CategoryImageAssignmentScopeDTO('EN ', 'web '),
            ));
            self::fail('An equivalent Image Assignment identity remains reserved.');
        } catch (CategoryImageAssignmentAlreadyExistsException) {
            self::assertTrue($images->listVisibleForCategory($id, new CategoryImageAssignmentScopeDTO('en', 'web'))->isEmpty());
        }

        $this->expectException(CategoryContentFieldAlreadyExistsException::class);
        $fields->create(new CreateCategoryContentFieldCommand($id, 'label', 'EN ', 'web ', $format, 'New label'));
    }

    /** @return iterable<string, array{string, string, string, string}> */
    public static function equivalentScopes(): iterable
    {
        yield 'case' => ['en', 'Web', 'EN', 'wEB'];
        yield 'trailing spaces' => ['en', 'web', 'en ', 'web '];
        yield 'decomposed accent' => ['café', 'béta', "cafe\u{0301}", 'beta'];
        yield 'embedded separator and trailing space' => ['en|N:', 'web|L3:app', 'EN|N: ', 'WEB|L3:APP '];
    }
}
