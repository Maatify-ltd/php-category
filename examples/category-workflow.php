<?php

declare(strict_types=1);

use Maatify\Category\Content\Mutation\Command\CreateCategoryContentCommand;
use Maatify\Category\ContentField\CategoryContentFieldFormatEnum;
use Maatify\Category\ContentField\CategoryContentFieldScopeDTO;
use Maatify\Category\ContentField\Mutation\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\Factory\CategoryFactory;
use Maatify\Category\ImageAssignment\Assignment\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\CategoryImageAssignmentScopeDTO;
use Maatify\Category\ImageAssignment\Default\Command\SetCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\CreateCategoryImageRoleCommand;
use Maatify\Category\Lifecycle\Command\CreateCategoryCommand;
use Maatify\Category\Lifecycle\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Lifecycle\Enum\CategoryStatusEnum;
use Maatify\Category\Query\DTO\CategoryListCriteriaDTO;
use Maatify\PhpPersistence\Pagination\Criteria\PageRequest;
use Maatify\SharedCommon\Infrastructure\SystemClock;

require dirname(__DIR__) . '/vendor/autoload.php';

$dsn = getenv('CATEGORY_TEST_DSN');
$username = getenv('CATEGORY_TEST_DB_USER');
$password = getenv('CATEGORY_TEST_DB_PASSWORD');
if ($dsn === false || $dsn === '' || $username === false || $username === '' || $password === false) {
    throw new RuntimeException('Set CATEGORY_TEST_DSN, CATEGORY_TEST_DB_USER and CATEGORY_TEST_DB_PASSWORD.');
}

$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$category = CategoryFactory::create($pdo, new SystemClock(new DateTimeZone('UTC')));

$pdo->beginTransaction();
try {
    $suffix = bin2hex(random_bytes(6));
    $childCode = 'example-child-' . $suffix;

    // Create a visible hierarchy that the Host can render.
    $rootId = $category->categories()->create(new CreateCategoryCommand('example-root-' . $suffix));
    $childId = $category->categories()->create(new CreateCategoryCommand($childCode, $rootId));
    if (count($category->categories()->listChildren($rootId)) !== 1) {
        throw new RuntimeException('The child category was not visible.');
    }

    // Publish localized content and JSON metadata in one language/platform scope.
    $category->contents()->create(new CreateCategoryContentCommand($childId, 'en-US', 'Example', 'Category guide'));
    $category->contentFields()->create(new CreateCategoryContentFieldCommand(
        $childId,
        'badge_config',
        'en-US',
        'web',
        CategoryContentFieldFormatEnum::JSON,
        '{"enabled":true}',
    ));
    if (
        count($category->contents()->listVisibleForCategory($childId)) !== 1
        || count($category->contentFields()->listVisibleForCategory(
            $childId,
            new CategoryContentFieldScopeDTO('en-US', 'web'),
        )) !== 1
    ) {
        throw new RuntimeException('The visible content or scoped field was not found.');
    }

    // In a real Host, the asset ID comes from the Host's Media/Storage workflow.
    $roleId = $category->imageRoles()->create(new CreateCategoryImageRoleCommand('gallery_' . $suffix));
    $scope = new CategoryImageAssignmentScopeDTO('en-US', 'web', $roleId);
    $imageId = $category->images()->assign(new CreateCategoryImageAssignmentCommand($childId, 700, $scope));
    $category->images()->setDefault(new SetCategoryImageAssignmentDefaultCommand($imageId));
    $images = iterator_to_array($category->images()->listVisibleForCategory($childId, $scope), false);
    if (
        count($images) !== 1
        || $images[0]->id !== $imageId
        || !$images[0]->isDefault
        || !$category->images()->listVisibleForCategory($childId, new CategoryImageAssignmentScopeDTO())->isEmpty()
    ) {
        throw new RuntimeException('The default image was not isolated to its exact scope.');
    }

    // An inactive category disappears from consumer reads but remains in management.
    $category->categories()->updateStatus(new UpdateCategoryStatusCommand($childId, CategoryStatusEnum::INACTIVE));
    $page = $category->categories()->paginateForManagement(
        new CategoryListCriteriaDTO(search: $childCode),
        new PageRequest(page: 1, perPage: 10, sortBy: 'id'),
    );
    if (
        count($category->categories()->listChildren($rootId)) !== 0
        || count($page->data) !== 1
        || $page->data[0]->id !== $childId
    ) {
        throw new RuntimeException('The inactive category was not isolated to the management read.');
    }
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "Category example completed.\n";
