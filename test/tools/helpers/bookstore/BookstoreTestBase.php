<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/../../../../runtime/lib/Propel.php';
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/../../../fixtures/bookstore/build/classes'));
Propel::init(dirname(__FILE__) . '/../../../fixtures/bookstore/build/conf/bookstore-conf.php');

/**
 * Base class contains some methods shared by subclass test cases.
 */
abstract class BookstoreTestBase extends \PHPUnit\Framework\TestCase
{
    protected $con;

    /**
     * This is run before each unit test; it populates the database.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->con = Propel::getConnection(BookPeer::DATABASE_NAME);
        $this->con->beginTransaction();
    }

    /**
     * This is run after each unit test. It empties the database.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        // Only commit if the transaction hasn't failed.
        // This is because tearDown() is also executed on a failed tests,
        // and we don't want to call PropelPDO::commit() in that case
        // since it will trigger an exception on its own
        // ('Cannot commit because a nested transaction was rolled back')
        if ($this->con->isCommitable()) {
            $this->con->commit();
        } else {
            // A nested rollback happened during the test (e.g. a save() that
            // hit a genuine SQL error) and left the connection uncommittable.
            // A nested PropelPDO::rollBack() only flips a flag - it never
            // issues a real ROLLBACK - so the underlying transaction is still
            // open here, and under PostgreSQL (unlike MySQL) it is now
            // permanently aborted: every further statement on this connection
            // would fail until a real ROLLBACK is issued. Force one now so
            // this doesn't leak an open, unusable transaction (and whatever
            // locks it holds) into every subsequent test sharing this
            // connection.
            $this->con->forceRollBack();
        }

        // Belt-and-suspenders: commit() above only issues a real COMMIT (and
        // resets the nesting depth to 0) when the depth is exactly 1. If some
        // code during the test opened a nested transaction that never reached
        // its matching commit()/rollBack() (e.g. an exception thrown between
        // the two), the depth stays elevated forever - isCommitable() still
        // reports true (nothing ever flagged it uncommittable), so commit()
        // above just silently decrements without ever closing the real,
        // underlying transaction. Left alone, that leftover open transaction
        // - and whatever locks it holds - would carry over into every test
        // that runs after this one on the same connection. Make sure this
        // test never hands off an open transaction to the next one.
        if ($this->con->isInTransaction()) {
            $this->con->forceRollBack();
        }
    }
}
