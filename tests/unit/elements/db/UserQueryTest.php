<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements\db;

use Craft;
use craft\elements\User;
use craft\helpers\Db;
use craft\services\Users;
use craft\test\TestCase;
use crafttests\fixtures\UserGroupsFixture;
use DateTime;
use UnitTester;

/**
 * Unit tests for the User::find() query.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UserQueryTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Users
     */
    protected $users;

    /**
     * @var User
     */
    protected $pendingUser;

    /**
     * @var User
     */
    protected $lockedUser;

    /**
     * @var User
     */
    protected $activeUser;

    /**
     * @var User
     */
    protected $suspendedUser;

    public function _fixtures(): array
    {
        return [
            'user-groups' => [
                'class' => UserGroupsFixture::class,
            ],
        ];
    }

    /**
     *
     */
    public function testFindAll()
    {
        // Our admin user + Our active user + Our locked user are defaults
        $all = User::find()->all();
        self::assertCount(3, $all);
    }

    /**
     *
     */
    public function testCount()
    {
        $count = User::find()->count();
        self::assertSame('3', (string)$count);
    }

    /*
     *
     */
    public function testFindResults()
    {
        $this->userQueryFindTest([], []);

        $this->userQueryFindTest(['username' => 'activeUser'], ['username' => 'activeUser']);
        $this->userQueryFindTest(['firstName' => 'locked'], ['firstName' => 'locked', 'username' => 'lockedUser']);
        $this->userQueryFindTest(['email' => 'active@user.com'], ['username' => 'activeUser']);
        $this->userQueryFindTest(['status' => User::STATUS_SUSPENDED, 'lastName' => 'user'], ['username' => 'suspendedUser']);
        $this->userQueryFindTest(['status' => [User::STATUS_SUSPENDED, User::STATUS_PENDING], 'firstName' => 'Pending'], ['username' => 'pendingUser']);
        $this->userQueryFindTest(['admin' => true], ['username' => 'craftcms']);
    }

    /**
     *
     */
    public function testMultipleStatuses()
    {
        $results = User::find()
            ->status([User::STATUS_SUSPENDED, User::STATUS_PENDING])
            ->count();

        self::assertSame('2', (string)$results);
    }

    /**
     *
     */
    public function testFindInvalidParamCombination()
    {
        self::assertNull(
            User::find()
                ->status(User::STATUS_LOCKED)
                ->email('active@user.com')
                ->one()
        );
    }

    /**
     *
     */
    public function testSearchByGroup()
    {
        self::assertNull(User::find()->groupId('1000')->one());

        Craft::$app->getUsers()->assignUserToGroups($this->activeUser->id, ['1000', '1001', '1002']);

        self::assertSame('1', (string)User::find()->groupId('1000')->count());
        self::assertSame('0', (string)User::find()->groupId('123121223')->count());
        self::assertSame('1', (string)User::find()->groupId(['1001', 1002])->count());
        self::assertSame('1', (string)User::find()->groupId(['1001', '123121223'])->count());

        Craft::$app->getUsers()->assignUserToGroups($this->lockedUser->id, ['1000', '1002']);
        self::assertSame('2', (string)User::find()->groupId(['1001', '1002'])->count());
        self::assertSame('1', (string)User::find()->groupId(['1001'])->count());


        self::assertSame('2', (string)User::find()->group('group1')->count());
        self::assertSame('2', (string)User::find()->group(['group1', 'group2'])->count());
        self::assertSame('1', (string)User::find()->group(['group2'])->count());
        self::assertSame('0', (string)User::find()->group(['invald_handle'])->count());

        $userGroup = Craft::$app->getUserGroups()->getGroupByHandle('group1');
        self::assertSame('2', (string)User::find()->group($userGroup)->count());
    }

    /**
     * @todo More
     */
    public function testCan()
    {
        Craft::$app->setEdition(Craft::Pro);

        $results = [];
        foreach (User::find()->status(null)->all() as $user) {
            if ($user->can('accessCp')) {
                $results[] = $user;
            }
        }
        self::assertCount(1, $results);

        // @todo uncomment this when Craft bug is fixed
//        Craft::$app->getUserPermissions()->saveGroupPermissions('1000', ['accessCp']);
//        Craft::$app->getUsers()->assignUserToGroups($this->activeUser->id, ['1000']);
//
//        $results = [];
//        foreach (User::find()->status(null)->all() as $user) {
//            if ($user->can('accessCp')) {
//                $results[] = $user;
//            }
//        }
//
//        self::assertCount(2, $results);
    }

    /**
     * @param array $methodCalls
     * @param array $validationParams
     */
    protected function userQueryFindTest(array $methodCalls, array $validationParams)
    {
        $result = User::find();
        foreach ($methodCalls as $methodCall => $value) {
            $result->{$methodCall}($value);
        }
        $result = $result->one();

        self::assertInstanceOf(
            User::class,
            $result
        );

        foreach ($validationParams as $key => $validationParam) {
            self::assertSame($validationParam, $result->$key);
        }
    }

    /**
     * @internal We are not going to fixture this one as we need to count very specifically the amount of users
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();

        $this->pendingUser = new User(
            [
                'firstName' => 'Pending',
                'lastName' => 'User',
                'username' => 'pendingUser',
                'unverifiedEmail' => 'pending@user.com',
                'email' => 'pending@user.com',
                'pending' => true,
            ]
        );

        $this->lockedUser = new User(
            [
                'firstName' => 'locked',
                'lastName' => 'user',
                'username' => 'lockedUser',
                'email' => 'locked@user.com',
                'locked' => true,
                'invalidLoginCount' => 2,
                'lockoutDate' => Db::prepareDateForDb(new DateTime('now')),
            ]
        );

        $this->activeUser = new User(
            [
                'firstName' => 'active',
                'lastName' => 'user',
                'username' => 'activeUser',
                'email' => 'active@user.com',
            ]
        );

        $this->suspendedUser = new User(
            [
                'firstName' => 'suspended',
                'lastName' => 'user',
                'username' => 'suspendedUser',
                'email' => 'suspended@user.com',
                'suspended' => true,
            ]
        );

        $this->tester->saveElement($this->pendingUser);
        $this->tester->saveElement($this->suspendedUser);
        $this->tester->saveElement($this->lockedUser);
        $this->tester->saveElement($this->activeUser);
    }

    /**
     * @inheritdoc
     */
    protected function _after()
    {
        parent::_after();

        $this->tester->deleteElement($this->pendingUser);
        $this->tester->deleteElement($this->suspendedUser);
        $this->tester->deleteElement($this->lockedUser);
        $this->tester->deleteElement($this->activeUser);
    }
}
