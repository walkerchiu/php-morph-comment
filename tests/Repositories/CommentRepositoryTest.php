<?php

namespace WalkerChiu\MorphComment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use WalkerChiu\MorphComment\Models\Entities\Comment;
use WalkerChiu\MorphComment\Models\Entities\CommentLang;
use WalkerChiu\MorphComment\Models\Repositories\CommentRepository;

class CommentRepositoryTest extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected $repository;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        //$this->loadLaravelMigrations(['--database' => 'mysql']);
        $this->loadMigrationsFrom(__DIR__ .'/../migrations');
        $this->withFactories(__DIR__ .'/../../src/database/factories');

        $this->repository = $this->app->make(CommentRepository::class);
    }

    /**
     * To load your package service provider, override the getPackageProviders.
     *
     * @param \Illuminate\Foundation\Application  $app
     * @return Array
     */
    protected function getPackageProviders($app)
    {
        return [\WalkerChiu\Core\CoreServiceProvider::class,
                \WalkerChiu\MorphComment\MorphCommentServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
    }

    /**
     * A basic functional test on CommentRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\Repository
     *
     * @return void
     */
    public function testMorphCommentRepository()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-comment.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-comment.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-comment.soft_delete', 1);

        // Give
        $faker = \Faker\Factory::create();

        $id_list = [];
        for ($i=1; $i<=3; $i++) {
            $record = $this->repository->save([
                'score'   => $faker->randomDigitNotNull,
                'subject' => $faker->sentence,
                'content' => $faker->text
            ]);
            array_push($id_list, $record->id);
        }

        // Get and Count records after creation
            // When
            $records = $this->repository->get();
            $count   = $this->repository->count();
            // Then
            $this->assertCount(3, $records);
            $this->assertEquals(3, $count);

        // Find someone
            // When
            $record = $this->repository->first();
            // Then
            $this->assertNotNull($record);

            // When
            $record = $this->repository->find($faker->uuid());
            // Then
            $this->assertNull($record);

        // Delete someone
            // When
            $this->repository->deleteByIds([$id_list[0]]);
            $count = $this->repository->count();
            // Then
            $this->assertEquals(2, $count);

            // When
            $this->repository->deleteByExceptIds([$id_list[2]]);
            $count = $this->repository->count();
            $record = $this->repository->find($id_list[2]);
            // Then
            $this->assertEquals(1, $count);
            $this->assertNotNull($record);

            // When
            $count = $this->repository->where('id', '>', 0)->count();
            // Then
            $this->assertEquals(1, $count);

            // When
            $count = $this->repository->whereWithTrashed('id', '>', 0)->count();
            // Then
            $this->assertEquals(3, $count);

            // When
            $count = $this->repository->whereOnlyTrashed('id', '>', 0)->count();
            // Then
            $this->assertEquals(2, $count);

        // Force delete someone
            // When
            $this->repository->forcedeleteByIds([$id_list[2]]);
            $records = $this->repository->get();
            // Then
            $this->assertCount(0, $records);

        // Restore records
            // When
            $this->repository->restoreByIds([$id_list[0], $id_list[1]]);
            $count = $this->repository->count();
            // Then
            $this->assertEquals(2, $count);
    }

    /**
     * Unit test about Lang creation on CommentRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\MorphComment\Models\Repositories\MorphCommentRepository
     * 
     * @return void
     */
    public function testcreateLangWithoutCheck()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-comment.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-comment.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-comment.soft_delete', 1);

        // Give
        factory(Comment::class)->create();

        // Find record
            // When
            $record = $this->repository->first();
            // Then
            $this->assertNotNull($record);

        // Create Lang
            // When
            $lang = $this->repository->createLangWithoutCheck(['morph_type' => get_class($record), 'morph_id' => $record->id, 'code' => 'en_us', 'key' => 'name', 'value' => 'Hello']);
            // Then
            $this->assertInstanceOf(CommentLang::class, $lang);
    }

    /**
     * Unit test about Enable and Disable on CommentRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\MorphComment\Models\Repositories\MorphCommentRepository
     *
     * @return void
     */
    public function testEnableAndDisable()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-comment.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-comment.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-comment.soft_delete', 1);

        // Give
        $db_morph_1 = factory(Comment::class)->create(['is_enabled' => 1]);
        $db_morph_2 = factory(Comment::class)->create();
        $db_morph_3 = factory(Comment::class)->create();
        $db_morph_4 = factory(Comment::class)->create();

        // Count records
            // When
            $count = $this->repository->count();
            $count_enabled = $this->repository->ofEnabled()->count();
            $count_disabled = $this->repository->ofDisabled()->count();
            // Then
            $this->assertEquals(4, $count);
            $this->assertEquals(1, $count_enabled);
            $this->assertEquals(3, $count_disabled);

        // Enable records
            // When
            $this->repository->whereToEnable('id', '=', $db_morph_4->id);
            $count_enabled = $this->repository->ofEnabled()->count();
            $count_disabled = $this->repository->ofDisabled()->count();
            // Then
            $this->assertEquals(2, $count_enabled);
            $this->assertEquals(2, $count_disabled);

        // Disable records
            // When
            $this->repository->whereToDisable('id', '>', 0);
            $count_enabled = $this->repository->ofEnabled()->count();
            $count_disabled = $this->repository->ofDisabled()->count();
            // Then
            $this->assertEquals(0, $count_enabled);
            $this->assertEquals(4, $count_disabled);
    }

    /**
     * Unit test about Query List on CommentRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\MorphComment\Models\Repositories\MorphCommentRepository
     *
     * @return void
     */
    public function testQueryList()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-comment.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-comment.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-comment.soft_delete', 1);

        // Give
        $db_morph_1 = factory(Comment::class)->create();
        $db_morph_2 = factory(Comment::class)->create();
        $db_morph_3 = factory(Comment::class)->create();
        $db_morph_4 = factory(Comment::class)->create();

        // Get query
            // When
            sleep(1);
            $this->repository->find($db_morph_3->id)->touch();
            $records = $this->repository->ofNormal()->get();
            // Then
            $this->assertCount(4, $records);

            // When
            $record = $records->first();
            // Then
            $this->assertArrayNotHasKey('deleted_at', $record->toArray());
            $this->assertEquals($db_morph_3->id, $record->id);

        // Get query of trashed records
            // When
            $this->repository->deleteByIds([$db_morph_4->id]);
            $this->repository->deleteByIds([$db_morph_1->id]);
            $records = $this->repository->ofTrash()->get();
            // Then
            $this->assertCount(2, $records);

            // When
            $record = $records->first();
            // Then
            $this->assertArrayHasKey('deleted_at', $record);
            $this->assertEquals($db_morph_1->id, $record->id);
    }

    /**
     * Unit test about FormTrait on CommentRepository.
     *
     * For WalkerChiu\Core\Models\Repositories\RepositoryTrait
     *     WalkerChiu\MorphComment\Models\Repositories\MorphCommentRepository
     *     WalkerChiu\Core\Models\Forms\FormTrait
     *
     * @return void
     */
    public function testFormTrait()
    {
        // Config
        Config::set('wk-core.onoff.core-lang_core', 0);
        Config::set('wk-morph-comment.onoff.core-lang_core', 0);
        Config::set('wk-core.lang_log', 1);
        Config::set('wk-morph-comment.lang_log', 1);
        Config::set('wk-core.soft_delete', 1);
        Config::set('wk-morph-comment.soft_delete', 1);

        // Name
            // Give
            $db_morph_1 = factory(Comment::class)->create();
            $db_morph_2 = factory(Comment::class)->create();
            $db_lang_1 = $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_1->id, 'morph_type' => Comment::class, 'code' => 'en_us', 'key' => 'name', 'value' => 'Hello']);
            $db_lang_2 = $this->repository->createLangWithoutCheck(['morph_id' => $db_morph_2->id, 'morph_type' => Comment::class, 'code' => 'zh_tw', 'key' => 'name', 'value' => '您好']);
            // When
            $result_1 = $this->repository->checkExistName('en_us', null, 'Hello');
            $result_2 = $this->repository->checkExistName('en_us', null, 'Hi');
            $result_3 = $this->repository->checkExistName('en_us', $db_morph_1->id, 'Hello');
            $result_4 = $this->repository->checkExistName('en_us', $db_morph_1->id, '您好');
            $result_5 = $this->repository->checkExistName('zh_tw', $db_morph_1->id, '您好');
            $result_6 = $this->repository->checkExistNameOfEnabled('en_us', null, 'Hello');
            // Then
            $this->assertTrue($result_1);
            $this->assertTrue(!$result_2);
            $this->assertTrue(!$result_3);
            $this->assertTrue(!$result_4);
            $this->assertTrue($result_5);
            $this->assertTrue(!$result_6);
    }
}
