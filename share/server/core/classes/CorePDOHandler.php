<?php
/*******************************************************************************
 *
 * CorePDOHandler.php - Class to handle any database using the PDO abstraction
 *
 * Copyright (c) 2004-2016 NagVis Project (Contact: info@nagvis.org)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 ******************************************************************************/

function _build_dsn_sqlite($params) {
    return $params['filename'];
}

function _build_dsn_common($params) {
    $connData = array_filter(array(
        "host" => $params['dbhost'],
        "port" => $params['dbport'],
        "dbname" => $params['dbname'],
        ), function($v) { return isset($v) && strlen($v) > 0; });

    return implode(';', array_map(
        function($k, $v) { return "$k=$v"; },
        array_keys($connData),
        $connData));
}

class CorePDOHandler {
    private $DB = null;
    private $file = null;
    private $dsn = null;

    private $driver = null;
    private $data = null;
    private $updating = false;
    private $lastErrorInfo = null;
    private $inTrans = false;

    // needs to be initialized after class declaration because directly
    // initializing it here is a syntax error in PHP 5.3
    private static $DRIVERS = null;

    public static function initialize_static() {
        self::$DRIVERS = array(
            '_common' => array(
                'queries' => array(
                    '-perm-add' => 'INSERT INTO perms ("mod", act, obj) VALUES (:mod, :act, :obj)',
                    '-perm-check' => 'SELECT COUNT(obj) AS num FROM perms WHERE "mod" = :mod AND act = :act AND obj = :obj',
                    '-perm-count' => 'SELECT COUNT(*) AS num FROM perms WHERE "mod"=:mod AND act=:act AND obj=:obj',
                    '-perm-delete-by-obj' => 'DELETE FROM perms WHERE "mod"=:mod AND obj=:obj',
                    '-perm-get-all' => 'SELECT "permId", "mod", act, obj FROM perms ORDER BY "mod",act,obj',
                    '-perm-get-by-user' => 'SELECT perms."mod" AS "mod", perms.act AS act, perms.obj AS obj '
                        .'FROM users2roles '
                        .'INNER JOIN roles2perms ON roles2perms."roleId" = users2roles."roleId" '
                        .'INNER JOIN perms ON perms."permId" = roles2perms."permId" '
                        .'WHERE users2roles."userId" = :id',
                    '-perm-rename-map' => 'UPDATE perms SET obj=:new_name '.
                        ' WHERE "mod"=\'Map\' AND obj=:old_name',
                    '-perm-change-act' => 'UPDATE perms SET act=:new_act WHERE mod=:mod and act=:old_act',

                    '-role-add' => 'INSERT INTO roles (name) VALUES (:name)',
                    '-role-add-with-id' => 'INSERT INTO roles ("roleId", name) VALUES (:roleId, :name)',
                    '-role-add-perm' => 'INSERT INTO roles2perms ("roleId", "permId") VALUES (:roleId, :permId)',
                    '-role-add-user-by-id' => 'INSERT INTO users2roles("userId", "roleId") VALUES(:userId, :roleId)',
                    '-role-count-by-name' => 'SELECT COUNT(*) AS num FROM roles WHERE name=:name',
                    '-role-delete-by-id' => 'DELETE FROM roles WHERE "roleId"=:roleId',
                    '-role-delete-by-user-id' => 'DELETE FROM users2roles WHERE "userId"=:userId',
                    '-role-delete-perm-by-id' => 'DELETE FROM roles2perms WHERE "roleId"=:roleId',
                    '-role-delete-perm-by-obj' => 'DELETE FROM roles2perms WHERE "permId" IN (SELECT "permId" FROM perms WHERE "mod"=:mod AND obj=:obj)',
                    '-role-get-all' => 'SELECT "roleId", name FROM roles ORDER BY name',
                    '-role-get-by-name' => 'SELECT "roleId" FROM roles WHERE name=:name',
                    '-role-get-by-user' => 'SELECT users2roles."roleId" AS "roleId", roles.name AS name '.
                        'FROM users2roles '.
                        'LEFT JOIN roles ON users2roles."roleId"=roles."roleId" '.
                        'WHERE "userId"=:id',
                    '-role-get-perm-by-id' => 'SELECT "permId" FROM roles2perms WHERE "roleId"=:roleId',
                    '-role-used-by' => 'SELECT users.name AS name FROM users2roles '.
                        'LEFT JOIN users ON users2roles."userId"=users."userId" '.
                        'WHERE users2roles."roleId"=:roleId',

                    '-user-add' => 'INSERT INTO users (name,password) VALUES (:name, :password)',
                    '-user-add-with-id' => 'INSERT INTO users ("userId", name, password) VALUES (:userId, :name, :password)',
                    '-user-count' => 'SELECT COUNT(*) AS num FROM users WHERE name=:name',
                    '-user-count-by-id' => 'SELECT COUNT(*) AS num FROM users WHERE "userId"=:userId',
                    '-user-delete' => 'DELETE FROM users WHERE "userId"=:userId',
                    '-user-delete-roles' => 'DELETE FROM users2roles WHERE "userId"=:userId',
                    '-user-get-all' => 'SELECT "userId", name FROM users ORDER BY name',
                    '-user-get-by-name' => 'SELECT "userId" FROM users WHERE name=:name',
                    '-user-get-by-pass' => 'SELECT "userId" FROM users WHERE name=:name AND password=:password',
                    '-user-update-pass' => 'UPDATE users SET password=:password WHERE "userId"=:id',

                    '-check-roles-perms' => 'SELECT COUNT(roles."name") AS num '.
                        'FROM perms '.
                        'INNER JOIN roles2perms ON roles2perms."permId" = perms."permId" '.
                        'INNER JOIN roles ON roles."roleId" = roles2perms."roleId" '.
                        'WHERE "mod" = :mod AND act = :act AND obj = :obj AND roles.name = :name',

                    '-create-pop-roles-perms-1' => 'INSERT INTO roles2perms ("roleId", "permId") '.
                        'SELECT r."roleId", p."permId" '.
                        'FROM roles r, perms p '.
                        'WHERE r.name = :r1 '.
                        '  AND p."mod" = :mod AND p.act = :act AND p.obj = :obj',

                    '-create-pop-roles-perms-2' => 'INSERT INTO roles2perms ("roleId", "permId") '.
                        'SELECT r."roleId", p."permId" '.
                        'FROM roles r, perms p '.
                        'WHERE r.name IN (:r1, :r2) '.
                        '  AND p."mod" = :mod AND p.act = :act AND p.obj = :obj',

                    '-create-pop-roles-perms-3' => 'INSERT INTO roles2perms ("roleId", "permId") '.
                        'SELECT r."roleId", p."permId" '.
                        'FROM roles r, perms p '.
                        'WHERE r.name IN (:r1, :r2, :r3) '.
                        '  AND p."mod" = :mod AND p.act = :act AND p.obj = :obj',

                    '-create-pop-perms-from-perms' => 'INSERT INTO perms ("mod", act, obj) '.
                        'SELECT :mod, :act, obj '.
                        'FROM perms '.
                        'WHERE "mod" = :fmod AND act = :fact',

                    '-create-update-db-version' => 'UPDATE version SET version=:version',
                ),

                'updates' => array(
                    '1091500' => array(
                        array('-perm-change-act', array('mod' => 'ChangePassword', 'old_act' => 'change', 'new_act' => '*'))
                    ),

                    '1080600' => array(
                        array('-perm-add', array('mod' => 'Url', 'act' => 'view', 'obj' => '*')),
                        array('-create-pop-roles-perms-3', array(
                            'r1' => 'Managers', 'r2' => 'Users (read-only)', 'r3' => 'Guests',
                            'mod' => 'Url', 'act' => 'view', 'obj' => '*')),
                    ),

                    '1080500' => array(
                        array('-perm-add', array('mod' => 'Action', 'act' => 'perform', 'obj' => '*')),
                        array('-create-pop-roles-perms-2', array(
                            'r1' => 'Managers', 'r2' => 'Users (read-only)',
                            'mod' => 'Action', 'act' => 'perform', 'obj' => '*')),
                    ),

                    '1060022' => array(
                        array('-perm-add', array('mod' => 'User', 'act' => 'setOption', 'obj' => '*')),
                        array('-create-pop-roles-perms-3', array(
                            'r1' => 'Managers', 'r2' => 'Users (read-only)', 'r3' => 'Guests',
                            'mod' => 'User', 'act' => 'setOption', 'obj' => '*')),
                    ),

                    '1050400' => array(
                        array('-perm-add', array('mod' => 'Multisite', 'act' => 'getMaps', 'obj' => '*')),
                        array('-create-pop-roles-perms-3', array(
                            'r1' => 'Managers', 'r2' => 'Users (read-only)', 'r3' => 'Guests',
                            'mod' => 'Multisite', 'act' => 'getMaps', 'obj' => '*')),
                    ),

                    '1050300' => array(
                        array('-perm-add', array('mod' => 'ManageBackgrounds', 'act' => 'manage', 'obj' => '*')),
                        array('-perm-add', array('mod' => 'ManageShapes', 'act' => 'manage', 'obj' => '*')),
                        array('-perm-add', array('mod' => 'Map', 'act' => 'manage', 'obj' => '*')),
                        array('-create-pop-roles-perms-1', array(
                            'r1' => 'Managers',
                            'mod' => 'ManageBackgrounds', 'act' => 'manage', 'obj' => '*')),
                        array('-create-pop-roles-perms-1', array(
                            'r1' => 'Managers',
                            'mod' => 'ManageShapes', 'act' => 'manage', 'obj' => '*')),
                        array('-create-pop-roles-perms-1', array(
                            'r1' => 'Managers',
                            'mod' => 'Map', 'act' => 'manage', 'obj' => '*')),
                    ),

                    '1050024' => array(
                        array('-create-pop-perms-from-perms', array(
                            'mod' => 'Map', 'act' => 'addModify',
                            'fmod' => 'Map', 'fact' => 'view')),
                        array('-create-pop-roles-perms-1', array(
                            'r1' => 'Managers',
                            'mod' => 'Map', 'act' => 'addModify', 'obj' => '*')),
                    ),
                ),
            ),

            'sqlite' => array(
                'build_dsn' => '_build_dsn_sqlite',

                // Note that these require a '.load' of an appropriate regex() function module!
                're_op' => 'REGEXP',
                're_op_neg' => 'NOT REGEXP',

                'queries' => array(
                    '-create-auth-users' => 'CREATE TABLE users (userId INTEGER, name VARCHAR(100), password VARCHAR(40), PRIMARY KEY(userId), UNIQUE(name))',
                    '-create-auth-roles' => 'CREATE TABLE roles (roleId INTEGER, name VARCHAR(100), PRIMARY KEY(roleId), UNIQUE(name))',
                    '-create-auth-perms' => 'CREATE TABLE perms (permId INTEGER, mod VARCHAR(100), act VARCHAR(100), obj VARCHAR(100), PRIMARY KEY(permId), UNIQUE(mod,act,obj))',
                    '-create-auth-users2roles' => 'CREATE TABLE users2roles (userId INTEGER, roleId INTEGER, PRIMARY KEY(userId, roleId))',
                    '-create-auth-roles2perms' => 'CREATE TABLE roles2perms (roleId INTEGER, permId INTEGER, PRIMARY KEY(roleId, permId))',

                    '-create-auth-version' => 'CREATE TABLE version (version VARCHAR(100), PRIMARY KEY(version))',
                    '-version-insert' => 'INSERT INTO version (version) VALUES (:version)',
                    '-version-update' => 'UPDATE version SET version=:version',

                    '-table-exists' => "SELECT * FROM sqlite_master WHERE type='table' AND name=:name",
                ),


                'init' => array(
                    'PRAGMA journal_mode = wal',
                ),
            ),

            'mysql' => array(
                'build_dsn' => '_build_dsn_common',

                're_op' => 'REGEXP BINARY',
                're_op_neg' => 'NOT REGEXP BINARY',

                'queries' => array(
                    '-create-auth-users' => 'CREATE TABLE users (userId INTEGER AUTO_INCREMENT, name VARCHAR(100), password VARCHAR(40), PRIMARY KEY(userId), UNIQUE(name))',
                    '-create-auth-roles' => 'CREATE TABLE roles ("roleId" INTEGER AUTO_INCREMENT, name VARCHAR(100), PRIMARY KEY(roleId), UNIQUE(name))',
                    '-create-auth-perms' => 'CREATE TABLE perms ("permId" INTEGER AUTO_INCREMENT, "mod" VARCHAR(100), act VARCHAR(100), obj VARCHAR(100), PRIMARY KEY("permId"), UNIQUE("mod", act, obj))',
                    '-create-auth-users2roles' => 'CREATE TABLE users2roles ("userId" INTEGER, "roleId" INTEGER, PRIMARY KEY("userId", "roleId"))',
                    '-create-auth-roles2perms' => 'CREATE TABLE roles2perms ("roleId" INTEGER, "permId" INTEGER, PRIMARY KEY("roleId", "permId"))',

                    '-create-auth-version' => 'CREATE TABLE version (version VARCHAR(100), PRIMARY KEY(version))',
                    '-version-insert' => 'INSERT INTO version (version) VALUES (:version)',
                    '-version-update' => 'UPDATE version SET version=:version',

                    '-table-exists' => "SHOW TABLES LIKE :name",
                ),

                'init' => array(
                    "SET SESSION sql_mode = 'PIPES_AS_CONCAT,ANSI_QUOTES,IGNORE_SPACE'",
                ),
            ),

            'pgsql' => array(
                'build_dsn' => '_build_dsn_common',

                're_op' => '~',
                're_op_neg' => '!~',

                'queries' => array(
                    '-table-exists' => "SELECT table_name ".
                        "FROM information_schema.tables ".
                        "WHERE table_schema='public' AND table_name = :name",
                ),
            ),
        );
    }

    public function __construct() {}

    public function open($driver, $params, $username, $password) {
        if ($driver == '_common') {
            error_log("Internal error: '_common' is not supposed to be used as a driver name");
            return false;
        } elseif (!array_key_exists($driver, self::$DRIVERS)) {
            error_log("Internal error: invalid database driver '$driver'");
            return false;
        }
        $drv_data = self::$DRIVERS[$driver];
        $dsn = "$driver:".$drv_data['build_dsn']($params);
        $this->dsn = $dsn;

        try {
            $this->DB = new PDO($dsn, $username, $password, array(
                // PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // TODO: This should be loaded from the configuration (e.g. in cae of backends)
                //PDO::ATTR_TIMEOUT => 1,
            ));
        } catch(PDOException $e) {
            error_log('Could not initialize a database connection: '.$e->getMessage());
            $this->lastErrorInfo = $e->getMessage();
            return false;
        }
        if($this->DB === false || $this->DB === null)
            return false;

        $this->driver = $driver;
        $this->data = $drv_data;
        $this->updating = false;
        $this->lastErrorInfo = null;

        if(isset($drv_data['init'])) {
            foreach($drv_data['init'] as $q) {
                $res = $this->DB->exec($q);
                if($res === false) {
                    $this->DB = null;
                    $this->lastErrorInfo = "Initial DB query ($q) failed";
                    return false;
                }
            }
        }
        return true;
    }

    public function getDSN() {
        return $this->dsn;
    }

    public function getRegularExpressionOperator() {
        return $this->data['re_op'];
    }

    public function getNegatedRegularExpressionOperator() {
        return $this->data['re_op_neg'];
    }

    public function prep($q) {
        // TODO: some kind of LRU cache for the dynamically built queries
        if (array_key_exists($q, $this->data['queries'])) {
            $sql = $this->data['queries'][$q];
        } elseif (array_key_exists($q, self::$DRIVERS['_common']['queries'])) {
            $sql = self::$DRIVERS['_common']['queries'][$q];
        } else {
            $sql = $q;
        }

        $st = $this->DB->prepare($sql);
        if ($st === false) {
            $this->lastErrorInfo = $this->DB->errorInfo();
            return false;
        }

        $st->setFetchMode(PDO::FETCH_ASSOC);
        return $st;
    }

    public function tableExist($table) {
        $res = $this->query('-table-exists', array('name' => $table));
        /* rowCount() is not always available for SELECT statements, so try the next best thing... */
        return $res !== false && $res->fetch() !== false;
    }

    public function query($query, $params = array()) {
        $this->lastErrorInfo = null;
        $st = $this->prep($query);
        if($st === false)
            return false;
        if($st->execute($params) === false) {
            $this->lastErrorInfo = $st->errorInfo();
            return false;
        }
        return $st;
    }

    public function queryFatal($s, $params = array()) {
        $res = $this->query($s, $params);
        if($res === FALSE)
            die("Could not execute the $s query: ".$this->errorString());
        else
            return $res;
    }

    public function count($query, $params) {
        $RET = $this->query($query, $params)->fetch();
        return intval($RET['num']);
    }

    public function error() {
        if (isset($this->lastErrorInfo))
            return $this->lastErrorInfo;
        else
            return $this->DB ? $this->DB->errorInfo() : '';
    }

    public function errorString() {
        $err = $this->error();
        $msg = $err[0];
        if (isset($err[1]))
            $msg .= '( '.$err[1].')';
        if (isset($err[2]))
            $msg .= ': '.$err[2];
        return $msg;
    }

    public function close() {
        $this->DB = null;
    }

    /**
     * PUBLIC is_nonnull_int()
     *
     * Checks whether a value (a string or an integer) returned by a database query
     * represents a valid integer.
     */
    public function is_nonnull_int($v) {
        return isset($v) && preg_match('/^-? (?: 0 | [1-9][0-9]* ) $/x', $v);
    }

    /**
     * PUBLIC eq_int()
     *
     * Checks whether a value (a string or an integer) returned by a database query
     * is a valid integer and is equal to the specified one.
     */
    public function eq_int($v, $exp) {
        return $this->is_nonnull_int($v) && intval($v) == $exp;
    }

    /**
     * PUBLIC eq_int()
     *
     * Checks whether a value (a string or an integer) returned by a database query
     * is either null or a valid integer equal to the specified one.
     * Returns true for an empty string; the caller should take care to use this
     * function only on database fields that are supposed to be integers.
     */
    public function null_or_eq_int($v, $exp) {
        return !isset($v) || $v === '' || $this->eq_int($v, $exp);
    }

    public function deletePermissions($mod, $name) {
        // Only create when not existing
        if($this->count('-perm-count', array('mod' => $mod, 'act' => 'view', 'obj' => $name)) > 0) {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: delete permissions for '.$mod.' '.$name);
            $this->query('-role-delete-perm-by-obj', array('mod' => $mod, 'obj' => $name));
            $this->query('-perm-delete-by-obj', array('mod' => $mod, 'obj' => $name));
        } else {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: won\'t delete '.$mod.' permissions '.$name);
        }
    }

    public function createMapPermissions($name) {
        // Only create when not existing
        if($this->count('-perm-count', array('mod' => 'Map', 'act' => 'view', 'obj' => $name)) <= 0) {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: create permissions for map '.$name);
            if ($this->updating && !$this->inTrans) {
                $this->DB->beginTransaction();
                $this->inTrans = true;
            }
            $this->query('-perm-add', array('mod' => 'Map', 'act' => 'view', 'obj' => $name));
            $this->query('-perm-add', array('mod' => 'Map', 'act' => 'edit', 'obj' => $name));
            $this->query('-perm-add', array('mod' => 'Map', 'act' => 'delete', 'obj' => $name));
        } else {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: won\'t create permissions for map '.$name);
        }

        return true;
    }

    public function createRotationPermissions($name) {
        // Only create when not existing
        if($this->count('-perm-count', array('mod' => 'Rotation', 'act' => 'view', 'obj' => $name)) <= 0) {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: create permissions for rotation '.$name);
            $this->query('-perm-add', array('mod' => 'Rotation', 'act' => 'view', 'obj' => $name));
        } else {
            if(DEBUG&&DEBUGLEVEL&2) debug('auth.db: won\'t create permissions for rotation '.$name);
        }

        return true;
    }

    public function updateDb() {
        // Read the current version from db
        $dbVersion = 0;
        if(!$this->tableExist('version'))
            $this->createVersionTable();
        else
            $dbVersion = GlobalCore::getInstance()->versionToTag($this->getDbVersion());

        $reason = 'Could not start a database transaction';
        $this->inTrans = false;
        $this->updating = true;
        try {
            ksort(self::$DRIVERS['_common']['updates']);
            foreach (self::$DRIVERS['_common']['updates'] as $ver => $queries) {
                if (intval($ver) < $dbVersion)
                    continue;

                if (!$this->inTrans) {
                    $this->DB->beginTransaction();
                    $this->inTrans = true;
                }
                foreach ($queries as $q) {
                    $reason = "Could not update the database to version $ver: '$q[0]'";
                    $this->query($q[0], $q[1]);
                }
            }

            foreach(GlobalCore::getInstance()->demoMaps AS $map) {
                if(count(GlobalCore::getInstance()->getAvailableMaps('/^'.$map.'$/')) <= 0)
                    continue;

                $this->createMapPermissions($map);

                // Ignore errors here; these may already have been set up
                if ($this->count('-check-roles-perms', array('name' => 'Guests', 'mod' => 'map', 'act' => 'view', 'obj' => $map)) > 1)
                {
                    if (!$this->inTrans) {
                        $this->DB->beginTransaction();
                        $this->inTrans = true;
                    }
                    $this->query('-create-pop-roles-perms-1',
                        array('r1' => 'Guests', 'mod' => 'Map', 'act' => 'view', 'obj' => $map));
                }
            }

            if ($dbVersion < GlobalCore::getInstance()->versionToTag(CONST_VERSION)) {
                if (!$this->inTrans) {
                    $this->DB->beginTransaction();
                    $this->inTrans = true;
                }
                $reason = 'Could not update the NagVis version in the database to '.CONST_VERSION;
                $this->query('-create-update-db-version', array('version' => CONST_VERSION));
            }

            $reason = 'Could not commit the transaction for updating the database schema';
        } catch(PDOException $e) {
            error_log($reason.': '.$e->getMessage());
            if ($this->inTrans) {
                try {
                    $this->DB->rollBack();
                } catch (PDOException $e) {
                    error_log('Could not roll back the database update transaction: '.$e->getMessage());
                }
                $this->inTrans = false;
            }
        }
        if ($this->inTrans) {
            try {
                $this->DB->commit();
            } catch (PDOException $e) {
                error_log("Could not commit the database transaction: ".$e->getMessage());
            }
            $this->inTrans = false;
        }
        $this->updating = false;
    }

    public function getDbVersion() {
        $data = $this->query('SELECT version FROM version')->fetch();
        return $data['version'];
    }

    public function updateDbVersion() {
        $this->query('-version-update', array('version' => CONST_VERSION));
    }

    public function createVersionTable() {
        $this->query('-create-auth-version');
        $this->query('-version-insert', array('version' => CONST_VERSION));
    }

    public function createInitialDb() {
        $this->queryFatal('-create-auth-users');
        $this->queryFatal('-create-auth-roles');
        $this->queryFatal('-create-auth-perms');
        $this->queryFatal('-create-auth-users2roles');
        $this->queryFatal('-create-auth-roles2perms');

        $this->createVersionTable();

        // If running in OMD create the 'omdadmin' user instead of 'admin'
        if(GlobalCore::getInstance()->omdSite() !== null) {
            $this->queryFatal('-user-add-with-id', array('userId' => 1, 'name' => 'omdadmin', 'password' => '051e0bbcfb79ea2a3ce5c487cc111051aac51ae8'));
        } else {
            $this->queryFatal('-user-add-with-id', array('userId' => 1, 'name' => 'admin', 'password' => '868103841a2244768b2dbead5dbea2b533940e20'));
        }

        $this->queryFatal('-user-add-with-id', array('userId' => 2, 'name' => 'guest', 'password' => 'a4e74a1d28ec981c945310d87f8d7b535d794cd2'));
        $this->queryFatal('-role-add-with-id', array('roleId' => 1, 'name' => 'Administrators'));
        $this->queryFatal('-role-add-with-id', array('roleId' => 2, 'name' => 'Users (read-only)'));
        $this->queryFatal('-role-add-with-id', array('roleId' => 3, 'name' => 'Guests'));
        $this->queryFatal('-role-add-with-id', array('roleId' => 4, 'name' => 'Managers'));

        // Access controll: Full access to everything
        $this->queryFatal('-perm-add', array('mod' => '*', 'act' => '*', 'obj' => '*'));

        // Access controll: Overview module levels
        $this->queryFatal('-perm-add', array('mod' => 'Overview', 'act' => 'view', 'obj' => '*'));

        // Access controll: Access to all General actions
        $this->queryFatal('-perm-add', array('mod' => 'General', 'act' => '*', 'obj' => '*'));

	// Create permissions for Action/peform/*
        $this->queryFatal('-perm-add', array('mod' => 'Action', 'act' => 'perform', 'obj' => '*'));

        // Access controll: Map module levels for the demo maps
        foreach(GlobalCore::getInstance()->demoMaps AS $map) {
            $this->createMapPermissions($map);
        }

        // Access controll: Rotation module levels for rotation "demo"
        $this->createRotationPermissions('demo');

        // Access controll: Change user options
        $this->queryFatal('-perm-add', array('mod' => 'User', 'act' => 'setOption', 'obj' => '*'));

        // Access controll: Change own password
        $this->queryFatal('-perm-add', array('mod' => 'ChangePassword', 'act' => '*', 'obj' => '*'));

        // Access controll: View maps via multisite
        $this->queryFatal('-perm-add', array('mod' => 'Multisite', 'act' => 'getMaps', 'obj' => '*'));

        // Access controll: Search objects on maps
        $this->queryFatal('-perm-add', array('mod' => 'Search', 'act' => 'view', 'obj' => '*'));

        // Access controll: Authentication: Logout
        $this->queryFatal('-perm-add', array('mod' => 'Auth', 'act' => 'logout', 'obj' => '*'));

        // Access controll: Summary permissions for viewing/editing/deleting all maps
        $this->createMapPermissions('*');

        // Access controll: Rotation module levels for viewing all rotations
        $this->queryFatal('-perm-add', array('mod' => 'Rotation', 'act' => 'view', 'obj' => '*'));

        // Access controll: Manage users
        $this->queryFatal('-perm-add', array('mod' => 'UserMgmt', 'act' => 'manage', 'obj' => '*'));

        // Access controll: Manage roles
        $this->queryFatal('-perm-add', array('mod' => 'RoleMgmt', 'act' => 'manage', 'obj' => '*'));

        // Access control: WUI Management pages
        $this->queryFatal('-perm-add', array('mod' => 'ManageBackgrounds', 'act' => 'manage', 'obj' => '*'));
        $this->queryFatal('-perm-add', array('mod' => 'ManageShapes', 'act' => 'manage', 'obj' => '*'));

        // Access controll: Edit/Delete maps
        $this->queryFatal('-perm-add', array('mod' => 'Map', 'act' => 'manage', 'obj' => '*'));
        $this->queryFatal('-perm-add', array('mod' => 'Map', 'act' => 'add', 'obj' => '*'));

        $this->queryFatal('-perm-add', array('mod' => 'MainCfg', 'act' => 'edit', 'obj' => '*'));

        // Access control: View URLs e.g. in rotation pools
        $this->queryFatal('-perm-add', array('mod' => 'Url', 'act' => 'view', 'obj' => '*'));

        // Assign the new permission to the managers, users, guests
        $this->queryFatal('-create-pop-roles-perms-3', array(
            'r1' => 'Managers', 'r2' => 'Users (read-only)', 'r3' => 'Guests',
            'mod' => 'Url', 'act' => 'view', 'obj' => '*'));

        /*
         * Administrators handling
         */

        $data = $this->queryFatal('-role-get-by-name', array('name' => 'Administrators'))->fetch();
        $this->queryFatal('-role-add-user-by-id', array('userId' => 1, 'roleId' => $data['roleId']));

        // Access assignment: Administrators => * * *
        $this->queryFatal('-create-pop-roles-perms-1', array(
            'r1' => 'Administrators',
            'mod' => '*', 'act' => '*', 'obj' => '*'));

        /*
         * Managers handling
         */

        // Permit all actions in General module
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'General', 'act' => '*', 'obj' => '*'));

        // Managers are allowed to perform actions
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'Action', 'act' => 'perform', 'obj' => '*'));

        // Access assignment: Managers => Allowed to update user options
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'User', 'act' => 'setOption', 'obj' => '*'));

        // Access assignment: Managers => Allowed to edit/delete all maps
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'Map', 'act' => 'manage', 'obj' => '*'));
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'Map', 'act' => 'delete', 'obj' => '*'));
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'Map', 'act' => 'edit', 'obj' => '*'));

        // Access assignment: Managers => Allowed to create maps
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'Map', 'act' => 'add', 'obj' => '*'));

        // Access assignment: Managers => Allowed to manage backgrounds and shapes
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'ManageBackgrounds', 'act' => 'manage', 'obj' => '*'));
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'ManageShapes', 'act' => 'manage', 'obj' => '*'));

        // Access assignment: Managers => Allowed to view the overview
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'Overview', 'act' => 'view', 'obj' => '*'));

        // Access assignment: Managers => Allowed to view all maps
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'Map', 'act' => 'view', 'obj' => '*'));

        // Access assignment: Managers => Allowed to view all rotations
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'Rotation', 'act' => 'view', 'obj' => '*'));

        // Access assignment: Managers => Allowed to change their passwords
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'ChangePassword', 'act' => 'change', 'obj' => '*'));

        // Access assignment: Managers => Allowed to view their maps via multisite
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'Multisite', 'act' => 'getMaps', 'obj' => '*'));

        // Access assignment: Managers => Allowed to search objects
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'Search', 'act' => 'view', 'obj' => '*'));

        // Access assignment: Managers => Allowed to logout
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Managers', 'mod' => 'Auth', 'act' => 'logout', 'obj' => '*'));

        /*
         * Users handling
         */

        // Users are allowed to perform actions
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Users (read-only)', 'mod' => 'Action', 'act' => 'perform', 'obj' => '*'));

        // Permit all actions in General module
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Users (read-only)', 'mod' => 'General', 'act' => '*', 'obj' => '*'));

        // Access assignment: Users => Allowed to update user options
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Users (read-only)', 'mod' => 'User', 'act' => 'setOption', 'obj' => '*'));

        // Access assignment: Users => Allowed to view the overview
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Users (read-only)', 'mod' => 'Overview', 'act' => 'view', 'obj' => '*'));

        // Access assignment: Users => Allowed to view all maps
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Users (read-only)', 'mod' => 'Map', 'act' => 'view', 'obj' => '*'));

        // Access assignment: Users => Allowed to view all rotations
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Users (read-only)', 'mod' => 'Rotation', 'act' => 'view', 'obj' => '*'));

        // Access assignment: Users => Allowed to change their passwords
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Users (read-only)', 'mod' => 'ChangePassword', 'act' => 'change', 'obj' => '*'));

        // Access assignment: Users => Allowed to view their maps via multisite
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Users (read-only)', 'mod' => 'Multisite', 'act' => 'getMaps', 'obj' => '*'));

        // Access assignment: Users => Allowed to search objects
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Users (read-only)', 'mod' => 'Search', 'act' => 'view', 'obj' => '*'));

        // Access assignment: Users => Allowed to logout
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Users (read-only)', 'mod' => 'Auth', 'act' => 'logout', 'obj' => '*'));

        /*
         * Guest handling
         */

        $data = $this->queryFatal('-role-get-by-name', array('name' => 'Guests'))->fetch();
        $this->queryFatal('-role-add-user-by-id', array('userId' => 2, 'roleId' => $data['roleId']));

        // Permit all actions in General module
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Guests', 'mod' => 'General', 'act' => '*', 'obj' => '*'));

        // Access assignment: Guests => Allowed to update user options
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Guests', 'mod' => 'User', 'act' => 'setOption', 'obj' => '*'));

        // Access assignment: Guests => Allowed to view the overview
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Guests', 'mod' => 'Overview', 'act' => 'view', 'obj' => '*'));

        // Access assignment: Guests => Allowed to view their maps via multisite
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Guests', 'mod' => 'Multisite', 'act' => 'getMaps', 'obj' => '*'));

        // Access assignment: Guests => Allowed to view the demo maps
        foreach(GlobalCore::getInstance()->demoMaps AS $map) {
            $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Guests', 'mod' => 'Map', 'act' => 'view', 'obj' => $map));
        }

        // Access assignment: Guests => Allowed to view the demo rotation
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Guests', 'mod' => 'Rotation', 'act' => 'view', 'obj' => 'demo'));

        // Access assignment: Guests => Allowed to change their passwords
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Guests', 'mod' => 'ChangePassword', 'act' => 'change', 'obj' => '*'));

        // Access assignment: Guests => Allowed to search objects
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Guests', 'mod' => 'Search', 'act' => 'view', 'obj' => '*'));

        // Access assignment: Guests => Allowed to logout
        $this->queryFatal('-create-pop-roles-perms-1', array('r1' => 'Guests', 'mod' => 'Auth', 'act' => 'logout', 'obj' => '*'));
    }
}

CorePDOHandler::initialize_static();

?>
