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
include_once dirname(__FILE__) . '/CmsDataPopulator.php';

/**
 * Base class contains some methods shared by subclass test cases.
 */
abstract class CmsTestBase extends \PHPUnit\Framework\TestCase
{
    protected $con;

    /**
     * This is run before each unit test; it populates the database.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->con = Propel::getConnection(PagePeer::DATABASE_NAME);
        $this->con->beginTransaction();
        CmsDataPopulator::depopulate($this->con);
        CmsDataPopulator::populate($this->con);
    }

    /**
     * This is run after each unit test.  It empties the database.
     */
    protected function tearDown(): void
    {
        // If an earlier operation in this test left the connection
        // uncommittable (a nested rollback under PostgreSQL leaves the real
        // transaction aborted - see BookstoreTestBase::tearDown()), even this
        // depopulate() call would fail since PostgreSQL rejects every
        // statement on an aborted transaction until it's rolled back for
        // real.
        if (!$this->con->isCommitable()) {
            $this->con->forceRollBack();
            parent::tearDown();

            return;
        }

        CmsDataPopulator::depopulate($this->con);

        if ($this->con->isCommitable()) {
            $this->con->commit();
        } else {
            $this->con->forceRollBack();
        }

        // See BookstoreTestBase::tearDown() for why this is still needed
        // even after the commit()/forceRollBack() above.
        if ($this->con->isInTransaction()) {
            $this->con->forceRollBack();
        }

        parent::tearDown();
    }

}
