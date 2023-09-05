<?php

namespace Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Overtrue\LaravelLike\Events\Liked;
use Overtrue\LaravelLike\Events\Unliked;

class FeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['auth.providers.users.model' => User::class]);
    }

    public function test_user_can_like_and_unlike_a_post()
    {
        // Arrange
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'Hello world!']);

        // Act - User likes the post
        $user->like($post);

        // Assert - Liked event is dispatched with correct data
        Event::assertDispatched(Liked::class, function ($event) use ($user, $post) {
            return $event->like->likeable instanceof Post
                && $event->like->user instanceof User
                && $event->like->user->id === $user->id
                && $event->like->likeable->id === $post->id;
        });

        // Assert - User has liked the post and post is liked by user
        $this->assertTrue($user->hasLiked($post), 'User should have liked the post');
        $this->assertTrue($post->isLikedBy($user), 'Post should be liked by the user');

        // Act - User unlikes the post
        $this->assertTrue($user->unlike($post), 'Unlike should return true');

        // Assert - Unliked event is dispatched with correct data
        Event::assertDispatched(Unliked::class, function ($event) use ($user, $post) {
            return $event->like->likeable instanceof Post
                && $event->like->user instanceof User
                && $event->like->user->id === $user->id
                && $event->like->likeable->id === $post->id;
        });
    }

    public function test_multiple_users_can_like_same_post_independently()
    {
        // Arrange
        $user1 = User::create(['name' => 'overtrue']);
        $user2 = User::create(['name' => 'allen']);
        $user3 = User::create(['name' => 'taylor']);
        $post = Post::create(['title' => 'Hello world!']);

        // Act - All users like the same post
        $user2->like($post);
        $user3->like($post);
        $user1->like($post);

        // Act - One user unlikes the post
        $user1->unlike($post);

        // Assert - Only the unliking user's status changes
        $this->assertFalse($user1->hasLiked($post), 'User1 should not have liked the post after unliking');
        $this->assertTrue($user2->hasLiked($post), 'User2 should still have liked the post');
        $this->assertTrue($user3->hasLiked($post), 'User3 should still have liked the post');
    }

    public function test_user_can_like_multiple_objects_and_aggregate_counts()
    {
        // Arrange
        $user = User::create(['name' => 'overtrue']);
        $post1 = Post::create(['title' => 'Hello world!']);
        $post2 = Post::create(['title' => 'Hello everyone!']);
        $book1 = Book::create(['title' => 'Learn laravel.']);
        $book2 = Book::create(['title' => 'Learn symfony.']);

        // Act - User likes multiple objects
        $user->like($post1);
        $user->like($post2);
        $user->like($book1);
        $user->like($book2);

<<<<<<< HEAD
        // Assert - Total likes count
        $this->assertSame(4, $user->likes()->count(), 'User should have 4 total likes');

        // Assert - Filtered likes count by type
        $this->assertSame(2, $user->likes()->withType(Book::class)->count(), 'User should have 2 book likes');
=======
        $this->assertSame(4, $user->likes()->count());
        $this->assertSame(2, $user->likes()->withType(Book::class)->count());
>>>>>>> fc2847d (totalLikers attribute)
        $this->assertSame(4, $user->totalLikes);
    }

    public function test_user_can_like_another_user()
    {
        // Arrange
        $user1 = User::create(['name' => 'overtrue']);
        $user2 = User::create(['name' => 'allen']);

        // Act - User1 likes User2
        $user1->like($user2);

        // Assert - Like relationship is established
        $this->assertTrue($user1->hasLiked($user2), 'User1 should have liked User2');
        $this->assertTrue($user2->isLikedBy($user1), 'User2 should be liked by User1');
    }

    public function test_post_can_retrieve_its_likers_and_check_like_status_efficiently()
    {
        // Arrange
        $user1 = User::create(['name' => 'overtrue']);
        $user2 = User::create(['name' => 'allen']);
        $user3 = User::create(['name' => 'taylor']);
        $post = Post::create(['title' => 'Hello world!']);

        // Act - Two users like the post
        $user1->like($post);
        $user2->like($post);

        // Assert - Post can retrieve its likers
        $this->assertCount(2, $post->likers, 'Post should have 2 likers');
        $this->assertSame('overtrue', $post->likers[0]['name'], 'First liker should be overtrue');
        $this->assertSame('allen', $post->likers[1]['name'], 'Second liker should be allen');

        // Assert - Like status checks are efficient (no additional queries)
        $sqls = $this->getQueryLog(function () use ($post, $user1, $user2, $user3) {
            $this->assertTrue($post->isLikedBy($user1), 'Post should be liked by user1');
            $this->assertTrue($post->isLikedBy($user2), 'Post should be liked by user2');
            $this->assertFalse($post->isLikedBy($user3), 'Post should not be liked by user3');
        });

        $this->assertEmpty($sqls->all(), 'No additional queries should be executed when checking like status');
    }

    public function test_like_works_with_custom_morph_class_name()
    {
        // Arrange
        $user1 = User::create(['name' => 'overtrue']);
        $user2 = User::create(['name' => 'allen']);
        $post = Post::create(['title' => 'Hello world!']);

        // Act - Set custom morph map
        Relation::morphMap([
            'posts' => Post::class,
        ]);

        $user1->like($post);
        $user2->like($post);

        // Assert - Like functionality works with custom morph class
        $this->assertCount(2, $post->likers, 'Post should have 2 likers with custom morph class');
        $this->assertSame('overtrue', $post->likers[0]['name'], 'First liker should be overtrue');
        $this->assertSame('allen', $post->likers[1]['name'], 'Second liker should be allen');
    }

    public function test_eager_loading_prevents_n_plus_one_queries()
    {
        // Arrange
        $user = User::create(['name' => 'overtrue']);
        $post1 = Post::create(['title' => 'Hello world!']);
        $post2 = Post::create(['title' => 'Hello everyone!']);
        $book1 = Book::create(['title' => 'Learn laravel.']);
        $book2 = Book::create(['title' => 'Learn symfony.']);

        $user->like($post1);
        $user->like($post2);
        $user->like($book1);
        $user->like($book2);

        // Act & Assert - Eager loading should use minimal queries
        $sqls = $this->getQueryLog(function () use ($user) {
            $user->load('likes.likeable');
        });

        $this->assertSame(3, $sqls->count(), 'Eager loading should use 3 queries (likes + 2 likeable types)');

        // Act & Assert - Checking liked status after eager loading should not trigger additional queries
        $sqls = $this->getQueryLog(function () use ($user, $post1) {
            $user->hasLiked($post1);
        });

        $this->assertEmpty($sqls->all(), 'No additional queries should be executed when checking liked status after eager loading');
    }

    public function test_user_can_attach_like_status_to_various_collection_types()
    {
        // Arrange
        $post1 = Post::create(['title' => 'Post title1']);
        $post2 = Post::create(['title' => 'Post title2']);
        $post3 = Post::create(['title' => 'Post title3']);
        $user = User::create(['name' => 'overtrue']);

        $user->like($post1);
        $user->like($post2);

        // Test single model
        $post1 = Post::find($post1->id);
        $this->assertNull($post1->has_liked, 'Model should not have like status initially');
        $user->attachLikeStatus($post1);
        $this->assertTrue($post1->has_liked, 'Model should have like status after attachment');

        // Test collection
        $posts = Post::oldest('id')->get();
        $user->attachLikeStatus($posts);
        $this->assertTrue($posts[0]['has_liked'], 'First post in collection should be liked');
        $this->assertTrue($posts[1]['has_liked'], 'Second post in collection should be liked');
        $this->assertFalse($posts[2]['has_liked'], 'Third post in collection should not be liked');

        // Test lazy collection
        $posts = Post::oldest('id')->cursor();
        $user->attachLikeStatus($posts);
        $posts = $posts->toArray();
        $this->assertTrue($posts[0]['has_liked'], 'First post in lazy collection should be liked');
        $this->assertTrue($posts[1]['has_liked'], 'Second post in lazy collection should be liked');
        $this->assertFalse($posts[2]['has_liked'], 'Third post in lazy collection should not be liked');

        // Test paginator
        $posts = Post::oldest('id')->paginate();
        $user->attachLikeStatus($posts);
        $this->assertTrue($posts[0]['has_liked'], 'First post in paginator should be liked');
        $this->assertTrue($posts[1]['has_liked'], 'Second post in paginator should be liked');
        $this->assertFalse($posts[2]['has_liked'], 'Third post in paginator should not be liked');

        // Test cursor paginator
        $posts = Post::oldest('id')->cursorPaginate();
        $user->attachLikeStatus($posts);
        $this->assertTrue($posts[0]['has_liked'], 'First post in cursor paginator should be liked');
        $this->assertTrue($posts[1]['has_liked'], 'Second post in cursor paginator should be liked');
        $this->assertFalse($posts[2]['has_liked'], 'Third post in cursor paginator should not be liked');

        // Test array
        $posts = Post::oldest('id')->get()->all();
        $user->attachLikeStatus($posts);
        $this->assertTrue($posts[0]['has_liked'], 'First post in array should be liked');
        $this->assertTrue($posts[1]['has_liked'], 'Second post in array should be liked');
        $this->assertFalse($posts[2]['has_liked'], 'Third post in array should not be liked');

        // Test with custom resolver
        $posts = [['post' => $post1], ['post' => $post2], ['post' => $post3]];
        $user->attachLikeStatus($posts, fn ($i) => $i['post']);

        $this->assertTrue($posts[0]['post']['has_liked'], 'First post with custom resolver should be liked');
        $this->assertTrue($posts[1]['post']['has_liked'], 'Second post with custom resolver should be liked');
        $this->assertFalse($posts[2]['post']['has_liked'], 'Third post with custom resolver should not be liked');
    }

    public function test_toggle_like_functionality()
    {
        // Arrange
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'Hello world!']);

        // Act & Assert - First toggle should like the post
        $like = $user->toggleLike($post);
        $this->assertInstanceOf(\Overtrue\LaravelLike\Like::class, $like, 'Toggle should return Like instance when liking');
        $this->assertTrue($user->hasLiked($post), 'User should have liked the post after toggle');

        // Act & Assert - Second toggle should unlike the post
        $result = $user->toggleLike($post);
        $this->assertTrue($result, 'Toggle should return true when unliking');
        $this->assertFalse($user->hasLiked($post), 'User should not have liked the post after second toggle');
    }

    public function test_like_returns_existing_like_when_already_liked()
    {
        // Arrange
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'Hello world!']);

        // Act - Like the post twice
        $like1 = $user->like($post);
        $like2 = $user->like($post);

        // Assert - Should return the same like instance
        $this->assertSame($like1->id, $like2->id, 'Liking the same post twice should return the same like instance');
        $this->assertSame(1, $user->likes()->count(), 'Should only have one like record');
    }

    public function test_unlike_returns_true_when_no_like_exists()
    {
        // Arrange
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'Hello world!']);

        // Act - Try to unlike a post that was never liked
        $result = $user->unlike($post);

        // Assert - Should return true even when no like exists
        $this->assertTrue($result, 'Unlike should return true even when no like exists');
    }

    public function test_like_model_relationships()
    {
        // Arrange
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'Hello world!']);
        $like = $user->like($post);

        // Assert - Like model relationships work correctly
        $this->assertInstanceOf(User::class, $like->user, 'Like should belong to user');
        $this->assertInstanceOf(Post::class, $like->likeable, 'Like should belong to likeable post');
        $this->assertSame($user->id, $like->user->id, 'Like user ID should match');
        $this->assertSame($post->id, $like->likeable->id, 'Like likeable ID should match');
    }

    public function test_like_model_scope_with_type()
    {
        // Arrange
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'Hello world!']);
        $book = Book::create(['title' => 'Learn Laravel']);

        $user->like($post);
        $user->like($book);

        // Act & Assert - Scope should filter by type
        $postLikes = \Overtrue\LaravelLike\Like::withType(Post::class)->get();
        $bookLikes = \Overtrue\LaravelLike\Like::withType(Book::class)->get();

        $this->assertCount(1, $postLikes, 'Should have 1 post like');
        $this->assertCount(1, $bookLikes, 'Should have 1 book like');
        $this->assertInstanceOf(Post::class, $postLikes->first()->likeable, 'First like should be a post');
        $this->assertInstanceOf(Book::class, $bookLikes->first()->likeable, 'First like should be a book');
    }

    public function test_get_liked_items_functionality()
    {
        // Arrange
        $user = User::create(['name' => 'overtrue']);
        $post1 = Post::create(['title' => 'Post 1']);
        $post2 = Post::create(['title' => 'Post 2']);
        $book = Book::create(['title' => 'Book 1']);

        $user->like($post1);
        $user->like($book);

        // Act - Get liked posts
        $likedPosts = $user->getLikedItems(Post::class)->get();

        // Assert - Should only return liked posts
        $this->assertCount(1, $likedPosts, 'Should return 1 liked post');
        $this->assertSame($post1->id, $likedPosts->first()->id, 'Should return the correct liked post');
    }

    public function test_attach_like_status_with_invalid_argument_throws_exception()
    {
        // Arrange
        $user = User::create(['name' => 'overtrue']);
        $invalidArgument = 'invalid string';

        // Act & Assert - Should throw InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument type.');

        $user->attachLikeStatus($invalidArgument);
    }

    public function test_like_works_with_different_user_foreign_key_config()
    {
        // This test is skipped because it requires database schema changes
        // In a real application, you would need to create a migration to add the custom column
        $this->markTestSkipped('Requires database schema changes to add custom foreign key column');
    }

    public function test_like_works_with_uuid_primary_keys()
    {
        // This test is skipped because it requires database schema changes
        // In a real application, you would need to create a migration to change the primary key type
        $this->markTestSkipped('Requires database schema changes to use UUID primary keys');
    }

    public function test_like_events_contain_correct_data()
    {
        // Arrange
        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'Hello world!']);

        // Act
        $user->like($post);

        // Assert - Liked event should contain correct data
        Event::assertDispatched(Liked::class, function ($event) use ($user, $post) {
            return $event->like instanceof \Overtrue\LaravelLike\Like
                && $event->like->user_id === $user->id
                && $event->like->likeable_id === $post->id
                && $event->like->likeable_type === $post->getMorphClass();
        });

        // Act
        $user->unlike($post);

        // Assert - Unliked event should contain correct data
        Event::assertDispatched(Unliked::class, function ($event) use ($user, $post) {
            return $event->like instanceof \Overtrue\LaravelLike\Like
                && $event->like->user_id === $user->id
                && $event->like->likeable_id === $post->id
                && $event->like->likeable_type === $post->getMorphClass();
        });
    }

    public function test_like_model_uses_custom_table_name()
    {
        // This test is skipped because it requires database schema changes
        // In a real application, you would need to create a migration to create the custom table
        $this->markTestSkipped('Requires database schema changes to create custom table');
    }

    public function test_like_model_uses_custom_model_class()
    {
        // Arrange - Create custom like model
        $customLikeModel = new class extends \Overtrue\LaravelLike\Like
        {
            protected $table = 'likes';
        };

        config(['like.like_model' => get_class($customLikeModel)]);

        $user = User::create(['name' => 'overtrue']);
        $post = Post::create(['title' => 'Hello world!']);

        // Act
        $like = $user->like($post);

        // Assert - Should use custom model class
        $this->assertInstanceOf(get_class($customLikeModel), $like, 'Should use custom like model class');
    }

    protected function getQueryLog(\Closure $callback): \Illuminate\Support\Collection
    {
        $sqls = \collect([]);
        \DB::listen(function ($query) use ($sqls) {
            $sqls->push(['sql' => $query->sql, 'bindings' => $query->bindings]);
        });

        $callback();

        return $sqls;
    }
}
